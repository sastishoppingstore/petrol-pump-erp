<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_VERIFIED = 'VERIFIED';

    protected $fillable = [
        'branch_id',
        'bank_account_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'reconciliation_date',
        'reconciled_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'statement_balance' => 'string',
        'book_balance' => 'string',
        'statement_date' => 'date',
        'reconciliation_date' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function getDifferenceAttribute()
    {
        return (float) bcsub($this->statement_balance, $this->book_balance, 2);
    }

    public function isBalanced(): bool
    {
        return abs($this->getDifferenceAttribute()) < 0.01;
    }
}
