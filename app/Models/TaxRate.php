<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    protected $table = 'tax_rates';

    protected $fillable = [
        'province_id', 'fuel_product_id', 'rate',
        'effective_from', 'effective_to', 'source', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            // Percentage, kept as an exact decimal string.
            'rate' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }
}
