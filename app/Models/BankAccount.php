<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;

    public const TYPE_CURRENT = 'CURRENT';
    public const TYPE_SAVINGS = 'SAVINGS';
    public const TYPE_DEPOSIT = 'DEPOSIT';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_CLOSED = 'CLOSED';

    /**
     * Transaction types that ADD money to an account / take it out.
     * Same classification BankController uses for the bank book
     * (bank_transactions.type enum, migration 2026_01_01_007600).
     */
    public const CREDIT_TYPES = ['DEPOSIT', 'TRANSFER_IN', 'MARKUP', 'CHEQUE_DEPOSIT'];
    public const DEBIT_TYPES = ['WITHDRAWAL', 'TRANSFER_OUT', 'CHARGES', 'CHEQUE_BOUNCE'];

    /**
     * Columns of the canonical `bank_accounts` table (migration
     * 2026_01_01_004100). There is deliberately NO `current_balance`
     * here: that column does not exist on the table — the live balance
     * is computed by currentBalance() from bank_transactions.
     */
    protected $fillable = [
        'branch_id',
        'bank_id',
        'account_title',
        'account_number',
        'iban',
        'account_type',
        'opening_balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Live balance: opening_balance + Σ credit-type transactions
     * − Σ debit-type transactions, in bcmath — identical to
     * BankController::accountBalance(). The bank_transactions ledger
     * is the single source of truth (append-only).
     */
    public function currentBalance(): string
    {
        $credits = (string) ($this->transactions()
            ->whereIn('type', self::CREDIT_TYPES)
            ->sum('amount') ?? '0');

        $debits = (string) ($this->transactions()
            ->whereIn('type', self::DEBIT_TYPES)
            ->sum('amount') ?? '0');

        return bcsub(bcadd((string) $this->opening_balance, $credits, 2), $debits, 2);
    }

    /**
     * Read-only accessor so legacy `$account->current_balance` reads
     * keep working even though the column itself does not exist.
     * (Writes are not possible: the key is not in $fillable.)
     */
    public function getCurrentBalanceAttribute(): string
    {
        return $this->currentBalance();
    }

    /**
     * Bank name via the parent bank — legacy views select accounts
     * with `$account->bank_name`, which is not a column here.
     */
    public function getBankNameAttribute(): ?string
    {
        return $this->bank?->name;
    }

    /** Account number with only the last four digits visible. */
    public function maskedAccountNumber(): string
    {
        $number = (string) $this->account_number;

        if (strlen($number) <= 4) {
            return $number;
        }

        return '****' . substr($number, -4);
    }
}
