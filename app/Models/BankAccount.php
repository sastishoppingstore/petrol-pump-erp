<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    public const TYPE_CURRENT = 'CURRENT';
    public const TYPE_SAVINGS = 'SAVINGS';

    protected $fillable = [
        'branch_id', 'bank_id', 'account_title', 'account_number',
        'iban', 'account_type', 'opening_balance', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Running balance derived from the transaction ledger — never stored, so it
     * cannot drift from the transactions that produced it (spec section 45).
     */
    public function currentBalance(): string
    {
        $opening = Money::n($this->opening_balance);

        if (\Illuminate\Support\Facades\Schema::hasTable('bank_transactions') && $this->transactions()->exists()) {
            $credits = Money::n($this->transactions()
                ->where('status', BankTransaction::STATUS_COMPLETED)
                ->whereIn('type', [
                    BankTransaction::TYPE_DEPOSIT,
                    BankTransaction::TYPE_TRANSFER_IN,
                    BankTransaction::TYPE_MARKUP,
                    BankTransaction::TYPE_CHEQUE_DEPOSIT,
                ])->sum('amount'));

            $debits = Money::n($this->transactions()
                ->where('status', BankTransaction::STATUS_COMPLETED)
                ->whereIn('type', [
                    BankTransaction::TYPE_WITHDRAWAL,
                    BankTransaction::TYPE_TRANSFER_OUT,
                    BankTransaction::TYPE_CHARGES,
                    BankTransaction::TYPE_CHEQUE_BOUNCE,
                ])->sum('amount'));

            return Money::subtract(Money::add($opening, $credits), $debits);
        }

        $deposited = Money::n($this->deposits()->where('status', 'COMPLETED')->sum('amount'));

        return Money::add($opening, $deposited);
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Show only the last few digits, e.g. "•••• 4321".
     */
    public function maskedAccountNumber(): string
    {
        $number = (string) $this->account_number;

        if (strlen($number) <= 4) {
            return $number;
        }

        return '•••• '.substr($number, -4);
    }

    public function displayName(): string
    {
        return "{$this->bank?->name} — {$this->account_title} ({$this->maskedAccountNumber()})";
    }
}
