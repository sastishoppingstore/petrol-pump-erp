<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_VOIDED = 'VOIDED';
    public const STATUS_REFUNDED = 'REFUNDED';

    public const MODE_LITRES = 'LITRES';
    public const MODE_AMOUNT = 'AMOUNT';

    protected $fillable = [
        'branch_id', 'shift_id', 'invoice_number', 'customer_id', 'customer_name', 'customer_phone', 'vehicle_id',
        'employee_id', 'sale_date', 'sale_mode', 'subtotal', 'discount', 'tax',
        'total', 'total_litres', 'total_cost', 'status', 'notes',
        'void_reason', 'voided_by', 'voided_at', 'voided_sale_id',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'voided_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_litres' => 'decimal:3',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_VOIDED => 'danger',
            self::STATUS_REFUNDED => 'warning',
            default => 'secondary',
        };
    }

    /**
     * COGS = litres x cost_rate, using the historical cost stored on the item.
     */
    public function cogs(): string
    {
        return Money::n($this->total_cost);
    }

    /**
     * Gross Margin = Revenue - COGS. Never call this "net profit" (spec).
     */
    public function grossMargin(): string
    {
        return Money::subtract(Money::n($this->total), Money::n($this->total_cost));
    }

    public function grossMarginPercent(): string
    {
        if (Money::isZero(Money::n($this->total))) {
            return '0.00';
        }

        return \App\Support\Decimal::percentage(
            Money::n($this->grossMargin()),
            Money::n($this->total),
            2
        );
    }
}
