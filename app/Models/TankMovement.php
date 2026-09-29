<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankMovement extends Model
{
    public const TYPE_PURCHASE = 'PURCHASE';
    public const TYPE_SALE = 'SALE';
    public const TYPE_ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    public const TYPE_ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    public const TYPE_LOSS = 'LOSS';
    public const TYPE_CORRECTION = 'CORRECTION';

    protected $fillable = [
        'branch_id',
        'tank_id',
        'fuel_product_id',
        'type',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reference_type',
        'reference_id',
        'unit_cost',
        'reason',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'before_quantity' => 'decimal:3',
            'after_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Colours for the movement type badge in the UI.
     */
    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE, self::TYPE_ADJUSTMENT_IN, self::TYPE_TRANSFER_IN => 'success',
            self::TYPE_SALE, self::TYPE_ADJUSTMENT_OUT, self::TYPE_TRANSFER_OUT => 'primary',
            self::TYPE_LOSS => 'danger',
            self::TYPE_CORRECTION => 'warning',
            default => 'secondary',
        };
    }
}
