<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    public const TYPE_SALE = 'SALE';
    public const TYPE_OPENING = 'OPENING';
    public const TYPE_CLOSING = 'CLOSING';
    public const TYPE_CORRECTION = 'CORRECTION';

    protected $fillable = [
        'branch_id',
        'nozzle_id',
        'shift_id',
        'sale_id',
        'type',
        'previous_meter',
        'current_meter',
        'quantity',
        'user_id',
        'reason',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'previous_meter' => 'decimal:3',
            'current_meter' => 'decimal:3',
            'quantity' => 'decimal:3',
        ];
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
