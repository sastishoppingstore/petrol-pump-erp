<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const CATEGORY_LUBRICANT = 'LUBRICANT';
    public const CATEGORY_FILTER = 'FILTER';
    public const CATEGORY_TUCK_SHOP = 'TUCK_SHOP';
    public const CATEGORY_SERVICE = 'SERVICE';
    public const CATEGORY_TYRE = 'TYRE';
    public const CATEGORY_CAR_WASH = 'CAR_WASH';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'category',
        'unit',
        'cost_price',
        'selling_price',
        'current_stock',
        'min_stock_level',
        'barcode',
        'status',
        'notes',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'decimal:3',
        'min_stock_level' => 'decimal:3',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductStockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('category', '!=', self::CATEGORY_SERVICE)
            ->whereColumn('current_stock', '<=', 'min_stock_level');
    }

    public function isLowStock(): bool
    {
        if ($this->category === self::CATEGORY_SERVICE) {
            return false;
        }

        return Quantity::compare(Quantity::n($this->current_stock), Quantity::n($this->min_stock_level)) <= 0;
    }

    public function profitAmount(): string
    {
        return Money::subtract(Money::n($this->selling_price), Money::n($this->cost_price));
    }

    public function profitMargin(): string
    {
        $selling = Money::n($this->selling_price);
        if (Money::compare($selling, '0') <= 0) {
            return '0.00';
        }

        $profit = $this->profitAmount();
        // (profit / selling) * 100
        return bcdiv(bcmul($profit, '100', 4), $selling, 2);
    }
}
