<?php

namespace App\Services\Reports;

use App\Models\Cheque;
use App\Models\Customer;
use App\Models\EmployeeSalary;
use App\Models\Supplier;
use App\Support\Money;
use Carbon\Carbon;

/**
 * Cash-Flow Forecast (K4) — agle 30 din ka andaza, lekin har figure
 * ke peeche ASAL records hain (koi jhoota andaza nahi):
 *
 *  IN  — customer receivables jo abhi due hain (current_balance > 0)
 *  IN  — received cheques jin ki due date anay wale 30 din me hai
 *  OUT — supplier payables jo abhi outstanding hain (current_balance > 0)
 *  OUT — issued cheques jin ki due date anay wale 30 din me hai
 *  OUT — takhreeban salary: sab se recent payroll month ke kul
 *        net_salary, is mahine ke aakhir (payday) wale hafte me
 *
 * Rakmein 4 weekly buckets (7 din har) me taqseem hoti hain; jo cheez
 * abhi due hai (ya overdue hai) wo Week 1 me jati hai.
 * Tamam hisaab Money (bcmath) se — float sirf chart payload ke liye.
 */
class ForecastService
{
    private const WEEKS = 4;
    private const DAYS = 30;

    /**
     * @return array{
     *     chart: array{labels: array<int, string>, datasets: array<int, array{label: string, data: array<int, float>}>},
     *     total_in: string, total_out: string, net: string,
     *     breakdown: array{receivables: string, cheques_in: string, payables: string, cheques_out: string, salary: string, salary_month: ?string, payday: string}
     * }
     */
    public function build(?int $branchId): array
    {
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(self::DAYS - 1)->endOfDay();

        $in = array_fill(0, self::WEEKS, '0.00');
        $out = array_fill(0, self::WEEKS, '0.00');

        // ---------- IN: customer receivables (abhi due) ----------
        $receivables = $this->partyBalanceSum(Customer::query(), $branchId);
        $in[0] = Money::add($in[0], $receivables);

        // ---------- IN: received cheques (due date buckets) ----------
        $chequesInTotal = '0.00';
        $receivedCheques = Cheque::query()
            ->where('type', Cheque::TYPE_RECEIVED)
            ->whereIn('status', [Cheque::STATUS_RECEIVED, Cheque::STATUS_DEPOSITED])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $horizon)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['amount', 'due_date']);

        foreach ($receivedCheques as $cheque) {
            $bucket = $this->bucketFor($cheque->due_date, $today);
            $in[$bucket] = Money::add($in[$bucket], Money::n($cheque->amount));
            $chequesInTotal = Money::add($chequesInTotal, Money::n($cheque->amount));
        }

        // ---------- OUT: supplier payables (abhi outstanding) ----------
        $payables = $this->partyBalanceSum(Supplier::query(), $branchId);
        $out[0] = Money::add($out[0], $payables);

        // ---------- OUT: issued cheques (due date buckets) ----------
        $chequesOutTotal = '0.00';
        $issuedCheques = Cheque::query()
            ->where('type', Cheque::TYPE_ISSUED)
            ->whereIn('status', [Cheque::STATUS_ISSUED, Cheque::STATUS_PRESENTED])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $horizon)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['amount', 'due_date']);

        foreach ($issuedCheques as $cheque) {
            $bucket = $this->bucketFor($cheque->due_date, $today);
            $out[$bucket] = Money::add($out[$bucket], Money::n($cheque->amount));
            $chequesOutTotal = Money::add($chequesOutTotal, Money::n($cheque->amount));
        }

        // ---------- OUT: takhreeban salary (recent payroll month) ----------
        $salaryTotal = '0.00';
        $salaryMonthLabel = null;
        $payday = $today->copy()->endOfMonth();

        $latestMonth = EmployeeSalary::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->max('month');

        if ($latestMonth) {
            $salaryTotal = Money::n(
                EmployeeSalary::query()
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->where('month', $latestMonth)
                    ->sum('net_salary')
            );
            $salaryMonthLabel = Carbon::createFromFormat('Y-m', (string) $latestMonth)->format('M Y');
            $out[$this->bucketFor($payday, $today)] = Money::add($out[$this->bucketFor($payday, $today)], $salaryTotal);
        }

        // ---------- Totals + chart payload ----------
        $totalIn = '0.00';
        $totalOut = '0.00';
        foreach ($in as $amount) {
            $totalIn = Money::add($totalIn, $amount);
        }
        foreach ($out as $amount) {
            $totalOut = Money::add($totalOut, $amount);
        }

        $labels = [];
        for ($i = 0; $i < self::WEEKS; $i++) {
            $start = $today->copy()->addDays($i * 7);
            $end = $today->copy()->addDays(min($i * 7 + 6, self::DAYS - 1));
            $labels[] = __('finance.forecast.week') . ' ' . ($i + 1) . ' (' . $start->format('d M') . ' – ' . $end->format('d M') . ')';
        }

        return [
            'chart' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => __('finance.forecast.money_in'), 'data' => array_map(fn ($v) => (float) $v, $in)],
                    ['label' => __('finance.forecast.money_out'), 'data' => array_map(fn ($v) => (float) $v, $out)],
                ],
            ],
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net' => Money::subtract($totalIn, $totalOut),
            'breakdown' => [
                'receivables' => $receivables,
                'cheques_in' => $chequesInTotal,
                'payables' => $payables,
                'cheques_out' => $chequesOutTotal,
                'salary' => $salaryTotal,
                'salary_month' => $salaryMonthLabel,
                'payday' => $payday->format('d M Y'),
            ],
        ];
    }

    /**
     * Customers/Suppliers ke positive current_balance ka kul jama.
     * Branch filter ReportService wale pattern par: branch ki rows +
     * shared (branch_id NULL) rows.
     */
    private function partyBalanceSum($query, ?int $branchId): string
    {
        $rows = $query
            ->when($branchId, fn ($q) => $q->where(fn ($w) => $w->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->where('current_balance', '>', 0)
            ->get(['current_balance']);

        $sum = '0.00';
        foreach ($rows as $row) {
            $sum = Money::add($sum, Money::n($row->current_balance));
        }

        return $sum;
    }

    /** Due date ko weekly bucket (0–3) me badalna; overdue = bucket 0. */
    private function bucketFor(Carbon $date, Carbon $today): int
    {
        $diff = (int) $today->diffInDays($date->copy()->startOfDay(), false);

        if ($diff < 0) {
            return 0;
        }

        return min(self::WEEKS - 1, (int) intdiv($diff, 7));
    }
}
