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

    /**
     * Columns of the canonical `bank_reconciliations` table (migration
     * 2026_01_01_007600): the book side is `ledger_balance` and the
     * variance is stored in `difference`. The older `book_balance` /
     * `reconciliation_date` names exist nowhere in the migrated table.
     */
    protected $fillable = [
        'branch_id',
        'bank_account_id',
        'statement_date',
        'statement_balance',
        'ledger_balance',
        'difference',
        'reconciled_by',
        'statement_file_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'statement_balance' => 'string',
        'ledger_balance' => 'string',
        'difference' => 'string',
        'statement_date' => 'date',
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

    /**
     * Statement balance − ledger (book) balance. Same value the
     * `difference` column stores; computed live so it stays correct
     * even for rows written before the column was populated.
     */
    public function getDifferenceAttribute(): string
    {
        return bcsub((string) $this->statement_balance, (string) $this->ledger_balance, 2);
    }

    public function isBalanced(): bool
    {
        return bccomp($this->getDifferenceAttribute(), '0.00', 2) === 0;
    }
}
