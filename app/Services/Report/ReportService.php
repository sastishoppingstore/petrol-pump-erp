<?php

namespace App\Services\Report;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\DailyClosing;
use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\Tank;
use App\Models\TankReading;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Support\Decimal;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use App\Support\Quantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {
    }

    /**
     * Resolve date range filter from preset or custom inputs.
     *
     * @return array{from: string, to: string, preset: string, label: string}
     */
    public function resolveDateRange(
        string $preset = 'today',
        ?string $customFrom = null,
        ?string $customTo = null,
        ?int $shiftId = null,
    ): array {
        $now = now();
        $preset = strtolower(trim($preset));

        if ($preset === 'shift' && $shiftId) {
            $shift = Shift::find($shiftId);
            if ($shift) {
                $from = $shift->start_time->format('Y-m-d H:i:s');
                $to = $shift->end_time ? $shift->end_time->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
                return [
                    'from' => $from,
                    'to' => $to,
                    'preset' => 'shift',
                    'label' => "Shift #{$shift->shift_number} ({$shift->start_time->format('d M H:i')})",
                ];
            }
        }

        switch ($preset) {
            case 'yesterday':
                $from = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                $to = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                $label = 'Yesterday (' . $now->copy()->subDay()->format('d M Y') . ')';
                break;
            case 'week':
                $from = $now->copy()->subDays(6)->startOfDay()->format('Y-m-d');
                $to = $now->format('Y-m-d');
                $label = 'Last 7 Days';
                break;
            case '15days':
                $from = $now->copy()->subDays(14)->startOfDay()->format('Y-m-d');
                $to = $now->format('Y-m-d');
                $label = 'Last 15 Days';
                break;
            case 'month':
                $from = $now->copy()->startOfMonth()->format('Y-m-d');
                $to = $now->format('Y-m-d');
                $label = 'This Month (' . $now->format('F Y') . ')';
                break;
            case 'year':
                $from = $now->copy()->startOfYear()->format('Y-m-d');
                $to = $now->format('Y-m-d');
                $label = 'This Year (' . $now->format('Y') . ')';
                break;
            case 'custom':
                $from = $customFrom ? Carbon::parse($customFrom)->format('Y-m-d') : $now->format('Y-m-d');
                $to = $customTo ? Carbon::parse($customTo)->format('Y-m-d') : $now->format('Y-m-d');
                $label = "Custom: {$from} to {$to}";
                break;
            case 'today':
            default:
                $preset = 'today';
                $from = $now->format('Y-m-d');
                $to = $now->format('Y-m-d');
                $label = 'Today (' . $now->format('d M Y') . ')';
                break;
        }

        return [
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'label' => $label,
        ];
    }

    // =========================================================================
    // 1. SALES REPORTS (5)
    // =========================================================================

    /**
     * Report 1: Sales Summary & Detail.
     */
    public function salesSummary(int $branchId, string $from, string $to): array
    {
        $sales = Sale::query()
            ->where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', Sale::STATUS_COMPLETED)
            ->with(['payments', 'user', 'customer'])
            ->orderByDesc('id')
            ->get();

        $totalSales = '0.00';
        $totalLitres = '0.000';
        $cashTotal = '0.00';
        $creditTotal = '0.00';
        $cardTotal = '0.00';

        foreach ($sales as $sale) {
            $totalSales = Money::add($totalSales, $sale->total);
            $totalLitres = Quantity::add($totalLitres, $sale->total_litres);
            foreach ($sale->payments as $p) {
                $m = strtoupper($p->method);
                if ($m === 'CASH') {
                    $cashTotal = Money::add($cashTotal, $p->amount);
                } elseif ($m === 'CREDIT') {
                    $creditTotal = Money::add($creditTotal, $p->amount);
                } else {
                    $cardTotal = Money::add($cardTotal, $p->amount);
                }
            }
        }

        return [
            'sales' => $sales,
            'count' => $sales->count(),
            'total_sales' => $totalSales,
            'total_litres' => $totalLitres,
            'cash_total' => $cashTotal,
            'credit_total' => $creditTotal,
            'card_total' => $cardTotal,
        ];
    }

    /**
     * Report 2: Fuel Sales Report (by product).
     */
    public function fuelSales(int $branchId, string $from, string $to): array
    {
        $items = SaleItem::query()
            ->where('sale_items.branch_id', $branchId)
            ->whereHas('sale', fn ($q) => $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->where('status', Sale::STATUS_COMPLETED))
            ->join('fuel_products', 'fuel_products.id', '=', 'sale_items.fuel_product_id')
            ->select(
                'fuel_products.id',
                'fuel_products.name',
                'fuel_products.code',
                DB::raw('SUM(sale_items.litres) as total_litres'),
                DB::raw('SUM(sale_items.amount) as total_amount'),
                DB::raw('SUM(sale_items.cost_amount) as total_cost')
            )
            ->groupBy('fuel_products.id', 'fuel_products.name', 'fuel_products.code')
            ->get();

        $totalLitres = '0.000';
        $totalAmount = '0.00';
        $totalCost = '0.00';

        $products = [];
        foreach ($items as $item) {
            $lit = Quantity::round(Quantity::n($item->total_litres));
            $amt = Money::round(Money::n($item->total_amount));
            $cost = Money::round(Money::n($item->total_cost));
            $avgRate = Quantity::isZero($lit) ? '0.00' : Money::round(Decimal::divide($amt, $lit, 2));

            $totalLitres = Quantity::add($totalLitres, $lit);
            $totalAmount = Money::add($totalAmount, $amt);
            $totalCost = Money::add($totalCost, $cost);

            $products[] = [
                'name' => $item->name,
                'code' => $item->code,
                'litres' => $lit,
                'amount' => $amt,
                'average_rate' => $avgRate,
                'cost' => $cost,
                'margin' => Money::subtract($amt, $cost),
            ];
        }

        return [
            'products' => $products,
            'total_litres' => $totalLitres,
            'total_amount' => $totalAmount,
            'total_cost' => $totalCost,
            'gross_margin' => Money::subtract($totalAmount, $totalCost),
        ];
    }

    /**
     * Report 3: Nozzle Sales Report.
     */
    public function nozzleSales(int $branchId, string $from, string $to): array
    {
        $nozzles = Nozzle::where('branch_id', $branchId)->with(['dispenser', 'fuelProduct'])->get();

        $rows = [];
        $grandLitres = '0.000';
        $grandAmount = '0.00';

        foreach ($nozzles as $nozzle) {
            $items = SaleItem::query()
                ->where('nozzle_id', $nozzle->id)
                ->whereHas('sale', fn ($q) => $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->where('status', Sale::STATUS_COMPLETED))
                ->select(
                    DB::raw('MIN(meter_start) as opening_meter'),
                    DB::raw('MAX(meter_end) as closing_meter'),
                    DB::raw('SUM(litres) as total_litres'),
                    DB::raw('SUM(amount) as total_amount')
                )
                ->first();

            $litres = Quantity::round(Quantity::n($items?->total_litres));
            $amount = Money::round(Money::n($items?->total_amount));

            $grandLitres = Quantity::add($grandLitres, $litres);
            $grandAmount = Money::add($grandAmount, $amount);

            $rows[] = [
                'nozzle' => $nozzle,
                'dispenser' => $nozzle->dispenser?->name ?? "Dispenser #{$nozzle->dispenser_id}",
                'product' => $nozzle->fuelProduct?->name ?? 'Fuel',
                'opening_meter' => $items?->opening_meter ?? $nozzle->current_meter,
                'closing_meter' => $items?->closing_meter ?? $nozzle->current_meter,
                'litres' => $litres,
                'amount' => $amount,
            ];
        }

        return [
            'nozzles' => $rows,
            'grand_litres' => $grandLitres,
            'grand_amount' => $grandAmount,
        ];
    }

    /**
     * Report 4: Meter Reading Report.
     */
    public function meterReadings(int $branchId, string $from, string $to): array
    {
        $readings = MeterReading::query()
            ->where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(reading_time)'), [$from, $to])
            ->with(['nozzle.dispenser', 'shift', 'user'])
            ->orderByDesc('reading_time')
            ->get();

        return [
            'readings' => $readings,
            'count' => $readings->count(),
        ];
    }

    /**
     * Report 5: Cashier Performance Report.
     */
    public function cashierPerformance(int $branchId, string $from, string $to): array
    {
        $cashiers = User::query()
            ->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId))
            ->get();

        $rows = [];
        foreach ($cashiers as $user) {
            $shifts = Shift::query()
                ->where('branch_id', $branchId)
                ->where('employee_id', $user->id)
                ->whereBetween(DB::raw('DATE(start_time)'), [$from, $to])
                ->get();

            if ($shifts->isEmpty()) {
                continue;
            }

            $sales = Sale::query()
                ->where('branch_id', $branchId)
                ->where('user_id', $user->id)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->where('status', Sale::STATUS_COMPLETED)
                ->get();

            $totalLitres = '0.000';
            $totalSales = '0.00';
            foreach ($sales as $s) {
                $totalLitres = Quantity::add($totalLitres, $s->total_litres);
                $totalSales = Money::add($totalSales, $s->total);
            }

            $totalCashHanded = '0.00';
            $totalVariance = '0.00';
            foreach ($shifts as $sh) {
                $totalCashHanded = Money::add($totalCashHanded, $sh->actual_cash ?? '0.00');
                $totalVariance = Money::add($totalVariance, $sh->cash_difference ?? '0.00');
            }

            $rows[] = [
                'user' => $user,
                'shifts_count' => $shifts->count(),
                'sales_count' => $sales->count(),
                'total_litres' => $totalLitres,
                'total_sales' => $totalSales,
                'cash_handed' => $totalCashHanded,
                'cash_variance' => $totalVariance,
            ];
        }

        return ['cashiers' => $rows];
    }

    // =========================================================================
    // 2. CASH & BANK REPORTS (4)
    // =========================================================================

    /**
     * Report 6: Cash Book.
     */
    public function cashBook(int $branchId, string $from, string $to): array
    {
        $cashAccount = Account::where('code', Account::CODE_CASH_IN_HAND)->first();
        if (! $cashAccount) {
            return ['opening_balance' => '0.00', 'lines' => [], 'total_in' => '0.00', 'total_out' => '0.00', 'closing_balance' => '0.00'];
        }

        $gl = $this->accounting->generalLedger($cashAccount->id, $branchId, $from, $to);

        return [
            'account' => $cashAccount,
            'opening_balance' => $gl['opening_balance'],
            'lines' => $gl['lines'],
            'total_in' => $gl['total_debit'],
            'total_out' => $gl['total_credit'],
            'closing_balance' => $gl['closing_balance'],
        ];
    }

    /**
     * Report 7: Bank Book.
     */
    public function bankBook(int $branchId, ?int $bankAccountId, string $from, string $to): array
    {
        $accounts = BankAccount::where('branch_id', $branchId)->with('bank')->get();

        $selectedAccount = $bankAccountId ? BankAccount::find($bankAccountId) : $accounts->first();

        $deposits = [];
        $totalDeposits = '0.00';

        if ($selectedAccount) {
            $deposits = BankDeposit::query()
                ->where('bank_account_id', $selectedAccount->id)
                ->whereBetween(DB::raw('DATE(deposited_at)'), [$from, $to])
                ->where('status', BankDeposit::STATUS_COMPLETED)
                ->orderBy('deposited_at')
                ->get();

            foreach ($deposits as $d) {
                $totalDeposits = Money::add($totalDeposits, $d->amount);
            }
        }

        return [
            'accounts' => $accounts,
            'selected_account' => $selectedAccount,
            'deposits' => $deposits,
            'total_deposits' => $totalDeposits,
        ];
    }

    /**
     * Report 8: Cheque Register.
     */
    public function chequeRegister(int $branchId, string $from, string $to): array
    {
        $customerCheques = CustomerPayment::query()
            ->where('branch_id', $branchId)
            ->where('payment_method', CustomerPayment::METHOD_CHEQUE)
            ->whereBetween('payment_date', [$from, $to])
            ->with(['customer', 'bankAccount'])
            ->get();

        $supplierCheques = SupplierPayment::query()
            ->where('branch_id', $branchId)
            ->where('payment_method', SupplierPayment::METHOD_CHEQUE)
            ->whereBetween('payment_date', [$from, $to])
            ->with(['supplier', 'bankAccount'])
            ->get();

        return [
            'customer_cheques' => $customerCheques,
            'supplier_cheques' => $supplierCheques,
        ];
    }

    /**
     * Report 9: Daily Closing Report history.
     */
    public function dailyClosingHistory(int $branchId, string $from, string $to): array
    {
        $closings = DailyClosing::query()
            ->where('branch_id', $branchId)
            ->whereBetween('closing_date', [$from, $to])
            ->with(['closedByUser', 'approvedByUser'])
            ->orderByDesc('closing_date')
            ->get();

        return ['closings' => $closings];
    }

    // =========================================================================
    // 3. UDHAAR / CUSTOMER REPORTS (3)
    // =========================================================================

    /**
     * Report 10: Customer Ledger / Statement.
     */
    public function customerLedger(int $branchId, int $customerId, string $from, string $to): array
    {
        $customer = Customer::findOrFail($customerId);

        // Calculate opening balance before $from
        $preDebit = Money::round(Money::n(
            CustomerLedger::where('branch_id', $branchId)
                ->where('customer_id', $customerId)
                ->where('date', '<', $from)
                ->sum('debit')
        ));
        $preCredit = Money::round(Money::n(
            CustomerLedger::where('branch_id', $branchId)
                ->where('customer_id', $customerId)
                ->where('date', '<', $from)
                ->sum('credit')
        ));

        $opening = Money::add(
            Money::subtract($preDebit, $preCredit),
            Money::n($customer->opening_balance)
        );

        $entries = CustomerLedger::query()
            ->where('branch_id', $branchId)
            ->where('customer_id', $customerId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = $opening;
        $lines = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($entries as $e) {
            $deb = Money::round($e->debit);
            $cred = Money::round($e->credit);
            $running = Money::subtract(Money::add($running, $deb), $cred);

            $totalDebit = Money::add($totalDebit, $deb);
            $totalCredit = Money::add($totalCredit, $cred);

            $lines[] = [
                'date' => $e->date->format('Y-m-d'),
                'description' => $e->description,
                'debit' => $deb,
                'credit' => $cred,
                'balance' => $running,
            ];
        }

        return [
            'customer' => $customer,
            'opening_balance' => $opening,
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $running,
        ];
    }

    /**
     * Report 11: Customer Outstanding Report.
     */
    public function customerOutstanding(int $branchId): array
    {
        $customers = Customer::query()
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->orderBy('name')
            ->get();

        $rows = [];
        $totalOutstanding = '0.00';

        foreach ($customers as $c) {
            $bal = Money::round($c->current_balance);
            if (! Money::isZero($bal)) {
                $totalOutstanding = Money::add($totalOutstanding, $bal);
                $rows[] = [
                    'customer' => $c,
                    'credit_limit' => Money::round($c->credit_limit),
                    'balance' => $bal,
                    'available_credit' => Money::subtract(Money::round($c->credit_limit), $bal),
                ];
            }
        }

        return [
            'customers' => $rows,
            'total_outstanding' => $totalOutstanding,
        ];
    }

    /**
     * Report 12: Customer Ageing Report (0-30, 31-60, 61-90, 90+ days).
     */
    public function customerAgeing(int $branchId): array
    {
        $customers = Customer::query()
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->get();

        $rows = [];
        $bucketTotals = ['current' => '0.00', '30_days' => '0.00', '60_days' => '0.00', '90_plus' => '0.00', 'total' => '0.00'];

        $today = now();

        foreach ($customers as $c) {
            $bal = Money::round($c->current_balance);
            if (Money::compare($bal, '0.00') <= 0) {
                continue;
            }

            // Estimate ageing from oldest unpaid ledger debit lines
            $debits = CustomerLedger::query()
                ->where('branch_id', $branchId)
                ->where('customer_id', $c->id)
                ->where('debit', '>', 0)
                ->orderBy('date')
                ->get();

            $c_current = '0.00';
            $c_30 = '0.00';
            $c_60 = '0.00';
            $c_90 = '0.00';

            $remaining = $bal;
            foreach ($debits as $d) {
                if (Money::compare($remaining, '0.00') <= 0) {
                    break;
                }
                $take = Money::compare($remaining, $d->debit) >= 0 ? $d->debit : $remaining;
                $days = $today->diffInDays($d->date);

                if ($days <= 30) {
                    $c_current = Money::add($c_current, $take);
                } elseif ($days <= 60) {
                    $c_30 = Money::add($c_30, $take);
                } elseif ($days <= 90) {
                    $c_60 = Money::add($c_60, $take);
                } else {
                    $c_90 = Money::add($c_90, $take);
                }
                $remaining = Money::subtract($remaining, $take);
            }

            if (! Money::isZero($remaining)) {
                $c_90 = Money::add($c_90, $remaining);
            }

            $rows[] = [
                'customer' => $c,
                'current' => $c_current,
                'days_30' => $c_30,
                'days_60' => $c_60,
                'days_90_plus' => $c_90,
                'total' => $bal,
            ];

            $bucketTotals['current'] = Money::add($bucketTotals['current'], $c_current);
            $bucketTotals['30_days'] = Money::add($bucketTotals['30_days'], $c_30);
            $bucketTotals['60_days'] = Money::add($bucketTotals['60_days'], $c_60);
            $bucketTotals['90_plus'] = Money::add($bucketTotals['90_plus'], $c_90);
            $bucketTotals['total'] = Money::add($bucketTotals['total'], $bal);
        }

        return [
            'rows' => $rows,
            'totals' => $bucketTotals,
        ];
    }

    // =========================================================================
    // 4. SUPPLIER REPORTS (3)
    // =========================================================================

    /**
     * Report 13: Supplier Ledger / Statement.
     */
    public function supplierLedger(int $branchId, int $supplierId, string $from, string $to): array
    {
        $supplier = Supplier::findOrFail($supplierId);

        $preDebit = Money::round(Money::n(
            SupplierLedger::where('branch_id', $branchId)
                ->where('supplier_id', $supplierId)
                ->where('date', '<', $from)
                ->sum('debit')
        ));
        $preCredit = Money::round(Money::n(
            SupplierLedger::where('branch_id', $branchId)
                ->where('supplier_id', $supplierId)
                ->where('date', '<', $from)
                ->sum('credit')
        ));

        // Payable balance = Credit - Debit
        $opening = Money::add(
            Money::subtract($preCredit, $preDebit),
            Money::n($supplier->opening_balance)
        );

        $entries = SupplierLedger::query()
            ->where('branch_id', $branchId)
            ->where('supplier_id', $supplierId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = $opening;
        $lines = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($entries as $e) {
            $deb = Money::round($e->debit);
            $cred = Money::round($e->credit);
            $running = Money::subtract(Money::add($running, $cred), $deb);

            $totalDebit = Money::add($totalDebit, $deb);
            $totalCredit = Money::add($totalCredit, $cred);

            $lines[] = [
                'date' => $e->date->format('Y-m-d'),
                'description' => $e->description,
                'debit' => $deb,
                'credit' => $cred,
                'balance' => $running,
            ];
        }

        return [
            'supplier' => $supplier,
            'opening_balance' => $opening,
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $running,
        ];
    }

    /**
     * Report 14: Supplier Payable Report.
     */
    public function supplierPayable(int $branchId): array
    {
        $suppliers = Supplier::query()
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->orderBy('name')
            ->get();

        $rows = [];
        $totalPayable = '0.00';

        foreach ($suppliers as $s) {
            $bal = Money::round($s->current_balance);
            if (! Money::isZero($bal)) {
                $totalPayable = Money::add($totalPayable, $bal);
                $rows[] = [
                    'supplier' => $s,
                    'balance' => $bal,
                ];
            }
        }

        return [
            'suppliers' => $rows,
            'total_payable' => $totalPayable,
        ];
    }

    /**
     * Report 15: Purchase Report.
     */
    public function purchases(int $branchId, string $from, string $to): array
    {
        $purchases = Purchase::query()
            ->where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$from, $to])
            ->with(['supplier', 'fuelProduct', 'tank'])
            ->orderByDesc('purchase_date')
            ->get();

        $totalLitres = '0.000';
        $totalAmount = '0.00';

        foreach ($purchases as $p) {
            $totalLitres = Quantity::add($totalLitres, $p->volume_received);
            $totalAmount = Money::add($totalAmount, $p->total_amount);
        }

        return [
            'purchases' => $purchases,
            'total_litres' => $totalLitres,
            'total_amount' => $totalAmount,
        ];
    }

    // =========================================================================
    // 5. STOCK REPORTS (2)
    // =========================================================================

    /**
     * Report 16: Tank Stock & Variance Report.
     */
    public function tankStockVariance(int $branchId, string $from, string $to): array
    {
        $tanks = Tank::where('branch_id', $branchId)->with('fuelProduct')->get();

        $rows = [];
        foreach ($tanks as $tank) {
            $readings = TankReading::query()
                ->where('tank_id', $tank->id)
                ->whereBetween(DB::raw('DATE(reading_time)'), [$from, $to])
                ->orderBy('reading_time')
                ->get();

            $openingReading = $readings->first();
            $closingReading = $readings->last();

            $purchases = Purchase::query()
                ->where('tank_id', $tank->id)
                ->whereBetween('purchase_date', [$from, $to])
                ->where('status', Purchase::STATUS_APPROVED)
                ->sum('volume_received');

            $sales = SaleItem::query()
                ->where('tank_id', $tank->id)
                ->whereHas('sale', fn ($q) => $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->where('status', Sale::STATUS_COMPLETED))
                ->sum('litres');

            $openingLitres = Quantity::round(Quantity::n($openingReading?->physical_volume ?? $tank->current_stock));
            $receiptsLitres = Quantity::round(Quantity::n($purchases));
            $salesLitres = Quantity::round(Quantity::n($sales));
            $expected = Quantity::subtract(Quantity::add($openingLitres, $receiptsLitres), $salesLitres);
            $actual = Quantity::round(Quantity::n($closingReading?->physical_volume ?? $tank->current_stock));
            $variance = Quantity::subtract($actual, $expected);

            $rows[] = [
                'tank' => $tank,
                'product' => $tank->fuelProduct?->name ?? 'Fuel',
                'opening_stock' => $openingLitres,
                'receipts' => $receiptsLitres,
                'sales' => $salesLitres,
                'expected_stock' => $expected,
                'physical_stock' => $actual,
                'variance' => $variance,
            ];
        }

        return ['tanks' => $rows];
    }

    /**
     * Report 17: Price-Change Gain/Loss Report.
     */
    public function priceChangeGainLoss(int $branchId, string $from, string $to): array
    {
        $priceChanges = FuelPrice::query()
            ->where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(effective_from)'), [$from, $to])
            ->with('fuelProduct')
            ->orderByDesc('effective_from')
            ->get();

        $rows = [];
        $totalGainLoss = '0.00';

        foreach ($priceChanges as $pc) {
            $fuelId = $pc->fuel_product_id;
            $prevPrice = FuelPrice::where('branch_id', $branchId)
                ->where('fuel_product_id', $fuelId)
                ->where('effective_from', '<', $pc->effective_from)
                ->orderByDesc('effective_from')
                ->first();

            $oldRate = Money::round(Money::n($prevPrice?->price_per_litre));
            $newRate = Money::round(Money::n($pc->price_per_litre));
            $rateDiff = Money::subtract($newRate, $oldRate);

            // Total fuel stock in tanks at effective_from
            $tankStock = Tank::where('branch_id', $branchId)
                ->where('fuel_product_id', $fuelId)
                ->sum('current_stock');
            $stockLitres = Quantity::round(Quantity::n($tankStock));

            $gainLoss = Money::amountForLitres($stockLitres, $rateDiff);
            $totalGainLoss = Money::add($totalGainLoss, $gainLoss);

            $rows[] = [
                'date' => $pc->effective_from->format('Y-m-d H:i'),
                'product' => $pc->fuelProduct?->name,
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'rate_diff' => $rateDiff,
                'stock_litres' => $stockLitres,
                'gain_loss' => $gainLoss,
            ];
        }

        return [
            'changes' => $rows,
            'total_gain_loss' => $totalGainLoss,
        ];
    }

    // =========================================================================
    // 6. FINANCIAL & HR REPORTS (5)
    // =========================================================================

    /**
     * Report 18: Profit & Loss Statement.
     */
    public function profitAndLoss(int $branchId, string $from, string $to): array
    {
        return $this->accounting->profitAndLoss($branchId, $from, $to);
    }

    /**
     * Report 19: Balance Sheet.
     */
    public function balanceSheet(int $branchId, string $asOfDate): array
    {
        return $this->accounting->balanceSheet($branchId, $asOfDate);
    }

    /**
     * Report 20: Trial Balance.
     */
    public function trialBalance(int $branchId, string $asOfDate): array
    {
        return $this->accounting->trialBalance($branchId, $asOfDate);
    }

    /**
     * Report 21: Operating Expenses Report.
     */
    public function expenses(int $branchId, string $from, string $to): array
    {
        $expenses = Expense::query()
            ->where('branch_id', $branchId)
            ->whereBetween('date', [$from, $to])
            ->where('status', Expense::STATUS_PAID)
            ->with(['category', 'bankAccount', 'creator'])
            ->orderBy('date')
            ->get();

        $byCategory = [];
        $totalAmount = '0.00';

        foreach ($expenses as $e) {
            $catName = $e->category?->name ?? 'General';
            $byCategory[$catName] = Money::add($byCategory[$catName] ?? '0.00', $e->amount);
            $totalAmount = Money::add($totalAmount, $e->amount);
        }

        return [
            'expenses' => $expenses,
            'by_category' => $byCategory,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Report 22: Staff & Salary Report.
     */
    public function staffSalary(int $branchId, string $from, string $to): array
    {
        $employees = Employee::where('branch_id', $branchId)->with('user')->get();

        $month = Carbon::parse($from)->format('Y-m');
        $salaries = EmployeeSalary::where('branch_id', $branchId)
            ->where('month', $month)
            ->with('employee')
            ->get();

        $totalSalaries = '0.00';
        foreach ($salaries as $s) {
            $totalSalaries = Money::add($totalSalaries, $s->paid_amount);
        }

        return [
            'employees' => $employees,
            'month' => $month,
            'salaries' => $salaries,
            'total_salaries' => $totalSalaries,
        ];
    }
}
