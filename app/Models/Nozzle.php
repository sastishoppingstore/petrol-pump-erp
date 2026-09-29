<?php

namespace App\Models;

use App\Support\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nozzle extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_MAINTENANCE = 'MAINTENANCE';

    protected $fillable = [
        'branch_id',
        'dispenser_id',
        'tank_id',
        'fuel_product_id',
        'nozzle_number',
        'opening_meter',
        'meter_multiplier',
        'status',
        'notes',
    ];

    /**
     * current_meter is intentionally NOT fillable.
     *
     * It is advanced only by SaleService (increment) or MeterService
     * (authorised CORRECTION). Mass assignment would allow a silent edit that
     * bypasses the append-only meter_readings history.
     */
    protected function casts(): array
    {
        return [
            'opening_meter' => 'decimal:3',
            'current_meter' => 'decimal:3',
            'meter_multiplier' => 'decimal:3',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function dispenser(): BelongsTo
    {
        return $this->belongsTo(Dispenser::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function label(): string
    {
        return "{$this->dispenser?->dispenser_number} / {$this->nozzle_number}";
    }

    /**
     * A nozzle may only ever move forward. Anything lower is a meter fault and
     * must go through the CORRECTION workflow, not a normal sale.
     */
    public function canAdvanceTo(string $meter): bool
    {
        return Quantity::compare($meter, Quantity::n($this->current_meter)) >= 0;
    }
}
