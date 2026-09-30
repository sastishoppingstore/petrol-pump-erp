<?php

namespace App\Services\Shift;

use App\Models\Branch;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Shift;
use App\Models\ShiftNozzle;
use App\Models\Tank;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Opening and tracking a shift (spec section 3).
 *
 * Two rules are enforced here, in the service, not only in the form:
 *   - an employee may hold only one OPEN shift at a time
 *   - a nozzle may be assigned to only one OPEN shift at a time
 */
class ShiftService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Open a shift.
     *
     * @param  array<int, int>  $nozzleIds
     * @param  array<int, string>  $openingMeters  keyed by nozzle id
     */
    public function open(
        User $employee,
        Branch $branch,
        string $openingCash,
        array $nozzleIds = [],
        array $openingMeters = [],
        ?string $notes = null,
    ): Shift {
        if (! $employee->canAccessBranch($branch->id)) {
            throw ValidationException::withMessages([
                'branch_id' => 'You are not assigned to that branch.',
            ]);
        }

        $nozzleIds = array_values(array_unique(array_map('intval', $nozzleIds)));

        $this->assertNoOpenShift($employee);
        $this->assertNozzlesAvailable($nozzleIds);

        try {
            return DB::transaction(function () use ($employee, $branch, $openingCash, $nozzleIds, $openingMeters, $notes) {
                $shift = Shift::create([
                    'branch_id' => $branch->id,
                    'employee_id' => $employee->id,
                    'shift_number' => $this->sequences->next('shift'),
                    'opened_at' => now(),
                    'opening_cash' => Money::round($openingCash),
                    'status' => Shift::STATUS_OPEN,
                    'notes' => $notes,
                ]);

                foreach ($nozzleIds as $nozzleId) {
                    $nozzle = Nozzle::query()->whereKey($nozzleId)->lockForUpdate()->firstOrFail();

                    if ((int) $nozzle->branch_id !== (int) $branch->id) {
                        throw ValidationException::withMessages([
                            'nozzles' => "Nozzle {$nozzle->nozzle_number} belongs to a different branch.",
                        ]);
                    }

                    $current = Money::n($nozzle->current_meter);
                    $supplied = isset($openingMeters[$nozzleId])
                        ? Quantity::round((string) $openingMeters[$nozzleId])
                        : $current;

                    // A closing/opening reading lower than the system meter is
                    // rejected with the spec's exact wording.
                    if (Quantity::compare($supplied, $current) < 0) {
                        throw ValidationException::withMessages([
                            "nozzles.{$nozzleId}.opening_meter" => \App\Services\Fuel\MeterService::ERROR_LOWER_METER,
                        ]);
                    }

                    // Record the physical opening reading for the shift.
                    MeterReading::create([
                        'branch_id' => $branch->id,
                        'nozzle_id' => $nozzle->id,
                        'shift_id' => $shift->id,
                        'type' => MeterReading::TYPE_OPENING,
                        'previous_meter' => $current,
                        'current_meter' => $supplied,
                        'quantity' => Quantity::subtract($supplied, $current),
                        'user_id' => $employee->id,
                        'ip_address' => request()->ip(),
                    ]);

                    ShiftNozzle::create([
                        'shift_id' => $shift->id,
                        'nozzle_id' => $nozzle->id,
                        'opening_meter' => $supplied,
                        'system_litres' => '0.000',
                        'sold_litres' => '0.000',
                    ]);
                }

                $this->audit->record(
                    userId: $employee->id,
                    action: 'shift_open',
                    module: 'shift',
                    referenceType: Shift::class,
                    referenceId: $shift->id,
                    newData: [
                        'shift_number' => $shift->shift_number,
                        'branch_id' => $branch->id,
                        'opening_cash' => $shift->opening_cash,
                        'nozzles' => $nozzleIds,
                    ],
                );

                return $shift->load('nozzles');
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Shift open failed', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'branch_id' => 'Unable to open the shift. No changes were saved.',
            ]);
        }
    }

    /**
     * An employee may hold only one OPEN shift (spec section 6).
     */
    private function assertNoOpenShift(User $employee): void
    {
        $existing = Shift::query()
            ->where('employee_id', $employee->id)
            ->where('status', Shift::STATUS_OPEN)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'branch_id' => "You already have an open shift ({$existing->shift_number}). Close it before opening another.",
            ]);
        }
    }

    /**
     * A nozzle may be dispensed on only one OPEN shift at a time.
     */
    private function assertNozzlesAvailable(array $nozzleIds): void
    {
        if ($nozzleIds === []) {
            return;
        }

        $busy = DB::table('shift_nozzles')
            ->join('shifts', 'shifts.id', '=', 'shift_nozzles.shift_id')
            ->whereIn('shift_nozzles.nozzle_id', $nozzleIds)
            ->where('shifts.status', Shift::STATUS_OPEN)
            ->join('nozzles', 'nozzles.id', '=', 'shift_nozzles.nozzle_id')
            ->pluck('nozzles.nozzle_number')
            ->all();

        if ($busy !== []) {
            throw ValidationException::withMessages([
                'nozzles' => 'These nozzles are already on an open shift: '.implode(', ', $busy).'.',
            ]);
        }
    }

    /**
     * The shift the user may currently sell against, if any.
     */
    public function activeShiftFor(User $user, ?int $branchId = null): ?Shift
    {
        $query = Shift::query()
            ->with(['nozzles.nozzle.dispenser', 'nozzles.nozzle.fuelProduct', 'branch', 'employee'])
            ->where('employee_id', $user->id)
            ->where('status', Shift::STATUS_OPEN);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query->latest('opened_at')->first();
    }

    /**
     * Live totals for the active shift screen.
     *
     *   Expected Cash = Opening Cash + Cash Sales + Customer Cash Payments
     *                  - Cash Expenses - Cash Handovers/Drops
     *
     * The authoritative figure is always recomputed from the underlying
     * documents; the denormalised columns on `shifts` are only a cache.
     */
    public function summary(Shift $shift): array
    {
        $opening = Money::n($shift->opening_cash);

        $cashSales = Money::n($this->sumSalePayments($shift, 'CASH'));
        $customerPayments = Money::n($this->sumCustomerPayments($shift));
        $cashIn = Money::n($this->sumCashIn($shift));
        $expenses = Money::n($this->sumExpenses($shift));
        $cashOut = Money::n($this->sumCashOut($shift));
        $drops = Money::n($this->sumCashDrops($shift));

        $totalInflows = Money::add(Money::add($opening, $cashSales), Money::add($customerPayments, $cashIn));
        $totalOutflows = Money::add(Money::add($expenses, $cashOut), $drops);
        $expected = Money::subtract($totalInflows, $totalOutflows);

        return [
            'opening_cash' => $opening,
            'cash_sales' => $cashSales,
            'customer_payments' => $customerPayments,
            'cash_in' => $cashIn,
            'expenses' => $expenses,
            'cash_out' => $cashOut,
            'cash_drops' => $drops,
            'expected_cash' => $expected,
            'card_sales' => Money::n($this->sumSalePayments($shift, 'CARD')),
            'credit_sales' => Money::n($this->sumSalePayments($shift, 'CREDIT')),
            'other_sales' => Money::n($this->sumOtherSales($shift)),
        ];
    }

    public function calculateExpectedCash(Shift $shift): string
    {
        return $this->summary($shift)['expected_cash'];
    }

    public function calculateTotalDrops(Shift $shift): string
    {
        return $this->summary($shift)['cash_drops'];
    }

    public function addCashMovement(Shift $shift, string $amount, string $type, User $user, ?string $notes = null): \App\Models\ShiftCash
    {
        return \App\Models\ShiftCash::create([
            'shift_id' => $shift->id,
            'entry_type' => $type,
            'amount' => Money::round($amount),
            'user_id' => $user->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Sales tables arrive in Phase 5; until then these read the same columns
     * the sale transaction will populate, and return zero on an empty schema.
     */
    private function sumSalePayments(Shift $shift, string $method): string
    {
        if (! $this->tableExists('sales')) {
            return '0.00';
        }

        return Money::n(DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.shift_id', $shift->id)
            ->where('sales.status', 'COMPLETED')
            ->where('sale_payments.method', $method)
            ->sum('sale_payments.amount'));
    }

    private function sumOtherSales(Shift $shift): string
    {
        if (! $this->tableExists('sales')) {
            return '0.00';
        }

        return Money::n(DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.shift_id', $shift->id)
            ->where('sales.status', 'COMPLETED')
            ->whereNotIn('sale_payments.method', ['CASH', 'CARD', 'CREDIT'])
            ->sum('sale_payments.amount'));
    }

    private function sumCustomerPayments(Shift $shift): string
    {
        if (! $this->tableExists('customer_payments')) {
            return '0.00';
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('customer_payments', 'shift_id')) {
            return '0.00';
        }

        $methodCol = \Illuminate\Support\Facades\Schema::hasColumn('customer_payments', 'payment_method') ? 'payment_method' : 'method';

        return Money::n(DB::table('customer_payments')
            ->where('shift_id', $shift->id)
            ->where($methodCol, 'CASH')
            ->sum('amount'));
    }

    private function sumExpenses(Shift $shift): string
    {
        if (! $this->tableExists('expenses')) {
            return '0.00';
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('expenses', 'shift_id')) {
            return '0.00';
        }

        $methodCol = \Illuminate\Support\Facades\Schema::hasColumn('expenses', 'payment_method') ? 'payment_method' : 'method';
        $statusCol = \Illuminate\Support\Facades\Schema::hasColumn('expenses', 'status') ? 'status' : null;

        $query = DB::table('expenses')
            ->where('shift_id', $shift->id)
            ->where($methodCol, 'CASH');

        if ($statusCol) {
            $query->whereIn($statusCol, ['APPROVED', 'PAID']);
        }

        return Money::n($query->sum('amount'));
    }

    private function sumCashIn(Shift $shift): string
    {
        $fromEntries = $this->tableExists('cash_entries')
            ? DB::table('cash_entries')
                ->where('shift_id', $shift->id)
                ->where('type', 'CASH_IN')
                ->where('status', 'APPROVED')
                ->sum('amount')
            : 0;

        $fromShiftCash = DB::table('shift_cash')
            ->where('shift_id', $shift->id)
            ->where('entry_type', 'CASH_IN')
            ->sum('amount');

        return Money::n(Money::add((string) $fromEntries, (string) $fromShiftCash));
    }

    private function sumCashOut(Shift $shift): string
    {
        $fromEntries = $this->tableExists('cash_entries')
            ? DB::table('cash_entries')
                ->where('shift_id', $shift->id)
                ->where('type', 'CASH_OUT')
                ->where('status', 'APPROVED')
                ->sum('amount')
            : 0;

        $fromShiftCash = DB::table('shift_cash')
            ->where('shift_id', $shift->id)
            ->where('entry_type', 'CASH_OUT')
            ->sum('amount');

        return Money::n(Money::add((string) $fromEntries, (string) $fromShiftCash));
    }

    private function sumCashDrops(Shift $shift): string
    {
        return Money::n(DB::table('shift_cash')
            ->where('shift_id', $shift->id)
            ->whereIn('entry_type', ['DROP', 'HANDOVER'])
            ->sum('amount'));
    }

    private function tableExists(string $table): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    }
}
