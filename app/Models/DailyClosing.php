<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyClosing extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_LOCKED = 'LOCKED';

    protected $fillable = [
        'branch_id',
        'closing_date',
        'closing_number',
        'status',
        'total_fuel_litres',
        'total_fuel_sales',
        'total_lube_sales',
        'total_sales_amount',
        'total_cash_sales',
        'total_credit_sales',
        'total_card_sales',
        'total_customer_receipts',
        'total_expenses',
        'total_bank_deposits',
        'total_supplier_payments',
        'opening_cash',
        'expected_cash',
        'actual_cash_counted',
        'cash_variance',
        'total_opening_stock',
        'total_purchases_stock',
        'total_sales_stock',
        'expected_dip_stock',
        'actual_dip_stock',
        'dip_variance_litres',
        'shifts_count',
        'shifts_verified',
        'tank_dips_verified',
        'cash_verified',
        'bank_deposits_verified',
        'closed_by',
        'approved_by',
        'approved_at',
        'email_sent_at',
        'email_recipient',
        'notes',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'approved_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'shifts_verified' => 'boolean',
        'tank_dips_verified' => 'boolean',
        'cash_verified' => 'boolean',
        'bank_deposits_verified' => 'boolean',
        'total_fuel_litres' => 'string',
        'total_fuel_sales' => 'string',
        'total_lube_sales' => 'string',
        'total_sales_amount' => 'string',
        'total_cash_sales' => 'string',
        'total_credit_sales' => 'string',
        'total_card_sales' => 'string',
        'total_customer_receipts' => 'string',
        'total_expenses' => 'string',
        'total_bank_deposits' => 'string',
        'total_supplier_payments' => 'string',
        'opening_cash' => 'string',
        'expected_cash' => 'string',
        'actual_cash_counted' => 'string',
        'cash_variance' => 'string',
        'total_opening_stock' => 'string',
        'total_purchases_stock' => 'string',
        'total_sales_stock' => 'string',
        'expected_dip_stock' => 'string',
        'actual_dip_stock' => 'string',
        'dip_variance_litres' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, self::STATUS_LOCKED], true);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
}
