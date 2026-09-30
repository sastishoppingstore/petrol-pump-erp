<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalFuelConsumption extends Model
{
    use HasFactory;

    const TYPE_GENERATOR = 'GENERATOR';
    const TYPE_STATION_VEHICLE = 'STATION_VEHICLE';
    const TYPE_TESTING = 'TESTING';
    const TYPE_CLEANING = 'CLEANING';

    protected $fillable = [
        'tank_id',
        'consumption_type',
        'litres_consumed',
        'consumption_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'consumption_date' => 'date',
            'litres_consumed' => 'decimal:3',
        ];
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    /**
     * Get human-readable consumption type
     */
    public function getTypeLabel(): string
    {
        return match ($this->consumption_type) {
            'GENERATOR' => 'Generator Fuel',
            'STATION_VEHICLE' => 'Station Vehicle',
            'TESTING' => 'Quality Testing',
            'CLEANING' => 'Cleaning',
            default => 'Unknown',
        };
    }

    /**
     * Check if this is a reversal (negative litres)
     */
    public function isReversal(): bool
    {
        return $this->litres_consumed < 0;
    }
}
