<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAWAL = 'WITHDRAWAL';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    public const TYPE_CHARGES = 'CHARGES';
    public const TYPE_MARKUP = 'MARKUP';
    public const TYPE_CHEQUE_DEPOSIT = 'CHEQUE_DEPOSIT';
    public const TYPE_CHEQUE_BOUNCE = 'CHEQUE_BOUNCE';

    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_REVERSED = 'REVERSED';

    protected $fillable = [
        'branch_id',
        'bank_account_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_number',
        'transaction_date',
        'description',
        'performed_by',
        'slip_path',
        'shift_id',
        'related_account_id',
        'reconciled',
        'reconciled_at',
        'reconciliation_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'datetime',
            'reconciled_at' => 'datetime',
            'reconciled' => 'boolean',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'related_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function isDebit(): bool
    {
        return in_array($this->type, [
            self::TYPE_WITHDRAWAL,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_CHARGES,
            self::TYPE_CHEQUE_BOUNCE,
        ], true);
    }

    public function isCredit(): bool
    {
        return in_array($this->type, [
            self::TYPE_DEPOSIT,
            self::TYPE_TRANSFER_IN,
            self::TYPE_MARKUP,
            self::TYPE_CHEQUE_DEPOSIT,
        ], true);
    }
}
