<?php

namespace App\Mail;

use App\Models\AutoReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled auto-report (12h/24h/7d/15d/30d/custom) ki owner email —
 * summary body me, PDF + Excel attachments ke saath. Attachments
 * ReportExportService se tayyar hokar bytes ki surat me aati hain;
 * koi ek export fail ho jaye to email us ke baghair bhi chali jati hai.
 */
class AutoReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $summary
     * @param  array{count: int, total: float}  $fbr
     */
    public function __construct(
        public readonly AutoReport $report,
        public readonly array $summary,
        public readonly array $fbr = ['count' => 0, 'total' => 0.0],
        public readonly ?string $pdfBytes = null,
        public readonly ?string $excelBytes = null,
        public readonly ?string $pdfFilename = null,
        public readonly ?string $excelFilename = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $branch = $this->report->branch?->name ?? ($this->summary['branch'] ?? 'Branch');
        $period = strtoupper((string) $this->report->period);
        $date = $this->report->report_date?->format('d M Y') ?? now()->format('d M Y');

        return new Envelope(
            subject: "📊 Auto Report ({$period}) — {$branch} — {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auto-report',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->pdfBytes !== null && $this->pdfBytes !== '') {
            $attachments[] = Attachment::fromData(
                fn () => $this->pdfBytes,
                $this->pdfFilename ?? 'report.pdf'
            )->withMime('application/pdf');
        }

        if ($this->excelBytes !== null && $this->excelBytes !== '') {
            $attachments[] = Attachment::fromData(
                fn () => $this->excelBytes,
                $this->excelFilename ?? 'report.xlsx'
            )->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        return $attachments;
    }
}
