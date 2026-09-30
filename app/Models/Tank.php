<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tank extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_MAINTENANCE = 'MAINTENANCE';

    protected $fillable = [
        'branch_id',
        'fuel_product_id',
        'tank_number',
        'name',
        'capacity',
        'min_level',
        'max_level',
        'opening_stock',
        'low_stock_threshold',
        'installation_date',
        'notes',
        'status',
    ];

    /**
     * current_stock is intentionally NOT fillable.
     *
     * It may only change through StockService::move(), which writes a
     * tank_movements row and updates the tank under SELECT ... FOR UPDATE in
     * the same transaction. Letting a mass-assignment touch it would let
     * stock drift away from its movement history.
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:3',
            'min_level' => 'decimal:3',
            'max_level' => 'decimal:3',
            'opening_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'installation_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(TankReading::class);
    }

    public function dipCharts(): HasMany
    {
        return $this->hasMany(TankDipChart::class)->orderBy('dip_cm');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function stockPercent(): string
    {
        return Quantity::percentOf(Quantity::n($this->current_stock), Quantity::n($this->capacity));
    }

    /**
     * "ok" / "low" / "critical" — drives the stock bar colour on the tank card.
     */
    public function stockLevel(): string
    {
        $threshold = Quantity::n($this->low_stock_threshold);

        if (Quantity::isZero($threshold)) {
            return 'ok';
        }

        if (Quantity::compare(Quantity::n($this->current_stock), $threshold) <= 0) {
            return 'critical';
        }

        // Halfway to the threshold is the amber warning band.
        $half = Quantity::divide($threshold, '2', Quantity::SCALE);

        if (Quantity::compare(Quantity::n($this->current_stock), Quantity::add($threshold, $half)) <= 0) {
            return 'low';
        }

        return 'ok';
    }

    public function hasRoomFor(string $quantity): bool
    {
        $room = Quantity::subtract(Quantity::n($this->capacity), Quantity::n($this->current_stock));

        return Quantity::compare($quantity, $room) <= 0;
    }

    public function hasStockFor(string $quantity): bool
    {
        return Quantity::compare(Quantity::n($this->current_stock), $quantity) >= 0;
    }

    public function fuelName(): string
    {
        return $this->fuelProduct?->name ?? '—';
    }

    protected static function newFactory()
    {
        return \Database\Factories\TankFactory::new();
    }

    public function displayName(): string
    {
        return $this->name
            ? "{$this->tank_number} — {$this->name}"
            : $this->tank_number;
    }
}
