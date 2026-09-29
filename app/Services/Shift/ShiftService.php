<?php

namespace App\Services\Shift;

use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Role;
use App\Models\Shift;
use App\Models\ShiftCash;
use App\Models\ShiftNozzle;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Fuel\MeterService;
use App\Services\System\NotificationService;
use App\Services\System\NumberSequenceService;
use App\Support\Decimal;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Shift management engine (spec section 3 & 4).
 *
 * Rules:
 * - Only one OPEN shift per employee and per nozzle.
 * - Opening meter must match nozzle's current meter within tolerance, or requires a note.
 * - Closing meter cannot be lower than opening meter.
 * - Cash Difference = Actual Cash - Expected Cash.
 * - If |Difference| > shift_variance_threshold, closing requires manager approval and note.
 * - Only shift owner or user with shift_close permission can close a shift.
 */
class ShiftService
{
    public function __construct(
        private readonly NumberSequenceService $numbers,
        private readonly AuditLogService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Open a new shift.
     *
     * @param array{
     *     branch_id: int,
     *     user_id: int,
     *     opening_cash: string|float|int,
     *     nozzles: array<int, array{nozzle_id: int, opening_meter?: string|float|null, notes?: string|null}>,
     *     opening_notes?: string|null
     * } $data
     */
    public function open(array $data, User $openedBy): Shift
    {
        return DB::transaction(function () use ($data, $openedBy) {
            $branchId = (int) $data['branch_id'];
            $userId = (int) $data['user_id'];
            $openingCash = Money::round((string) ($data['opening_cash'] ?? '0.00'));
            $openingNotes = $data['opening_notes'] ?? null;
            $nozzleEntries = $data['nozzles'] ?? [];

            if (Money::isNegative($openingCash)) {
                throw ValidationException::withMessages([
                    'opening_cash' => 'Opening cash float cannot be negative.',
                ]);
            }

            if (empty($nozzleEntries)) {
                throw ValidationException::withMessages([
                    'nozzles' => 'At least one nozzle must be assigned to the shift.',
                ]);
            }

            // 1. One OPEN shift per employee rule
            $hasOpenShift = Shift::where('user_id', $userId)
                ->where('status', Shift::STATUS_OPEN)
                ->lockForUpdate()
                ->exists();

            if ($hasOpenShift) {
                throw ValidationException::withMessages([
                    'user_id' => 'This employee already has an active open shift.',
                ]);
            }

            $nozzleIds = array_column($nozzleEntries, 'nozzle_id');

            // 2. Lock nozzles and validate branch + availability
            $nozzles = Nozzle::whereIn('id', $nozzleIds)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($nozzles->count() !== count(array_unique($nozzleIds))) {
                throw ValidationException::withMessages([
                    'nozzles' => 'One or more selected nozzles are invalid or belong to another branch.',
                ]);
            }

            // Check if any selected nozzle is in an OPEN shift
            $busyNozzle = ShiftNozzle::whereIn('nozzle_id', $nozzleIds)
                ->whereHas('shift', fn ($q) => $q->where('status', Shift::STATUS_OPEN))
                ->first();

            if ($busyNozzle) {
                $busyNozzleObj = $nozzles->get($busyNozzle->nozzle_id);
                $nozzleNum = $busyNozzleObj ? $busyNozzleObj->nozzle_number : $busyNozzle->nozzle_id;
                throw ValidationException::withMessages([
                    'nozzles' => "Nozzle {$nozzleNum} is already assigned to another open shift.",
                ]);
            }

            $tolerance = (string) config('erp.meter_variance_tolerance', 0.5);
            $shiftNumber = $this->numbers->next('shift');

            $shift = Shift::create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'shift_number' => $shiftNumber,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'status' => Shift::STATUS_OPEN,
                'opening_notes' => $openingNotes,
            ]);

            foreach ($nozzleEntries as $entry) {
                $nozzleId = (int) $entry['nozzle_id'];
                /** @var Nozzle $nozzle */
                $nozzle = $nozzles->get($nozzleId);
                $currentMeter = (string) $nozzle->current_meter;
                $enteredMeter = isset($entry['opening_meter']) && $entry['opening_meter'] !== ''
                    ? Quantity::round((string) $entry['opening_meter'])
                    : $currentMeter;
                $note = $entry['notes'] ?? null;

                // Validate variance against tolerance
                $meterDiff = Decimal::abs(Decimal::subtract($enteredMeter, $currentMeter, Quantity::SCALE));
                if (Decimal::compare($meterDiff, $tolerance, Quantity::SCALE) > 0 && empty($note) && empty($openingNotes)) {
                    throw ValidationException::withMessages([
                        "nozzle_{$nozzleId}" => "Opening meter ({$enteredMeter}) differs from system meter ({$currentMeter}) for Nozzle {$nozzle->nozzle_number}. A note is mandatory.",
                    ]);
                }

                // If entered meter differs, update nozzle current_meter to reflect physical reading
                if (Decimal::compare($enteredMeter, $currentMeter, Quantity::SCALE) !== 0) {
                    $nozzle->forceFill(['current_meter' => $enteredMeter])->save();
                }

                ShiftNozzle::create([
                    'shift_id' => $shift->id,
                    'nozzle_id' => $nozzleId,
                    'opening_meter' => $enteredMeter,
                    'notes' => $note,
                ]);

                // Append-only meter reading log
                MeterReading::create([
                    'branch_id' => $branchId,
                    'nozzle_id' => $nozzleId,
                    'shift_id' => $shift->id,
                    'type' => MeterReading::TYPE_OPENING,
                    'previous_meter' => $currentMeter,
                    'current_meter' => $enteredMeter,
                    'quantity' => '0.000',
                    'user_id' => $openedBy->id,
                    'reason' => 'Shift opened: '.$shiftNumber.($note ? " ({$note})" : ''),
                    'ip_address' => request()->ip(),
                ]);
            }

            $this->audit->log(
                user: $openedBy,
                action: 'shift.open',
                module: 'shifts',
                referenceType: Shift::class,
                referenceId: $shift->id,
                oldData: null,
                newData: [
                    'shift_number' => $shift->shift_number,
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'opening_cash' => $openingCash,
                    'nozzle_count' => count($nozzleEntries),
                ]
            );

            return $shift;
        });
    }

    /**
     * Add a cash movement (float addition, drop, handover) to an open shift.
     */
    public function addCashMovement(
        Shift $shift,
        string $amount,
        string $type,
        User $user,
        ?string $notes = null,
    ): ShiftCash {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'Cash movements can only be added to an active open shift.',
            ]);
        }

        $cleanAmount = Money::round($amount);
        if (Money::isNegative($cleanAmount) || Money::isZero($cleanAmount)) {
            throw ValidationException::withMessages([
                'amount' => 'Cash movement amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($shift, $cleanAmount, $type, $user, $notes) {
            $record = ShiftCash::create([
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $cleanAmount,
                'notes' => $notes,
            ]);

            // Update expected cash on shift
            $expectedCash = $this->calculateExpectedCash($shift);
            $shift->forceFill([
                'expected_cash' => $expectedCash,
                'cash_drops_total' => $this->calculateTotalDrops($shift),
            ])->save();

            $this->audit->log(
                user: $user,
                action: 'shift.cash_'.$type,
                module: 'shifts',
                referenceType: ShiftCash::class,
                referenceId: $record->id,
                oldData: null,
                newData: [
                    'shift_id' => $shift->id,
                    'type' => $type,
                    'amount' => $cleanAmount,
                    'notes' => $notes,
                ]
            );

            return $record;
        });
    }

    /**
     * Formula:
     * Expected Cash = Opening Cash + Cash Sales + Customer Cash Payments Received - Cash Expenses - Cash Handovers/Drops.
     */
    public function calculateExpectedCash(Shift $shift): string
    {
        $opening = Money::round((string) $shift->opening_cash);

        // 1. Float Additions from shift_cash
        $additions = ShiftCash::where('shift_id', $shift->id)
            ->where('type', ShiftCash::TYPE_FLOAT_ADDITION)
            ->sum('amount');
        $additionsStr = Money::round((string) ($additions ?: '0.00'));

        // 2. Cash sales (when sales table is populated in Phase 5)
        $cashSalesStr = '0.00';
        if (Schema::hasTable('sale_payments')) {
            $cashSales = DB::table('sale_payments')
                ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
                ->where('sales.shift_id', $shift->id)
                ->where('sales.status', 'COMPLETED')
                ->where('sale_payments.payment_method', 'CASH')
                ->sum('sale_payments.amount');
            $cashSalesStr = Money::round((string) ($cashSales ?: '0.00'));
        }

        // 3. Customer cash payments (Phase 6)
        $customerCashStr = '0.00';
        if (Schema::hasTable('customer_payments')) {
            $custPayments = DB::table('customer_payments')
                ->where('shift_id', $shift->id)
                ->where('payment_method', 'CASH')
                ->sum('amount');
            $customerCashStr = Money::round((string) ($custPayments ?: '0.00'));
        }

        // 4. Cash expenses (Phase 8)
        $cashExpensesStr = '0.00';
        if (Schema::hasTable('expenses')) {
            $cashExpenses = DB::table('expenses')
                ->where('shift_id', $shift->id)
                ->where('payment_method', 'CASH')
                ->where('status', 'APPROVED')
                ->sum('amount');
            $cashExpensesStr = Money::round((string) ($cashExpenses ?: '0.00'));
        }

        // 5. Drops & Handovers
        $dropsStr = $this->calculateTotalDrops($shift);

        // Expected = Opening + Additions + CashSales + CustomerCash - CashExpenses - Drops
        $total = Money::add($opening, $additionsStr);
        $total = Money::add($total, $cashSalesStr);
        $total = Money::add($total, $customerCashStr);
        $total = Money::subtract($total, $cashExpensesStr);
        $total = Money::subtract($total, $dropsStr);

        return $total;
    }

    public function calculateTotalDrops(Shift $shift): string
    {
        $drops = ShiftCash::where('shift_id', $shift->id)
            ->whereIn('type', [
                ShiftCash::TYPE_DROP,
                ShiftCash::TYPE_HANDOVER,
                ShiftCash::TYPE_EXPENSE_PAYOUT,
            ])
            ->sum('amount');

        return Money::round((string) ($drops ?: '0.00'));
    }

    /**
     * Close an active shift.
     *
     * @param array{
     *     actual_cash: string|float|int,
     *     card_total?: string|float|null,
     *     closing_notes?: string|null,
     *     nozzles: array<int, array{nozzle_id: int, closing_meter: string|float}>
     * } $data
     */
    public function close(Shift $shift, array $data, User $closedBy): Shift
    {
        // 1. Authorization: Only the shift owner or a user with shift_close permission
        if ($shift->user_id !== $closedBy->id && ! $closedBy->hasPermission(PermissionList::SHIFT_CLOSE)) {
            throw new AuthorizationException('You are not authorized to close this shift.');
        }

        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'This shift is not open and cannot be closed.',
            ]);
        }

        return DB::transaction(function () use ($shift, $data, $closedBy) {
            $shift->lockForUpdate();

            $actualCash = Money::round((string) ($data['actual_cash'] ?? '0.00'));
            $cardTotal = Money::round((string) ($data['card_total'] ?? '0.00'));
            $closingNotes = trim((string) ($data['closing_notes'] ?? ''));
            $nozzleInputs = $data['nozzles'] ?? [];

            if (Money::isNegative($actualCash)) {
                throw ValidationException::withMessages([
                    'actual_cash' => 'Actual cash cannot be negative.',
                ]);
            }

            // 2. Validate closing meters per nozzle
            $shiftNozzles = $shift->shiftNozzles()->with('nozzle')->get()->keyBy('nozzle_id');
            $totalLitres = '0.000';

            foreach ($nozzleInputs as $input) {
                $nozzleId = (int) $input['nozzle_id'];
                /** @var ShiftNozzle|null $sn */
                $sn = $shiftNozzles->get($nozzleId);

                if (! $sn) {
                    continue;
                }

                $openingMeter = (string) $sn->opening_meter;
                $closingMeter = Quantity::round((string) $input['closing_meter']);

                // Spec rule: "A meter value may only increase. Reject any lower value with:
                // Closing meter reading cannot be lower than opening meter reading."
                if (Decimal::compare($closingMeter, $openingMeter, Quantity::SCALE) < 0) {
                    throw ValidationException::withMessages([
                        "nozzle_{$nozzleId}" => MeterService::ERROR_LOWER_METER,
                    ]);
                }

                $meterSales = Decimal::subtract($closingMeter, $openingMeter, Quantity::SCALE);
                $totalLitres = Quantity::add($totalLitres, $meterSales);

                // System litres from POS sales in Phase 5
                $systemLitres = '0.000';
                if (Schema::hasTable('sale_items')) {
                    $sysL = DB::table('sale_items')
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->where('sales.shift_id', $shift->id)
                        ->where('sales.status', 'COMPLETED')
                        ->where('sale_items.nozzle_id', $nozzleId)
                        ->sum('sale_items.litres');
                    $systemLitres = Quantity::round((string) ($sysL ?: '0.000'));
                }

                $meterVariance = Decimal::subtract($meterSales, $systemLitres, Quantity::SCALE);

                $sn->forceFill([
                    'closing_meter' => $closingMeter,
                    'meter_sales_litres' => $meterSales,
                    'system_litres' => $systemLitres,
                    'meter_variance' => $meterVariance,
                ])->save();

                // Update physical nozzle current_meter
                $sn->nozzle->forceFill(['current_meter' => $closingMeter])->save();

                // Write append-only closing meter reading
                MeterReading::create([
                    'branch_id' => $shift->branch_id,
                    'nozzle_id' => $nozzleId,
                    'shift_id' => $shift->id,
                    'type' => MeterReading::TYPE_CLOSING,
                    'previous_meter' => $openingMeter,
                    'current_meter' => $closingMeter,
                    'quantity' => $meterSales,
                    'user_id' => $closedBy->id,
                    'reason' => 'Shift closed: '.$shift->shift_number,
                    'ip_address' => request()->ip(),
                ]);
            }

            // 3. Compute expected cash & variance
            $expectedCash = $this->calculateExpectedCash($shift);
            $difference = Money::subtract($actualCash, $expectedCash);

            // 4. Threshold check
            $threshold = (string) config('erp.shift_variance_threshold', 100);
            $absDifference = Decimal::abs($difference);
            $isOverThreshold = Decimal::compare($absDifference, $threshold, Money::SCALE) > 0;

            if ($isOverThreshold && empty($closingNotes)) {
                throw ValidationException::withMessages([
                    'closing_notes' => "Cash difference of Rs. {$difference} exceeds the threshold of Rs. {$threshold}. A closing note is required.",
                ]);
            }

            $isManagerOrAdmin = $closedBy->hasRole(Role::ADMIN) || $closedBy->hasRole(Role::MANAGER);

            // If over threshold and closed by attendant/cashier, needs manager approval
            if ($isOverThreshold && ! $isManagerOrAdmin) {
                $status = Shift::STATUS_PENDING_APPROVAL;
                $approvedBy = null;
                $approvedAt = null;

                // Notify managers & admins
                $this->notifyVarianceApproval($shift, $difference, $threshold);
            } else {
                $status = Shift::STATUS_CLOSED;
                $approvedBy = $isManagerOrAdmin ? $closedBy->id : null;
                $approvedAt = $isManagerOrAdmin ? now() : null;
            }

            $shift->forceFill([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => $difference,
                'card_total' => $cardTotal,
                'total_litres' => $totalLitres,
                'status' => $status,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'closing_notes' => $closingNotes ?: null,
            ])->save();

            $this->audit->log(
                user: $closedBy,
                action: 'shift.close',
                module: 'shifts',
                referenceType: Shift::class,
                referenceId: $shift->id,
                oldData: ['status' => Shift::STATUS_OPEN],
                newData: [
                    'status' => $status,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $actualCash,
                    'difference' => $difference,
                    'total_litres' => $totalLitres,
                ]
            );

            return $shift;
        });
    }

    /**
     * Manager approval for shifts flagged with excessive variance.
     */
    public function approve(Shift $shift, User $approver, ?string $notes = null): Shift
    {
        if (! $approver->hasRole(Role::ADMIN) && ! $approver->hasRole(Role::MANAGER) && ! $approver->hasPermission(PermissionList::SHIFT_CLOSE)) {
            throw new AuthorizationException('Only managers and admins can approve shift variances.');
        }

        if ($shift->status !== Shift::STATUS_PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'shift' => 'Only shifts pending approval can be approved.',
            ]);
        }

        return DB::transaction(function () use ($shift, $approver, $notes) {
            $shift->lockForUpdate();

            $combinedNotes = $shift->closing_notes;
            if ($notes) {
                $combinedNotes = ($combinedNotes ? $combinedNotes."\n" : '')."[Approved by {$approver->name}]: {$notes}";
            }

            $shift->forceFill([
                'status' => Shift::STATUS_CLOSED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'closing_notes' => $combinedNotes,
            ])->save();

            $this->audit->log(
                user: $approver,
                action: 'shift.approve',
                module: 'shifts',
                referenceType: Shift::class,
                referenceId: $shift->id,
                oldData: ['status' => Shift::STATUS_PENDING_APPROVAL],
                newData: [
                    'status' => Shift::STATUS_CLOSED,
                    'approved_by' => $approver->id,
                    'approval_notes' => $notes,
                ]
            );

            return $shift;
        });
    }

    /**
     * Send in-app notifications to admins & managers when a shift requires variance approval.
     */
    private function notifyVarianceApproval(Shift $shift, string $difference, string $threshold): void
    {
        $recipients = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.name', [Role::ADMIN, Role::MANAGER])
            ->pluck('user_roles.user_id')
            ->unique();

        $title = "Shift Variance Approval: {$shift->shift_number}";
        $message = sprintf(
            'Shift %s for %s has a cash variance of Rs. %s (Threshold: Rs. %s) and requires manager review.',
            $shift->shift_number,
            $shift->user?->name ?? 'Attendant',
            $difference,
            $threshold,
        );

        foreach ($recipients as $userId) {
            $this->notifications->notifyOnce(
                userId: $userId,
                type: 'SHIFT_VARIANCE',
                title: $title,
                message: $message,
                dedupeKey: "shift-variance:{$shift->id}",
                level: 'WARNING',
                module: 'shifts',
                referenceType: Shift::class,
                referenceId: $shift->id,
            );
        }
    }

    public function currentShiftForUser(User $user): ?Shift
    {
        return Shift::where('user_id', $user->id)
            ->where('status', Shift::STATUS_OPEN)
            ->with(['shiftNozzles.nozzle.fuelProduct', 'shiftCash', 'branch'])
            ->latest('opened_at')
            ->first();
    }

    public function activeShifts(?int $branchId = null): Collection
    {
        return Shift::where('status', Shift::STATUS_OPEN)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['user', 'shiftNozzles.nozzle.fuelProduct', 'branch'])
            ->latest('opened_at')
            ->get();
    }
}
