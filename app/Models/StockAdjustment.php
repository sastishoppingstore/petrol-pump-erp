<?php

namespace App\Models;

use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_LOSS = 'LOSS';
    public const TYPE_CORRECTION = 'CORRECTION';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    /**
     * The workflow fields are writable because only StockAdjustmentService
     * drives this record's lifecycle. Without them here, a create/update would
     * silently drop the status and the before/after stock figures.
     */
    protected $fillable = [
        'reference_number',
        'branch_id',
        'tank_id',
        'fuel_product_id',
        'type',
        'quantity',
        'reason',
        'notes',
        'status',
        'stock_before',
        'stock_after',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'tank_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PENDING => 'warning',
            default => 'secondary',
        };
    }
}
