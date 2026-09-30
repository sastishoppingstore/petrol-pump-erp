<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAWAL = 'WITHDRAWAL';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    public const TYPE_CARD_SETTLEMENT = 'CARD_SETTLEMENT';
    public const TYPE_CHEQUE_CLEARED = 'CHEQUE_CLEARED';
    public const TYPE_CHEQUE_BOUNCED = 'CHEQUE_BOUNCED';
    public const TYPE_BANK_CHARGES = 'BANK_CHARGES';
    public const TYPE_INTEREST = 'INTEREST';
    public const TYPE_REVERSAL = 'REVERSAL';

    protected $fillable = [
        'branch_id',
        'bank_account_id',
        'type',
        'reference_number',
        'description',
        'debit_amount',
        'credit_amount',
        'transaction_date',
        'value_date',
        'slip_path',
        'shift_id',
        'related_account_id',
        'reconciled',
        'reconciled_at',
        'notes',
    ];

    protected $casts = [
        'debit_amount' => 'string',
        'credit_amount' => 'string',
        'transaction_date' => 'date',
        'value_date' => 'date',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'related_account_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function markReconciled(): void
    {
        $this->update([
            'reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }
}
