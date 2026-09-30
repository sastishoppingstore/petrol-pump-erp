<?php

namespace App\Services\Shift;

use App\Mail\DailyClosingSummaryMail;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\CustomerPayment;
use App\Models\DailyClosing;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftCash;
use App\Models\SupplierPayment;
use App\Models\Tank;
use App\Models\TankReading;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class DailyClosingService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly AuditLogService $audit,
        private readonly AccountingService $accounting,
    ) {
    }

    /**
     * Inspect daily state and return 4-point verification checklist.
     */
    public function getChecklist(int $branchId, string $date): array
    {
        $d = Carbon::parse($date)->format('Y-m-d');

        // 1. Shifts check
        $shifts = Shift::query()
            ->where('branch_id', $branchId)
            ->whereDate('start_time', $d)
            ->get();

        $shiftsCount = $shifts->count();
        $openShiftsCount = $shifts->whereIn('status', [Shift::STATUS_OPEN, Shift::STATUS_PENDING_APPROVAL])->count();
        $shiftsVerified = $shiftsCount > 0 && $openShiftsCount === 0;

        // 2. Tank dips check
        $activeTanks = Tank::query()->where('branch_id', $branchId)->where('status', 'ACTIVE')->get();
        $tanksCount = $activeTanks->count();

        $readings = TankReading::query()
            ->where('branch_id', $branchId)
            ->whereDate('reading_time', $d)
            ->pluck('tank_id')
            ->unique();

        $dipsRecordedCount = $readings->count();
        $tanksVerified = $tanksCount > 0 && $dipsRecordedCount >= $tanksCount;

        // 3. Cash check
        // Sales totals for date
        $sales = Sale::query()
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $d)
            ->where('status', Sale::STATUS_COMPLETED)
            ->get();

        $cashSales = '0.00';
        $creditSales = '0.00';
        $cardSales = '0.00';
        $totalSales = '0.00';
        $totalLitres = '0.000';

        foreach ($sales as $sale) {
            $totalSales = Money::add($totalSales, $sale->total);
            $totalLitres = Quantity::add($totalLitres, $sale->total_litres);
            foreach ($sale->payments as $p) {
                $m = strtoupper($p->method);
                if ($m === 'CASH') {
                    $cashSales = Money::add($cashSales, $p->amount);
                } elseif ($m === 'CREDIT') {
                    $creditSales = Money::add($creditSales, $p->amount);
                } else {
                    $cardSales = Money::add($cardSales, $p->amount);
                }
            }
        }

        $customerReceipts = Money::round(Money::n(
            CustomerPayment::where('branch_id', $branchId)
                ->where('payment_date', $d)
                ->where('payment_method', CustomerPayment::METHOD_CASH)
                ->sum('amount')
        ));

        $expensesPaid = Money::round(Money::n(
            Expense::where('branch_id', $branchId)
                ->where('date', $d)
                ->where('status', Expense::STATUS_PAID)
                ->where('payment_method', Expense::METHOD_CASH)
                ->sum('amount')
        ));

        $bankDeposits = Money::round(Money::n(
            BankDeposit::where('branch_id', $branchId)
                ->whereDate('deposited_at', $d)
                ->where('status', BankDeposit::STATUS_COMPLETED)
                ->sum('amount')
        ));

        $openingCash = '0.00';
        $actualCashCounted = '0.00';

        foreach ($shifts as $s) {
            $openingCash = Money::add($openingCash, $s->opening_cash ?? '0.00');
            $actualCashCounted = Money::add($actualCashCounted, $s->actual_cash ?? '0.00');
        }

        // Expected Cash = Opening Float + Cash Sales + Customer Cash Receipts - Cash Expenses - Bank Deposits
        $expectedCash = Money::subtract(
            Money::subtract(
                Money::add(Money::add($openingCash, $cashSales), $customerReceipts),
                $expensesPaid
            ),
            $bankDeposits
        );

        $cashVariance = Money::subtract($actualCashCounted, $expectedCash);
        $cashVerified = $shiftsCount > 0 && ! Money::isZero($actualCashCounted);

        // 4. Bank deposits check
        $bankDepositsCount = BankDeposit::where('branch_id', $branchId)
            ->whereDate('deposited_at', $d)
            ->count();
        $bankDepositsVerified = true; // Can be 0 if no deposits today

        $existingClosing = DailyClosing::where('branch_id', $branchId)->where('closing_date', $d)->first();

        return [
            'closing_date' => $d,
            'existing_closing' => $existingClosing,
            'shifts' => [
                'total' => $shiftsCount,
                'open_or_pending' => $openShiftsCount,
                'is_verified' => $shiftsVerified,
            ],
            'tank_dips' => [
                'active_tanks' => $tanksCount,
                'dips_recorded' => $dipsRecordedCount,
                'is_verified' => $tanksVerified,
            ],
            'cash' => [
                'opening_cash' => $openingCash,
                'cash_sales' => $cashSales,
                'credit_sales' => $creditSales,
                'card_sales' => $cardSales,
                'customer_receipts' => $customerReceipts,
                'expenses_paid' => $expensesPaid,
                'bank_deposits' => $bankDeposits,
                'expected_cash' => $expectedCash,
                'actual_cash_counted' => $actualCashCounted,
                'cash_variance' => $cashVariance,
                'is_verified' => $cashVerified,
            ],
            'bank_deposits' => [
                'count' => $bankDepositsCount,
                'total_amount' => $bankDeposits,
                'is_verified' => $bankDepositsVerified,
            ],
            'sales_totals' => [
                'total_litres' => $totalLitres,
                'total_amount' => $totalSales,
            ],
            'ready_to_close' => ($shiftsVerified && $tanksVerified && $cashVerified),
        ];
    }

    /**
     * Perform Daily Closing, lock the date, and email the daily summary to the owner.
     */
    public function closeDay(
        int $branchId,
        string $date,
        User $actor,
        array $input = [],
        bool $forceOverride = false,
    ): DailyClosing {
        $d = Carbon::parse($date)->format('Y-m-d');

        // Check if already closed
        $existing = DailyClosing::where('branch_id', $branchId)->where('closing_date', $d)->first();
        if ($existing && $existing->isLocked() && ! $forceOverride) {
            throw ValidationException::withMessages([
                'closing_date' => "Day {$d} is already closed and locked.",
            ]);
        }

        $checklist = $this->getChecklist($branchId, $d);

        if (! $forceOverride) {
            if (! $checklist['shifts']['is_verified']) {
                throw ValidationException::withMessages([
                    'shifts' => "Cannot close day: {$checklist['shifts']['open_or_pending']} shift(s) are still OPEN or pending approval.",
                ]);
            }

            if (! $checklist['tank_dips']['is_verified']) {
                throw ValidationException::withMessages([
                    'tank_dips' => 'Cannot close day: Physical dips have not been recorded for all active underground tanks.',
                ]);
            }
        }

        return DB::transaction(function () use ($branchId, $d, $actor, $checklist, $input, $existing) {
            $year = Carbon::parse($d)->format('Y');
            $closingNumber = $existing?->closing_number ?? $this->sequences->next(
                branchId: $branchId,
                prefix: "DC-{$year}-",
                padding: 6
            );

            // Calculate tank dip variances
            $readings = TankReading::query()
                ->where('branch_id', $branchId)
                ->whereDate('reading_time', $d)
                ->get();

            $totalDipVariance = '0.000';
            $actualDipTotal = '0.000';
            $expectedDipTotal = '0.000';

            foreach ($readings as $r) {
                $actualDipTotal = Quantity::add($actualDipTotal, $r->physical_volume ?? '0.000');
                $expectedDipTotal = Quantity::add($expectedDipTotal, $r->book_volume ?? '0.000');
                $totalDipVariance = Quantity::add($totalDipVariance, $r->variance_volume ?? '0.000');
            }

            $actualCash = $input['actual_cash_counted'] ?? $checklist['cash']['actual_cash_counted'];
            $actualCash = Money::round(Money::n($actualCash));
            $cashVariance = Money::subtract($actualCash, $checklist['cash']['expected_cash']);

            $closingData = [
                'branch_id' => $branchId,
                'closing_date' => $d,
                'closing_number' => $closingNumber,
                'status' => DailyClosing::STATUS_LOCKED,
                'total_fuel_litres' => $checklist['sales_totals']['total_litres'],
                'total_fuel_sales' => $checklist['sales_totals']['total_amount'],
                'total_lube_sales' => '0.00',
                'total_sales_amount' => $checklist['sales_totals']['total_amount'],
                'total_cash_sales' => $checklist['cash']['cash_sales'],
                'total_credit_sales' => $checklist['cash']['credit_sales'],
                'total_card_sales' => $checklist['cash']['card_sales'],
                'total_customer_receipts' => $checklist['cash']['customer_receipts'],
                'total_expenses' => $checklist['cash']['expenses_paid'],
                'total_bank_deposits' => $checklist['cash']['bank_deposits'],
                'total_supplier_payments' => '0.00',
                'opening_cash' => $checklist['cash']['opening_cash'],
                'expected_cash' => $checklist['cash']['expected_cash'],
                'actual_cash_counted' => $actualCash,
                'cash_variance' => $cashVariance,
                'total_opening_stock' => $expectedDipTotal,
                'total_purchases_stock' => '0.000',
                'total_sales_stock' => $checklist['sales_totals']['total_litres'],
                'expected_dip_stock' => $expectedDipTotal,
                'actual_dip_stock' => $actualDipTotal,
                'dip_variance_litres' => $totalDipVariance,
                'shifts_count' => $checklist['shifts']['total'],
                'shifts_verified' => true,
                'tank_dips_verified' => true,
                'cash_verified' => true,
                'bank_deposits_verified' => true,
                'closed_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'notes' => $input['notes'] ?? null,
            ];

            $closing = DailyClosing::updateOrCreate(
                ['branch_id' => $branchId, 'closing_date' => $d],
                $closingData
            );

            // Lock date in general ledger
            $this->accounting->lockPeriod($branchId, $d, $actor->id, "Locked by Daily Closing {$closingNumber}");

            // Send daily summary email to owner
            $ownerEmail = config('mail.from.address') ?: 'owner@meharfilling.com';
            try {
                Mail::to($ownerEmail)->send(new DailyClosingSummaryMail($closing, $checklist));
                $closing->update([
                    'email_sent_at' => now(),
                    'email_recipient' => $ownerEmail,
                ]);
            } catch (Throwable $e) {
                Log::warning("Daily closing email could not be sent: {$e->getMessage()}");
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'daily_closing_complete',
                module: 'closing',
                referenceType: DailyClosing::class,
                referenceId: $closing->id,
                newData: [
                    'closing_number' => $closingNumber,
                    'closing_date' => $d,
                    'total_sales' => $closing->total_sales_amount,
                    'cash_variance' => $cashVariance,
                ]
            );

            return $closing;
        });
    }

    /**
     * Unlock a daily closing for adjustments (requires manager permission).
     */
    public function unlockDay(DailyClosing $closing, User $actor, string $reason): DailyClosing
    {
        if (! $actor->hasPermission(PermissionList::CLOSING_APPROVE)) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to unlock a closed day.',
            ]);
        }

        $closing->update([
            'status' => DailyClosing::STATUS_PENDING,
            'notes' => ($closing->notes ? $closing->notes . "\n" : '') . "Unlocked by {$actor->name} on " . now()->toDateTimeString() . ": {$reason}",
        ]);

        $this->audit->record(
            userId: $actor->id,
            action: 'daily_closing_unlock',
            module: 'closing',
            referenceType: DailyClosing::class,
            referenceId: $closing->id,
            newData: ['reason' => $reason]
        );

        return $closing;
    }
}
