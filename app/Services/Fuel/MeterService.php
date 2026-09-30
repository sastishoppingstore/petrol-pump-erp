<?php

namespace App\Services\Fuel;

use App\Models\Nozzle;
use App\Models\NozzleTest;
use App\Models\MeterReading;
use App\Models\Tank;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Meter reading rules (spec section 3).
 *
 * A meter value may only increase. Anything lower is a reset, a rollover or
 * a fault, and must go through the dedicated correction workflow with a
 * mandatory reason, an audit row and a CORRECTION meter_readings entry. It is
 * never silently overwritten.
 */
class MeterService
{
    /** The exact wording the spec requires for a backwards meter. */
    public const ERROR_LOWER_METER = 'Closing meter reading cannot be lower than opening meter reading.';

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly ?NumberSequenceService $sequences = null,
    ) {
    }

    /**
     * Advance a nozzle's meter by the litres just dispensed.
     *
     * Called inside the sale transaction with the nozzle already locked
     * FOR UPDATE by SaleService.
     *
     * @return array{previous: string, current: string}
     */
    public function advance(Nozzle $nozzle, string $litres, ?int $saleId = null, ?int $shiftId = null): array
    {
        $previous = Money::n($nozzle->current_meter);
        $current = Quantity::add($previous, $litres);

        if (! $nozzle->canAdvanceTo($current)) {
            throw ValidationException::withMessages(['meter_end' => self::ERROR_LOWER_METER]);
        }

        $nozzle->forceFill(['current_meter' => $current])->save();

        MeterReading::create([
            'branch_id' => $nozzle->branch_id,
            'nozzle_id' => $nozzle->id,
            'shift_id' => $shiftId,
            'sale_id' => $saleId,
            'type' => MeterReading::TYPE_SALE,
            'previous_meter' => $previous,
            'current_meter' => $current,
            'quantity' => $litres,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);

        return ['previous' => $previous, 'current' => $current];
    }

    /**
     * Record a physical reading (shift open / shift close) without moving the
     * nozzle's meter. The difference against the system meter is the variance.
     */
    public function recordPhysical(
        Nozzle $nozzle,
        string $meter,
        string $type,
        ?int $shiftId = null,
        ?string $reason = null,
    ): MeterReading {
        if (! in_array($type, [MeterReading::TYPE_OPENING, MeterReading::TYPE_CLOSING], true)) {
            throw new \InvalidArgumentException("Unexpected physical reading type [{$type}].");
        }

        $previous = Money::n($nozzle->current_meter);
        $meter = Quantity::round($meter);

        // A physical reading may legitimately sit below the system meter after
        // a fault, so this is recorded rather than rejected — but the variance
        // must be surfaced to the caller.
        $variance = Quantity::subtract($meter, $previous);

        return MeterReading::create([
            'branch_id' => $nozzle->branch_id,
            'nozzle_id' => $nozzle->id,
            'shift_id' => $shiftId,
            'type' => $type,
            'previous_meter' => $previous,
            'current_meter' => $meter,
            'quantity' => $variance,
            'user_id' => auth()->id(),
            'reason' => $reason,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Authorised meter correction: reset, rollover or fault fix.
     *
     * Requires the stock_adjustment permission, a mandatory reason, writes a
     * CORRECTION reading and an audit row, and all in one transaction.
     */
    public function correct(Nozzle $nozzle, string $newMeter, string $reason, ?int $userId = null): MeterReading
    {
        $userId ??= auth()->id();

        // Authorisation is enforced here, not only in the controller, so the
        // rule holds for any caller (spec section 3: meter correction requires
        // stock_adjustment or approve).
        $actor = $userId ? \App\Models\User::find($userId) : auth()->user();

        if (! $actor || ! $actor->hasAnyPermission([
            PermissionList::STOCK_ADJUSTMENT,
            PermissionList::STOCK_APPROVE,
        ])) {
            throw ValidationException::withMessages([
                'new_meter' => 'You do not have permission to correct a meter reading.',
            ]);
        }

        if (trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for a meter correction.',
            ]);
        }

        if (Quantity::isNegative($newMeter)) {
            throw ValidationException::withMessages([
                'new_meter' => 'The corrected meter reading cannot be negative.',
            ]);
        }

        try {
            return DB::transaction(function () use ($nozzle, $newMeter, $reason, $userId) {
                $locked = Nozzle::query()->lockForUpdate()->findOrFail($nozzle->id);

                $previous = Money::n($locked->current_meter);
                $newMeter = Quantity::round($newMeter);

                $locked->forceFill(['current_meter' => $newMeter])->save();

                $reading = MeterReading::create([
                    'branch_id' => $locked->branch_id,
                    'nozzle_id' => $locked->id,
                    'type' => MeterReading::TYPE_CORRECTION,
                    'previous_meter' => $previous,
                    'current_meter' => $newMeter,
                    'quantity' => Quantity::subtract($newMeter, $previous),
                    'user_id' => $userId,
                    'reason' => $reason,
                    'ip_address' => request()->ip(),
                ]);

                $this->audit->record(
                    userId: $userId,
                    action: 'meter_correction',
                    module: 'fuel',
                    referenceType: Nozzle::class,
                    referenceId: $locked->id,
                    oldData: ['current_meter' => $previous],
                    newData: ['current_meter' => $newMeter, 'reason' => $reason],
                );

                return $reading;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Meter correction failed', [
                'nozzle_id' => $nozzle->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'new_meter' => 'Unable to apply the meter correction. No changes were saved.',
            ]);
        }
    }

    /**
     * Validate a closing meter for a shift close. The spec's exact message is
     * used so the wording is consistent everywhere it appears.
     */
    public function assertNotLower(string $closingMeter, string $openingMeter): void
    {
        if (Quantity::compare($closingMeter, $openingMeter) < 0) {
            throw ValidationException::withMessages(['meter_end' => self::ERROR_LOWER_METER]);
        }
    }

    /**
     * Record a calibration test where fuel is dispensed into a measure can and returned to the tank.
     * Recorded in nozzle_tests and excluded from sales.
     */
    public function recordNozzleTest(
        Nozzle $nozzle,
        string $litres,
        string $reason = 'Calibration test / پیمانہ ٹیسٹ',
        ?int $shiftId = null,
        ?User $actor = null,
        ?string $notes = null,
    ): NozzleTest {
        $actor ??= auth()->user();
        if (! $actor) {
            throw ValidationException::withMessages(['user' => 'Authenticated user required.']);
        }

        $litres = Quantity::round($litres);
        if (Quantity::compare($litres, '0') <= 0) {
            throw ValidationException::withMessages(['litres' => 'Test litres must be greater than zero.']);
        }

        return DB::transaction(function () use ($nozzle, $litres, $reason, $shiftId, $actor, $notes) {
            $locked = Nozzle::query()->lockForUpdate()->findOrFail($nozzle->id);
            $tank = Tank::query()->lockForUpdate()->findOrFail($locked->tank_id);

            $previous = Money::n($locked->current_meter);
            $current = Quantity::add($previous, $litres);

            $locked->forceFill(['current_meter' => $current])->save();

            // Record physical meter reading as NOZZLE_TEST
            MeterReading::create([
                'branch_id' => $locked->branch_id,
                'nozzle_id' => $locked->id,
                'shift_id' => $shiftId,
                'type' => MeterReading::TYPE_TEST,
                'previous_meter' => $previous,
                'current_meter' => $current,
                'quantity' => $litres,
                'user_id' => $actor->id,
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ]);

            $sequences = $this->sequences ?? app(NumberSequenceService::class);
            $testNumber = $sequences->next('test');

            $test = NozzleTest::create([
                'branch_id' => $locked->branch_id,
                'nozzle_id' => $locked->id,
                'tank_id' => $tank->id,
                'shift_id' => $shiftId,
                'user_id' => $actor->id,
                'test_number' => $testNumber,
                'litres' => $litres,
                'meter_start' => $previous,
                'meter_end' => $current,
                'tested_at' => now(),
                'reason' => $reason,
                'returned_to_tank' => true,
                'status' => NozzleTest::STATUS_COMPLETED,
                'notes' => $notes,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'nozzle_test',
                module: 'fuel',
                referenceType: NozzleTest::class,
                referenceId: $test->id,
                newData: [
                    'test_number' => $testNumber,
                    'nozzle_id' => $locked->id,
                    'litres' => $litres,
                    'meter_start' => $previous,
                    'meter_end' => $current,
                    'returned_to_tank' => true,
                ],
            );

            return $test;
        });
    }

    /**
     * Calculate throughput considering meter rollover (e.g. 99999 or 9999999).
     */
    public function calculateThroughput(string $openingMeter, string $closingMeter, bool $isRollover = false, string $rolloverMax = '100000.000'): string
    {
        $opening = Quantity::round($openingMeter);
        $closing = Quantity::round($closingMeter);

        if (! $isRollover) {
            if (Quantity::compare($closing, $opening) < 0) {
                throw ValidationException::withMessages(['closing_meter' => self::ERROR_LOWER_METER]);
            }
            return Quantity::subtract($closing, $opening);
        }

        // Rollover: (max - opening) + closing
        $max = Quantity::round($rolloverMax);
        $diff = Quantity::subtract($max, $opening);
        return Quantity::add($diff, $closing);
    }

    /**
     * Record a meter rollover reading and advance nozzle meter to the rollover closing.
     */
    public function recordRollover(
        Nozzle $nozzle,
        string $closingMeter,
        string $rolloverMax,
        string $reason,
        User $actor,
        ?int $shiftId = null,
    ): MeterReading {
        $opening = Money::n($nozzle->current_meter);
        $throughput = $this->calculateThroughput($opening, $closingMeter, true, $rolloverMax);

        return DB::transaction(function () use ($nozzle, $closingMeter, $opening, $throughput, $reason, $actor, $shiftId) {
            $locked = Nozzle::query()->lockForUpdate()->findOrFail($nozzle->id);

            $locked->forceFill(['current_meter' => Quantity::round($closingMeter)])->save();

            $reading = MeterReading::create([
                'branch_id' => $locked->branch_id,
                'nozzle_id' => $locked->id,
                'shift_id' => $shiftId,
                'type' => MeterReading::TYPE_ROLLOVER,
                'previous_meter' => $opening,
                'current_meter' => Quantity::round($closingMeter),
                'quantity' => $throughput,
                'user_id' => $actor->id,
                'reason' => $reason ?: 'Meter rollover beyond max limit',
                'ip_address' => request()->ip(),
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'meter_rollover',
                module: 'fuel',
                referenceType: Nozzle::class,
                referenceId: $locked->id,
                oldData: ['current_meter' => $opening],
                newData: [
                    'current_meter' => Quantity::round($closingMeter),
                    'throughput' => $throughput,
                    'reason' => $reason,
                ],
            );

            return $reading;
        });
    }
}
