<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NozzleTest extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'branch_id',
        'nozzle_id',
        'tank_id',
        'shift_id',
        'user_id',
        'test_number',
        'litres',
        'meter_start',
        'meter_end',
        'tested_at',
        'reason',
        'returned_to_tank',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'litres' => 'decimal:3',
            'meter_start' => 'decimal:3',
            'meter_end' => 'decimal:3',
            'tested_at' => 'datetime',
            'returned_to_tank' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
