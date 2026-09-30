<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_VOID = 'VOID';

    public const PAYMENT_UNPAID = 'UNPAID';
    public const PAYMENT_PARTIAL = 'PARTIAL';
    public const PAYMENT_PAID = 'PAID';

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'purchase_number',
        'invoice_number',
        'challan_number',
        'purchase_date',
        'tank_id',
        'fuel_product_id',
        'volume_ordered',
        'volume_received',
        'dip_before',
        'dip_after',
        'shortage_litres',
        'shortage_amount',
        'shortage_claimed',
        'shortage_claim_status',
        'density',
        'temperature',
        'tanker_number',
        'driver_name',
        'bill_photo_path',
        'purchase_rate',
        'ifem',
        'petroleum_levy',
        'subtotal',
        'tax_amount',
        'freight_charges',
        'other_charges',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'approved_at' => 'datetime',
        'volume_ordered' => 'string',
        'volume_received' => 'string',
        'dip_before' => 'string',
        'dip_after' => 'string',
        'shortage_litres' => 'string',
        'shortage_amount' => 'string',
        'shortage_claimed' => 'boolean',
        'density' => 'string',
        'temperature' => 'string',
        'purchase_rate' => 'string',
        'ifem' => 'string',
        'petroleum_levy' => 'string',
        'subtotal' => 'string',
        'tax_amount' => 'string',
        'freight_charges' => 'string',
        'other_charges' => 'string',
        'total_amount' => 'string',
        'paid_amount' => 'string',
        'balance_amount' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
