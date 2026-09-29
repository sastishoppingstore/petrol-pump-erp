<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelProduct extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const UNIT_LITRE = 'LITRE';

    protected $table = 'fuel_products';

    protected $fillable = [
        'code',
        'name',
        'unit',
        'color',
        'selling_price',
        'average_cost',
        'tax_rate',
        'minimum_stock',
        'price_requires_approval',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            // Cast to string so Eloquent hands bcmath-compatible values to the
            // helpers. Casting to float would reintroduce the precision bug.
            'selling_price' => 'decimal:2',
            'average_cost' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'minimum_stock' => 'decimal:3',
            'price_requires_approval' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(FuelPrice::class);
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The price a sale would use right now: branch-specific price if one is
     * set, otherwise the fuel product's selling price.
     */
    public function currentPrice(?int $branchId = null): string
    {
        $price = FuelPrice::query()
            ->where('fuel_product_id', $this->id)
            ->when(
                $branchId,
                fn ($q) => $q->where(fn ($q2) => $q2->where('branch_id', $branchId)->orWhereNull('branch_id'))
            )
            ->where('effective_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>', now()))
            ->orderByRaw('branch_id IS NULL')  // branch-specific price wins
            ->orderByDesc('effective_from')
            ->first();

        return $price?->price ?? Money::n($this->selling_price);
    }
}
