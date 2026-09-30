<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    public const TYPE_RECEIVED = 'RECEIVED'; // Received from Customer
    public const TYPE_ISSUED = 'ISSUED';     // Issued to Supplier

    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_DEPOSITED = 'DEPOSITED';
    public const STATUS_CLEARED = 'CLEARED';
    public const STATUS_BOUNCED = 'BOUNCED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'branch_id',
        'type',
        'cheque_number',
        'bank_name',
        'bank_account_id',
        'customer_id',
        'supplier_id',
        'payee_name',
        'amount',
        'cheque_date',
        'due_date',
        'is_pdc',
        'status',
        'deposit_date',
        'cleared_date',
        'bounced_date',
        'bounce_reason',
        'bank_charges',
        'image_path',
        'notes',
        'created_by',
        'actioned_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'bank_charges' => 'decimal:2',
            'cheque_date' => 'date',
            'due_date' => 'date',
            'deposit_date' => 'date',
            'cleared_date' => 'date',
            'bounced_date' => 'date',
            'is_pdc' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function isPdc(): bool
    {
        return $this->is_pdc || ($this->due_date && $this->due_date->isFuture());
    }

    public function partyName(): string
    {
        if ($this->type === self::TYPE_RECEIVED) {
            return $this->customer?->name ?? $this->payee_name ?? 'Customer';
        }

        return $this->supplier?->name ?? $this->payee_name ?? 'Supplier';
    }
}
