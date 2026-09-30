<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'fuel_product_id',
        'nozzle_id',
        'item_description',
        'item_type',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount',
        'total_amount',
        'meter_start',
        'meter_end',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'meter_start' => 'decimal:3',
            'meter_end' => 'decimal:3',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class, 'fuel_product_id');
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class, 'nozzle_id');
    }
}
