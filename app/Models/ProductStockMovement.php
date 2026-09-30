<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockMovement extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'PURCHASE';
    public const TYPE_SALE = 'SALE';
    public const TYPE_ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    public const TYPE_ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    public const TYPE_RETURN = 'RETURN';

    protected $fillable = [
        'branch_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'before_stock',
        'after_stock',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'before_stock' => 'decimal:3',
        'after_stock' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
