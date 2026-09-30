<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVehicle extends Model
{
    protected $fillable = [
        'customer_id', 'registration_number', 'make', 'model',
        'colour', 'type', 'tank_capacity', 'status',
    ];

    protected function casts(): array
    {
        return ['tank_capacity' => 'decimal:3'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
