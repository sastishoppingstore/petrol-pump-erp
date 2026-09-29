<?php

namespace App\Services\Stock;

use App\Models\Tank;
use App\Models\TankMovement;
use App\Support\Quantity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The single place tank stock may change (spec section 3).
 *
 * Every stock change goes through move(). There is no other code path that
 * writes tanks.current_stock. Each call:
 *   1. locks the tank row with SELECT ... FOR UPDATE
 *   2. validates the resulting balance (>= 0 and <= capacity)
 *   3. writes an append-only tank_movements row with before/after quantities
 *   4. updates tanks.current_stock
 *   ...all inside the caller's transaction, so it commits or rolls back whole.
 *
 * The row lock is what makes this safe when two attendants sell from the same
 * tank at the same moment: the second transaction blocks until the first has
 * committed, then reads the already-updated stock.
 */
class StockService
{
    public const TYPE_PURCHASE = 'PURCHASE';
    public const TYPE_SALE = 'SALE';
    public const TYPE_ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    public const TYPE_ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    public const TYPE_LOSS = 'LOSS';
    public const TYPE_CORRECTION = 'CORRECTION';

    /**
     * Movement types that add stock, and those that remove it.
     *
     * @var array<int, string>
     */
    public const INBOUND_TYPES = [
        self::TYPE_PURCHASE,
        self::TYPE_ADJUSTMENT_IN,
        self::TYPE_TRANSFER_IN,
    ];

    public const OUTBOUND_TYPES = [
        self::TYPE_SALE,
        self::TYPE_ADJUSTMENT_OUT,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_LOSS,
    ];

    /**
     * The caller must already hold a transaction. Enforced so move() can never
     * half-apply: a movement row without a matching stock update (or the
     * reverse) would corrupt the ledger.
     */
    public function move(
        Tank $tank,
        string $type,
        string $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $reason = null,
        ?string $unitCost = null,
        ?int $userId = null,
    ): TankMovement {
        $this->assertInTransaction();
        $this->assertKnownType($type);

        $quantity = Quantity::round($quantity);

        if (Quantity::compare($quantity, '0') < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Movement quantity cannot be negative. Use an outbound movement type instead.',
            ]);
        }

        // The lock. Everything below reads the *latest* committed stock.
        $locked = Tank::query()->whereKey($tank->id)->lockForUpdate()->firstOrFail();

        $before = Quantity::n($locked->current_stock);
        $delta = $this->signedDelta($type, $quantity);
        $after = Quantity::add($before, $delta);

        $this->assertValidBalance($locked, $after, $type, $quantity);

        $locked->forceFill(['current_stock' => $after])->save();

        $movement = TankMovement::create([
            'branch_id' => $locked->branch_id,
            'tank_id' => $locked->id,
            'fuel_product_id' => $locked->fuel_product_id,
            'type' => $type,
            'quantity' => $delta,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'unit_cost' => $unitCost,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
        ]);

        return $movement;
    }

    /**
     * Expected stock derived purely from the movement ledger.
     *
     *   Expected = Opening + Purchases + Positive adjustments - Sales
     *              - Negative adjustments (+/- transfers / loss / correction)
     *
     * This is deliberately recomputed from the ledger rather than trusting
     * tanks.current_stock, so a drift between the two is detectable.
     */
    public function expected(int $tankId): string
    {
        $tank = Tank::findOrFail($tankId);

        // Every movement stores a *signed* quantity (inbound positive,
        // outbound negative), so the balance is simply the opening stock plus
        // the sum of the ledger. Summing in and out separately and combining
        // them would double-count the sign.
        $net = TankMovement::query()
            ->where('tank_id', $tankId)
            ->sum('quantity');

        return Quantity::add(Quantity::n($tank->opening_stock), Quantity::n($net));
    }

    /**
     * Variance = Physical (dip) stock - Expected stock.
     */
    public function variance(Tank $tank, string $physicalQuantity): string
    {
        return Quantity::subtract(
            Quantity::round($physicalQuantity),
            $this->expected($tank->id),
        );
    }

    /**
     * Does the ledger agree with the tank's current stock? A non-zero result
     * means something bypassed StockService, which must never happen.
     */
    public function ledgerDrift(Tank $tank): string
    {
        return Quantity::subtract(
            Quantity::n($tank->current_stock),
            $this->expected($tank->id),
        );
    }

    private function signedDelta(string $type, string $quantity): string
    {
        if (in_array($type, self::INBOUND_TYPES, true)) {
            return Quantity::round($quantity);
        }

        if (in_array($type, self::OUTBOUND_TYPES, true)) {
            return Quantity::subtract('0', $quantity);
        }

        // CORRECTION is signed by the caller.
        return Quantity::round($quantity);
    }

    private function assertValidBalance(Tank $tank, string $after, string $type, string $quantity): void
    {
        // Stock may never go below zero.
        if (Quantity::isNegative($after)) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Insufficient stock in tank %s. Available: %s L, requested: %s L.',
                    $tank->tank_number,
                    Quantity::format(Quantity::n($tank->current_stock)),
                    Quantity::format($quantity),
                ),
            ]);
        }

        // ...nor above the tank's physical capacity.
        $capacity = Quantity::n($tank->capacity);

        if (Quantity::compare($after, $capacity) > 0) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'That would exceed tank %s capacity. Available room: %s L, requested: %s L.',
                    $tank->tank_number,
                    Quantity::format(Quantity::subtract($capacity, Quantity::n($tank->current_stock))),
                    Quantity::format($quantity),
                ),
            ]);
        }
    }

    private function assertKnownType(string $type): void
    {
        $known = array_merge(
            self::INBOUND_TYPES,
            self::OUTBOUND_TYPES,
            [self::TYPE_CORRECTION],
        );

        if (! in_array($type, $known, true)) {
            throw new \InvalidArgumentException("Unknown stock movement type [{$type}].");
        }
    }

    private function assertInTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            // Without a transaction the movement row and the stock update could
            // diverge, so this is a programming error, not user input.
            throw new \LogicException(
                'StockService::move() must be called inside a DB transaction.'
            );
        }
    }
}
