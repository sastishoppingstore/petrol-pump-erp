<?php

namespace App\Models;

use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftNozzle extends Model
{
    protected $table = 'shift_nozzles';

    protected $fillable = [
        'shift_id',
        'nozzle_id',
        'opening_meter',
        'closing_meter',
        'sold_litres',
        'system_litres',
        'meter_variance',
        'variance_flagged',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_meter' => 'decimal:3',
            'closing_meter' => 'decimal:3',
            'sold_litres' => 'decimal:3',
            'system_litres' => 'decimal:3',
            'meter_variance' => 'decimal:3',
            'variance_flagged' => 'boolean',
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
