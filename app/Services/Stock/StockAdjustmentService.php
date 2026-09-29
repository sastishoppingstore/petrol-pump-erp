<?php

namespace App\Services\Stock;

use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Models\Tank;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Stock adjustments and their approval flow.
 *
 * An adjustment is a *request* until it is approved. Only approval moves the
 * stock, and it does so through StockService::move() like every other change.
 */
class StockAdjustmentService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditLogService $audit,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    /**
     * Raise an adjustment request. Nothing moves yet.
     */
    public function request(
        Tank $tank,
        string $type,
        string $quantity,
        string $reason,
        ?int $userId,
        ?string $notes = null,
    ): StockAdjustment {
        $this->assertType($type);

        if (Quantity::isNegative($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity cannot be negative.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every stock adjustment.',
            ]);
        }

        return StockAdjustment::create([
            'reference_number' => $this->sequences->next('adjustment'), // ADJ-{YYYY}-{6}
            'branch_id' => $tank->branch_id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $tank->fuel_product_id,
            'type' => $type,
            'quantity' => Quantity::round($quantity),
            'reason' => $reason,
            'notes' => $notes,
            'status' => StockAdjustment::STATUS_PENDING,
            'requested_by' => $userId,
            'requested_at' => now(),
        ]);
    }

    /**
     * Approve an adjustment and move the stock.
     *
     * The stock change and the status change commit together, so an approved
     * adjustment can never exist without its movement.
     */
    public function approve(StockAdjustment $adjustment, ?int $userId): StockAdjustment
    {
        if (! $adjustment->isPending()) {
            throw ValidationException::withMessages([
                'status' => "This adjustment is already {$adjustment->status}.",
            ]);
        }

        // Must be inside a transaction for move(); the caller's transaction is
        // opened here if it is not already running.
        return DB::transaction(function () use ($adjustment, $userId) {
            $locked = StockAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'status' => "This adjustment is already {$locked->status}.",
                ]);
            }

            $tank = Tank::query()->whereKey($locked->tank_id)->lockForUpdate()->firstOrFail();

            $movement = $this->stock->move(
                tank: $tank,
                type: $this->movementTypeFor($locked->type),
                quantity: Quantity::n($locked->quantity),
                referenceType: StockAdjustment::class,
                referenceId: $locked->id,
                reason: $locked->reason,
                userId: $userId,
            );

            $locked->update([
                'status' => StockAdjustment::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'stock_before' => $movement->before_quantity,
                'stock_after' => $movement->after_quantity,
                'tank_movement_id' => $movement->id,
            ]);

            $this->audit->record(
                userId: $userId,
                action: 'stock_adjustment_approve',
                module: 'stock',
                referenceType: StockAdjustment::class,
                referenceId: $locked->id,
                oldData: ['status' => StockAdjustment::STATUS_PENDING],
                newData: [
                    'status' => StockAdjustment::STATUS_APPROVED,
                    'quantity' => $locked->quantity,
                    'type' => $locked->type,
                    'reason' => $locked->reason,
                    'before' => $movement->before_quantity,
                    'after' => $movement->after_quantity,
                ],
            );

            return $locked->fresh();
        });
    }

    public function reject(StockAdjustment $adjustment, ?int $userId, string $reason): StockAdjustment
    {
        if (! $adjustment->isPending()) {
            throw ValidationException::withMessages([
                'status' => "This adjustment is already {$adjustment->status}.",
            ]);
        }

        $adjustment->update([
            'status' => StockAdjustment::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->audit->record(
            userId: $userId,
            action: 'stock_adjustment_reject',
            module: 'stock',
            referenceType: StockAdjustment::class,
            referenceId: $adjustment->id,
            newData: ['status' => StockAdjustment::STATUS_REJECTED, 'reason' => $reason],
        );

        return $adjustment;
    }

    private function movementTypeFor(string $adjustmentType): string
    {
        return match ($adjustmentType) {
            StockAdjustment::TYPE_IN => StockService::TYPE_ADJUSTMENT_IN,
            StockAdjustment::TYPE_OUT => StockService::TYPE_ADJUSTMENT_OUT,
            StockAdjustment::TYPE_LOSS => StockService::TYPE_LOSS,
            StockAdjustment::TYPE_CORRECTION => StockService::TYPE_CORRECTION,
            default => throw new \InvalidArgumentException("Unknown adjustment type [{$adjustmentType}]."),
        };
    }

    private function assertType(string $type): void
    {
        $valid = [
            StockAdjustment::TYPE_IN,
            StockAdjustment::TYPE_OUT,
            StockAdjustment::TYPE_LOSS,
            StockAdjustment::TYPE_CORRECTION,
        ];

        if (! in_array($type, $valid, true)) {
            throw ValidationException::withMessages([
                'type' => "Unknown adjustment type [{$type}].",
            ]);
        }
    }
}
