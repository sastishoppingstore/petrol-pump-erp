<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankDipChart extends Model
{
    protected $table = 'tank_dip_charts';
    
    protected $fillable = [
        'tank_id',
        'centimeters',
        'litres',
        'notes',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}
