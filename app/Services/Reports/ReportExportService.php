<?php

namespace App\Services\Reports;

use App\Exports\AutoReportExport;
use App\Models\AutoReport;
use App\Models\FbrInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * AutoReport (scheduled report) ko PDF / Excel bytes me badalta hai.
 * Email attachment (ReportDeliveryService) aur manual download
 * (ReportController) dono yehi service use karte hain taake owner ko
 * hamesha ek jaisi file mile.
 */
class ReportExportService
{
    /**
     * Summary ko normalize karo — purani rows me summary_json string
     * (double-encoded) bhi ho sakti hai.
     */
    public function summary(AutoReport $report): array
    {
        $summary = $report->summary_json;

        if (is_string($summary)) {
            $summary = json_decode($summary, true) ?: [];
        }

        return is_array($summary) ? $summary : [];
    }

    /**
     * Period ke andar FBR fiscalised sales ki asal figures (fbr_invoices
     * table se). Table/record na ho to zeros — jhooti figure kabhi nahi.
     *
     * @return array{count: int, total: float}
     */
    public function fbrFigures(AutoReport $report): array
    {
        try {
            $summary = $this->summary($report);
            $start = $summary['period_start'] ?? null;
            $end = $summary['period_end'] ?? null;

            if (! $start || ! $end) {
                return ['count' => 0, 'total' => 0.0];
            }

            $query = FbrInvoice::query()
                ->where('branch_id', $report->branch_id)
                ->whereBetween('created_at', [$start, $end]);

            return [
                'count' => (int) (clone $query)->count(),
                'total' => (float) $query->sum('total_amount'),
            ];
        } catch (\Throwable $e) {
            Log::warning('FBR figures for auto-report failed: ' . $e->getMessage(), [
                'report_id' => $report->id,
            ]);

            return ['count' => 0, 'total' => 0.0];
        }
    }

    /**
     * PDF bytes (DomPDF, saaf A4 view: reports.auto-report-pdf).
     */
    public function pdfBytes(AutoReport $report): string
    {
        $summary = $this->summary($report);

        $pdf = Pdf::loadView('reports.auto-report-pdf', [
            'report' => $report,
            'summary' => $summary,
            'fbr' => $this->fbrFigures($report),
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Excel (.xlsx) bytes — Summary / Payments / Fuel Breakdown sheets.
     */
    public function excelBytes(AutoReport $report): string
    {
        $export = new AutoReportExport(
            $this->summaryRows($report),
            $this->paymentRows($report),
            $this->fuelRows($report),
        );

        return Excel::raw($export, ExcelFormat::XLSX);
    }

    public function pdfFilename(AutoReport $report): string
    {
        return $this->baseFilename($report) . '.pdf';
    }

    public function excelFilename(AutoReport $report): string
    {
        return $this->baseFilename($report) . '.xlsx';
    }

    private function baseFilename(AutoReport $report): string
    {
        $branch = Str::slug($report->branch?->name ?? 'branch');
        $date = $report->report_date?->format('Y-m-d') ?? now()->format('Y-m-d');

        return "report-{$report->period}-{$branch}-{$date}";
    }

    /**
     * Summary sheet ke label/value rows.
     *
     * @return array<int, array<int, mixed>>
     */
    public function summaryRows(AutoReport $report): array
    {
        $s = $this->summary($report);
        $fbr = $this->fbrFigures($report);

        $rows = [
            ['Auto Report — ' . strtoupper((string) $report->period)],
            ['Branch', $s['branch'] ?? $report->branch?->name ?? ''],
            ['Period Start', $s['period_start'] ?? ''],
            ['Period End', $s['period_end'] ?? ''],
            ['Generated At', $s['generated_at'] ?? ''],
            [],
            ['Metric', 'Amount'],
            ['Total Sales (Rs)', $s['total_sales'] ?? 0],
            ['Total Litres', $s['total_litres'] ?? 0],
            ['Transactions', $s['transaction_count'] ?? 0],
            ['Average Price / Litre (Rs)', $s['avg_price_per_liter'] ?? 0],
            [],
            ['Profitability', 'Amount'],
            ['Total Revenue (Rs)', $s['total_revenue'] ?? 0],
            ['COGS (Rs)', $s['cogs'] ?? 0],
            ['Gross Margin (Rs)', $s['gross_margin'] ?? 0],
            ['Gross Margin %', $s['gross_margin_percentage'] ?? 0],
            ['Expenses (Rs)', $s['expenses'] ?? 0],
            ['Net Profit (Rs)', $s['net_profit'] ?? 0],
            ['Net Profit %', $s['net_profit_percentage'] ?? 0],
            [],
            ['Stock Movement', 'Litres'],
            ['Purchases (L)', $s['purchases'] ?? 0],
            ['Sales (L)', $s['sales'] ?? 0],
            ['Variance (L)', $s['variance'] ?? 0],
        ];

        if ($fbr['count'] > 0) {
            $rows[] = [];
            $rows[] = ['FBR Fiscalised Sales', ''];
            $rows[] = ['FBR Invoices (count)', $fbr['count']];
            $rows[] = ['FBR Invoices Total (Rs)', round($fbr['total'], 2)];
        }

        return $rows;
    }

    /**
     * Payments sheet ke rows — payment method totals summary se.
     *
     * @return array<int, array<int, mixed>>
     */
    public function paymentRows(AutoReport $report): array
    {
        $s = $this->summary($report);

        return [
            ['Payment Method', 'Amount (Rs)'],
            ['Cash', $s['cash_sales'] ?? 0],
            ['Card', $s['card_sales'] ?? 0],
            ['Credit (Udhaar)', $s['credit_sales'] ?? 0],
            ['Other', $s['other_sales'] ?? 0],
            ['Total', $s['total_sales'] ?? 0],
        ];
    }

    /**
     * Fuel breakdown sheet ke rows.
     *
     * @return array<int, array<int, mixed>>
     */
    public function fuelRows(AutoReport $report): array
    {
        $s = $this->summary($report);

        $rows = [
            ['Fuel', 'Litres', 'Revenue (Rs)'],
        ];

        foreach ($s['fuel_breakdown'] ?? [] as $fuel) {
            $rows[] = [
                $fuel['fuel'] ?? 'Unknown',
                $fuel['litres'] ?? 0,
                $fuel['revenue'] ?? 0,
            ];
        }

        return $rows;
    }
}
