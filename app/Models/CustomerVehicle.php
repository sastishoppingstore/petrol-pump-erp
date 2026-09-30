<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVehicle extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id', 'registration_number', 'driver_name', 'make', 'model',
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
