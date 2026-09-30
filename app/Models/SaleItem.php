<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'branch_id', 'fuel_product_id', 'tank_id', 'dispenser_id',
        'nozzle_id', 'litres', 'rate', 'cost_rate', 'amount', 'cost_amount',
        'meter_start', 'meter_end',
    ];

    protected function casts(): array
    {
        return [
            'litres' => 'decimal:3',
            'rate' => 'decimal:2',
            'cost_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'cost_amount' => 'decimal:2',
            'meter_start' => 'decimal:3',
            'meter_end' => 'decimal:3',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }

    public function dispenser(): BelongsTo
    {
        return $this->belongsTo(Dispenser::class);
    }
}
