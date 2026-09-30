<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const STATUS_PAID = 'PAID';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_VOID = 'VOID';

    public const METHOD_CASH = 'CASH';
    public const METHOD_BANK_TRANSFER = 'BANK_TRANSFER';
    public const METHOD_CHEQUE = 'CHEQUE';

    protected $fillable = [
        'branch_id',
        'category_id',
        'expense_number',
        'date',
        'title',
        'amount',
        'payment_method',
        'bank_account_id',
        'shift_id',
        'payee',
        'receipt_number',
        'attachment_path',
        'status',
        'created_by',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
