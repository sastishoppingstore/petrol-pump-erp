<?php

namespace App\Services\Reports;

use App\Mail\AutoReportMail;
use App\Models\AutoReport;
use App\Services\System\MailSettingsService;
use App\Services\System\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * AutoReport ki email delivery — owner ko scheduled report PDF + Excel
 * attachments ke saath bhejna.
 *
 * Flow:
 *  - Recipients: setting "report_email_recipients" (group: reports,
 *    comma-separated). Khali ho to company email (settings group
 *    "company", key "email") par fallback.
 *  - Toggle: setting "report_send_via_email" (default on).
 *  - Due check: report ki data_json me "emailed_at" save hota hai; agli
 *    email tab hi jati hai jab period ki muddat (12h/24h/7d/...) guzar
 *    jaye — hourly generation par owner ko spam nahi hota.
 *  - Har failure try/catch + Log: email fail hone se report generation
 *    kabhi nahi rukti. SMTP configure na ho (DB setting + .env dono
 *    khali/log) to sirf log karke skip.
 */
class ReportDeliveryService
{
    /** Period => kitne ghante baad agli email due hai. */
    private const PERIOD_HOURS = [
        '12h' => 12,
        '24h' => 24,
        '7d' => 168,
        '15d' => 360,
        '30d' => 720,
    ];

    public function __construct(
        private readonly SettingService $settings,
        private readonly MailSettingsService $mailSettings,
        private readonly ReportExportService $exports,
    ) {
    }

    /**
     * Email recipients ki saaf list. Pehle admin ki di hui list, warna
     * company email.
     *
     * @return array<int, string>
     */
    public function recipients(): array
    {
        $raw = (string) ($this->settings->get('report_email_recipients') ?? '');

        $emails = collect(explode(',', $raw))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if ($emails !== []) {
            return $emails;
        }

        $companyEmail = trim((string) ($this->settings->get('email') ?? ''));

        return $companyEmail !== '' && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)
            ? [$companyEmail]
            : [];
    }

    public function isEmailEnabled(): bool
    {
        return filter_var(
            $this->settings->get('report_send_via_email', '1'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Kya is report ki email ab due hai? (pehli dafa, ya period ki
     * muddat guzarne ke baad)
     */
    public function isDue(AutoReport $report): bool
    {
        $data = is_array($report->data_json) ? $report->data_json : [];
        $emailedAt = $data['emailed_at'] ?? null;

        if (! $emailedAt) {
            return true;
        }

        try {
            $last = Carbon::parse($emailedAt);
        } catch (\Throwable) {
            return true;
        }

        return $last->copy()->addHours($this->periodHours($report))->isPast();
    }

    private function periodHours(AutoReport $report): int
    {
        if ($report->period === 'custom') {
            $hours = (int) ($this->settings->get('auto_report_custom_hours') ?? 0);

            return $hours > 0 ? $hours : 24;
        }

        return self::PERIOD_HOURS[$report->period] ?? 24;
    }

    /**
     * Agar email on hai aur due hai to bhej do. Generation path
     * (console command) se call hota hai.
     */
    public function deliverIfDue(AutoReport $report): bool
    {
        if (! $this->isEmailEnabled()) {
            return false;
        }

        if (! $this->isDue($report)) {
            return false;
        }

        return $this->send($report);
    }

    /**
     * Report email bhejna (due check ke baghair — manual/forced use ke
     * liye). Failure par false + log; kabhi exception bahar nahi jati.
     */
    public function send(AutoReport $report): bool
    {
        try {
            $recipients = $this->recipients();

            if ($recipients === []) {
                Log::warning('Auto-report email skipped: koi recipient nahi (report_email_recipients + company email dono khali)', [
                    'report_id' => $report->id,
                    'branch_id' => $report->branch_id,
                ]);

                return false;
            }

            // DB SMTP settings runtime config par laagu karo.
            $this->mailSettings->apply();

            if (! $this->mailSettings->isConfigured()) {
                Log::warning('Auto-report email skipped: SMTP configure nahi hai (DB email settings / .env dono khali)', [
                    'report_id' => $report->id,
                ]);

                return false;
            }

            $summary = $this->exports->summary($report);
            $fbr = $this->exports->fbrFigures($report);

            // Attachments — ek export fail ho to doosra phir bhi jaye.
            $pdfBytes = null;
            $excelBytes = null;

            try {
                $pdfBytes = $this->exports->pdfBytes($report);
            } catch (\Throwable $e) {
                Log::error('Auto-report PDF export failed: ' . $e->getMessage(), ['report_id' => $report->id]);
            }

            try {
                $excelBytes = $this->exports->excelBytes($report);
            } catch (\Throwable $e) {
                Log::error('Auto-report Excel export failed: ' . $e->getMessage(), ['report_id' => $report->id]);
            }

            Mail::to($recipients)->send(new AutoReportMail(
                report: $report,
                summary: $summary,
                fbr: $fbr,
                pdfBytes: $pdfBytes,
                excelBytes: $excelBytes,
                pdfFilename: $this->exports->pdfFilename($report),
                excelFilename: $this->exports->excelFilename($report),
            ));

            // Delivery ka nishan — status/sent fields + data_json meta.
            $data = is_array($report->data_json) ? $report->data_json : [];
            $data['emailed_at'] = now()->toDateTimeString();
            $data['emailed_period_end'] = $summary['period_end'] ?? null;

            $report->update([
                'status' => 'sent_email',
                'sent_at' => now(),
                'recipient_email' => $recipients,
                'pdf_size_bytes' => $pdfBytes !== null ? strlen($pdfBytes) : $report->pdf_size_bytes,
                'excel_size_bytes' => $excelBytes !== null ? strlen($excelBytes) : $report->excel_size_bytes,
                'data_json' => $data,
            ]);

            Log::info('Auto-report email sent', [
                'report_id' => $report->id,
                'period' => $report->period,
                'recipients' => $recipients,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Auto-report email failed: ' . $e->getMessage(), [
                'report_id' => $report->id,
                'period' => $report->period,
            ]);

            return false;
        }
    }
}
