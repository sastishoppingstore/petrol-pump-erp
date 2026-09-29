<?php

namespace App\Services\Shift;

use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Shift;
use App\Models\ShiftNozzle;
use App\Services\Audit\AuditLogService;
use App\Services\Fuel\MeterService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Closing a shift (spec section 3).
 *
 *   Expected Cash = Opening Cash + Cash Sales + Customer Cash Payments
 *                  - Cash Expenses - Cash Handovers/Drops
 *   Difference    = Actual Cash - Expected Cash
 *
 * When |Difference| is over the configured threshold the shift is parked at
 * PENDING_APPROVAL and needs a manager (shift_close permission) to finish.
 * Only the shift owner or a user holding shift_close may close it at all.
 */
class ShiftClosingService
{
    public function __construct(
        private readonly ShiftService $shifts,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * @param  array<int|string, string>  $closingMeters  keyed by shift_nozzle id
     */
    public function close(
        Shift $shift,
        array $closingMeters,
        string $actualCash,
        User $actor,
        ?string $notes = null,
        string $cardSettlement = '0',
    ): Shift {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'status' => "Shift {$shift->shift_number} is already {$shift->status}.",
            ]);
        }

        // Only the shift owner, or someone with shift_close, may close it.
        if ((int) $shift->employee_id !== (int) $actor->id
            && ! $actor->hasPermission(PermissionList::SHIFT_CLOSE)) {
            throw ValidationException::withMessages([
                'status' => 'Only the shift owner or a manager can close this shift.',
            ]);
        }

        $summary = $this->shifts->summary($shift);
        $expected = $summary['expected_cash'];

        $actual = Money::round($actualCash);
        $difference = Money::subtract($actual, $expected);

        $threshold = Money::n((string) config('erp.shift_variance_threshold', 100));

        // |Difference| over the threshold needs a manager. Using the absolute
        // value matters: a balanced till and a small variance are both well
        // *inside* the tolerance, and must not be treated as exceeding it.
        $needsApproval = Money::exceedsTolerance($difference, $threshold);

        try {
            return DB::transaction(function () use (
                $shift, $closingMeters, $actual, $expected, $difference,
                $actor, $notes, $cardSettlement, $summary, $needsApproval
            ) {
                $locked = Shift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isOpen()) {
                    throw ValidationException::withMessages([
                        'status' => "Shift {$locked->shift_number} was already closed by someone else.",
                    ]);
                }

                $this->recordClosingMeters($locked, $closingMeters, $actor->id);

                $locked->update([
                    'closed_at' => now(),
                    'expected_cash' => $expected,
                    'actual_cash' => $actual,
                    'cash_difference' => $difference,
                    'card_settlement' => Money::round($cardSettlement),
                    'card_total' => $summary['card_sales'],
                    'credit_total' => $summary['credit_sales'],
                    'other_total' => $summary['other_sales'],
                    'total_sales' => Money::add(
                        Money::add($summary['cash_sales'], $summary['card_sales']),
                        Money::add($summary['credit_sales'], $summary['other_sales']),
                    ),
                    'expenses_total' => $summary['expenses'],
                    'closing_notes' => $notes,
                    'status' => $needsApproval
                        ? Shift::STATUS_PENDING_APPROVAL
                        : Shift::STATUS_CLOSED,
                ]);

                $this->audit->record(
                    userId: $actor->id,
                    action: 'shift_close',
                    module: 'shift',
                    referenceType: Shift::class,
                    referenceId: $locked->id,
                    oldData: ['status' => Shift::STATUS_OPEN],
                    newData: [
                        'status' => $locked->fresh()->status,
                        'expected_cash' => $expected,
                        'actual_cash' => $actual,
                        'cash_difference' => $difference,
                        'requires_approval' => $needsApproval,
                    ],
                );

                return $locked->fresh();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Shift close failed', ['shift_id' => $shift->id, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'actual_cash' => 'Unable to close the shift. No changes were saved.',
            ]);
        }
    }

    /**
     * A manager approves a shift that exceeded the cash variance threshold.
     */
    public function approve(Shift $shift, User $actor, ?string $note = null): Shift
    {
        if (! $shift->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Only a shift awaiting approval can be approved.',
            ]);
        }

        if (! $actor->hasAnyPermission([
            PermissionList::SHIFT_CLOSE,
            PermissionList::CASH_APPROVE,
        ])) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to approve a shift variance.',
            ]);
        }

        $shift->update([
            'status' => Shift::STATUS_CLOSED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'closing_notes' => $note
                ? trim($shift->closing_notes."\n\nApproved by {$actor->name}: ".$note)
                : $shift->closing_notes,
        ]);

        $this->audit->record(
            userId: $actor->id,
            action: 'shift_approve',
            module: 'shift',
            referenceType: Shift::class,
            referenceId: $shift->id,
            oldData: ['status' => Shift::STATUS_PENDING_APPROVAL],
            newData: ['status' => Shift::STATUS_CLOSED, 'note' => $note],
        );

        return $shift->fresh();
    }

    /**
     * Write the physical closing reading for every assigned nozzle and record
     * the meter variance.
     */
    private function recordClosingMeters(Shift $shift, array $supplied, int $userId): void
    {
        $assignments = ShiftNozzle::query()
            ->with('nozzle')
            ->where('shift_id', $shift->id)
            ->get();

        $tolerance = Quantity::n((string) config('erp.meter_variance_tolerance', 0.5));

        foreach ($assignments as $assignment) {
            $key = (string) $assignment->id;

            if (! isset($supplied[$key]) || $supplied[$key] === '') {
                throw ValidationException::withMessages([
                    "closing_meters.{$key}" => 'A closing meter reading is required for every nozzle on the shift.',
                ]);
            }

            $closing = Quantity::round((string) $supplied[$key]);
            $nozzle = $assignment->nozzle;

            // The spec's exact rejection message for a backwards meter.
            if (Quantity::compare($closing, $assignment->opening_meter) < 0) {
                throw ValidationException::withMessages([
                    "closing_meters.{$key}" => MeterService::ERROR_LOWER_METER,
                ]);
            }

            $current = Money::n($nozzle->current_meter);

            MeterReading::create([
                'branch_id' => $shift->branch_id,
                'nozzle_id' => $nozzle->id,
                'shift_id' => $shift->id,
                'type' => MeterReading::TYPE_CLOSING,
                'previous_meter' => $current,
                'current_meter' => $closing,
                'quantity' => Quantity::subtract($closing, $current),
                'user_id' => $userId,
                'ip_address' => request()->ip(),
            ]);

            $throughMeter = Quantity::subtract($closing, $assignment->opening_meter);
            $variance = Quantity::subtract($closing, $current);

            $assignment->update([
                'closing_meter' => $closing,
                'system_litres' => $throughMeter,
                'meter_variance' => $variance,
                'variance_flagged' => ! Quantity::isZero($tolerance)
                    && Quantity::compare(Quantity::round($variance), $tolerance) > 0,
            ]);
        }
    }
}
