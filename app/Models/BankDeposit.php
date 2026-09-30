<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDeposit extends Model
{
    public const TYPE_CASH = 'CASH';
    public const TYPE_CHEQUE = 'CHEQUE';

    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_REVERSED = 'REVERSED';

    protected $fillable = [
        'branch_id', 'bank_account_id', 'bank_name', 'shift_id',
        'amount', 'balance_before', 'balance_after',
        'reference_number', 'deposited_at', 'reason', 'deposit_type',
        'deposited_by', 'slip_path', 'status', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'deposited_at' => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function depositor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposited_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
