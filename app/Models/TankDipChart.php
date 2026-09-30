<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankDipChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'dip_cm',
        'litres',
    ];

    protected $casts = [
        'dip_cm' => 'decimal:2',
        'litres' => 'decimal:3',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}
