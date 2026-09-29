<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankReading extends Model
{
    public const VARIANCE_SURPLUS = 'SURPLUS';
    public const VARIANCE_SHORTAGE = 'SHORTAGE';
    public const VARIANCE_MATCH = 'MATCH';

    protected $fillable = [
        'branch_id',
        'tank_id',
        'fuel_product_id',
        'reading_date',
        'physical_quantity',
        'expected_quantity',
        'variance_quantity',
        'variance_type',
        'dip_height',
        'dip_width',
        'dip_length',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'reading_date' => 'date',
            'physical_quantity' => 'decimal:3',
            'expected_quantity' => 'decimal:3',
            'variance_quantity' => 'decimal:3',
            'dip_height' => 'decimal:3',
            'dip_width' => 'decimal:3',
            'dip_length' => 'decimal:3',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
