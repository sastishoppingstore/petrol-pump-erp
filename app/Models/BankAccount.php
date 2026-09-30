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

    /**
     * Running balance derived from the deposit ledger — never stored, so it
     * cannot drift from the deposits that produced it (spec section 45).
     */
    public function currentBalance(): string
    {
        $deposited = Money::n($this->deposits()->where('status', 'COMPLETED')->sum('amount'));

        return Money::add(Money::n($this->opening_balance), $deposited);
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
