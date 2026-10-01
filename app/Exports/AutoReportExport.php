<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * AutoReport (scheduled report) ki Excel workbook — 3 sheets:
 * Summary, Payments, Fuel Breakdown. Rows ReportExportService tayyar
 * karta hai taake email attachment aur manual download bilkul ek jaisi
 * file dein.
 */
class AutoReportExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array<int, mixed>>  $summaryRows
     * @param  array<int, array<int, mixed>>  $paymentRows
     * @param  array<int, array<int, mixed>>  $fuelRows
     */
    public function __construct(
        private readonly array $summaryRows,
        private readonly array $paymentRows,
        private readonly array $fuelRows,
    ) {
    }

    public function sheets(): array
    {
        return [
            new AutoReportSummarySheet($this->summaryRows),
            new AutoReportPaymentsSheet($this->paymentRows),
            new AutoReportFuelSheet($this->fuelRows),
        ];
    }
}
