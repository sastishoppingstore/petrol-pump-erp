<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class AutoReportSummarySheet implements FromArray, WithTitle
{
    /** @param array<int, array<int, mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Summary';
    }
}
