<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftNozzle extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'nozzle_id',
        'opening_meter',
        'closing_meter',
        'meter_sales_litres',
        'system_litres',
        'meter_variance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_meter' => 'decimal:3',
            'closing_meter' => 'decimal:3',
            'meter_sales_litres' => 'decimal:3',
            'system_litres' => 'decimal:3',
            'meter_variance' => 'decimal:3',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }
}
