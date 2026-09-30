<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvance extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_RECOVERED = 'RECOVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const METHOD_CASH = 'CASH';
    public const METHOD_BANK_TRANSFER = 'BANK_TRANSFER';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'amount',
        'balance',
        'monthly_deduction',
        'advance_date',
        'payment_method',
        'bank_account_id',
        'shift_id',
        'reason',
        'status',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'advance_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
