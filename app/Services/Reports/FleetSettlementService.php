<?php

namespace App\Services\Reports;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Fleet Card Settlement (K4).
 *
 * OMC fleet cards (PSO waghera) se hui sales cashier `sale_payments`
 * me method=FLEET_CARD ke tor par record karta hai, aur `reference`
 * me OMC/card programme ka naam likhta hai. Company (OMC) baad me
 * pump ko bulk settlement bhejti hai — is service se wohi hisaab
 * milta hai: period ke andar kul fleet sales, reference (OMC) ke
 * hisaab se groups, aur settled vs pending split.
 *
 * Settlement sirf `settled_at` timestamp lagata hai — amount ya
 * payment row kabhi change/delete nahi hoti (append-only usool).
 */
class FleetSettlementService
{
    /** Reference khali ho to group key. */
    public const NO_REFERENCE = '__none__';

    private function baseQuery(int $branchId, Carbon $from, Carbon $to): Builder
    {
        return SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sale_payments.method', SalePayment::METHOD_FLEET_CARD)
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->where('sales.branch_id', $branchId)
            ->whereBetween('sales.sale_date', [$from, $to]);
    }

    /**
     * Period ki fleet settlement report: totals, reference-wise
     * groups aur detail rows (max 300).
     *
     * @return array{
     *     totals: array{count: int, total: string, settled: string, pending: string, settled_count: int, pending_count: int},
     *     groups: array<int, array{key: string, label: string, count: int, total: string, settled: string, pending: string}>,
     *     payments: \Illuminate\Support\Collection<int, SalePayment>
     * }
     */
    public function report(int $branchId, Carbon $from, Carbon $to): array
    {
        $aggregate = $this->baseQuery($branchId, $from, $to)
            ->selectRaw('COUNT(*) AS cnt')
            ->selectRaw('COALESCE(SUM(sale_payments.amount), 0) AS total_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN sale_payments.settled_at IS NOT NULL THEN sale_payments.amount ELSE 0 END), 0) AS settled_amount')
            ->selectRaw('SUM(CASE WHEN sale_payments.settled_at IS NOT NULL THEN 1 ELSE 0 END) AS settled_cnt')
            ->first();

        $total = Money::n($aggregate->total_amount ?? 0);
        $settled = Money::n($aggregate->settled_amount ?? 0);

        $totals = [
            'count' => (int) ($aggregate->cnt ?? 0),
            'total' => $total,
            'settled' => $settled,
            'pending' => Money::subtract($total, $settled),
            'settled_count' => (int) ($aggregate->settled_cnt ?? 0),
            'pending_count' => (int) ($aggregate->cnt ?? 0) - (int) ($aggregate->settled_cnt ?? 0),
        ];

        $groupRows = $this->baseQuery($branchId, $from, $to)
            ->selectRaw("COALESCE(NULLIF(TRIM(sale_payments.reference), ''), '" . self::NO_REFERENCE . "') AS ref_key")
            ->selectRaw('COUNT(*) AS cnt')
            ->selectRaw('COALESCE(SUM(sale_payments.amount), 0) AS total_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN sale_payments.settled_at IS NOT NULL THEN sale_payments.amount ELSE 0 END), 0) AS settled_amount')
            ->groupBy('ref_key')
            ->orderByDesc('total_amount')
            ->get();

        $groups = $groupRows->map(function ($row) {
            $groupTotal = Money::n($row->total_amount ?? 0);
            $groupSettled = Money::n($row->settled_amount ?? 0);

            return [
                'key' => (string) $row->ref_key,
                'label' => $row->ref_key === self::NO_REFERENCE
                    ? '— ' . __('finance.fleet.no_reference') . ' —'
                    : (string) $row->ref_key,
                'count' => (int) $row->cnt,
                'total' => $groupTotal,
                'settled' => $groupSettled,
                'pending' => Money::subtract($groupTotal, $groupSettled),
            ];
        })->all();

        $payments = SalePayment::query()
            ->with(['sale.customer', 'sale.vehicle'])
            ->where('method', SalePayment::METHOD_FLEET_CARD)
            ->whereHas('sale', function (Builder $q) use ($branchId, $from, $to) {
                $q->where('status', Sale::STATUS_COMPLETED)
                    ->where('branch_id', $branchId)
                    ->whereBetween('sale_date', [$from, $to]);
            })
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->sortByDesc(fn (SalePayment $p) => optional($p->sale)->sale_date)
            ->values();

        return [
            'totals' => $totals,
            'groups' => $groups,
            'payments' => $payments,
        ];
    }

    /**
     * Ek reference (OMC) group ki tamam pending payments ko settled
     * mark karna — sirf diye gaye period/branch ke andar. settled_at
     * pehle se lagi hui rows dobara touch nahi hotin.
     *
     * @return int kitni payments settle huin
     */
    public function markSettled(int $branchId, string $referenceKey, Carbon $from, Carbon $to): int
    {
        $query = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sale_payments.method', SalePayment::METHOD_FLEET_CARD)
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->where('sales.branch_id', $branchId)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereNull('sale_payments.settled_at');

        if ($referenceKey === self::NO_REFERENCE) {
            $query->where(function ($q) {
                $q->whereNull('sale_payments.reference')
                    ->orWhereRaw("TRIM(sale_payments.reference) = ''");
            });
        } else {
            $query->where('sale_payments.reference', $referenceKey);
        }

        return $query->update([
            'sale_payments.settled_at' => now(),
            'sale_payments.updated_at' => now(),
        ]);
    }
}
