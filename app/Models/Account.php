<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    public const TYPE_ASSET = 'ASSET';
    public const TYPE_LIABILITY = 'LIABILITY';
    public const TYPE_EQUITY = 'EQUITY';
    public const TYPE_REVENUE = 'REVENUE';
    public const TYPE_EXPENSE = 'EXPENSE';

    public const BALANCE_DEBIT = 'DEBIT';
    public const BALANCE_CREDIT = 'CREDIT';

    // Standard Chart of Accounts Codes
    public const CODE_CASH_IN_HAND = '1010';
    public const CODE_BANK_ACCOUNTS = '1020';
    public const CODE_ACCOUNTS_RECEIVABLE = '1030';
    public const CODE_OMC_CARD_RECEIVABLE = '1040';
    public const CODE_FUEL_INVENTORY = '1050';
    public const CODE_LUBRICANT_INVENTORY = '1060';
    public const CODE_ACCOUNTS_PAYABLE = '2010';
    public const CODE_SALES_TAX_PAYABLE = '2020';
    public const CODE_OWNER_CAPITAL = '3010';
    public const CODE_OWNER_DRAWINGS = '3020';
    public const CODE_FUEL_SALES_REVENUE = '4010';
    public const CODE_LUBE_SALES_REVENUE = '4020';
    public const CODE_COST_OF_FUEL_SOLD = '5010';
    public const CODE_COST_OF_LUBE_SOLD = '5020';
    public const CODE_INVENTORY_GAIN_LOSS = '5030';
    public const CODE_CASH_SHORT_OVER = '5040';
    public const CODE_OPERATING_EXPENSES = '6010';

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'urdu_name',
        'type',
        'subcategory',
        'normal_balance',
        'is_system',
        'status',
        'opening_balance',
        'current_balance',
        'notes',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'opening_balance' => 'string',
        'current_balance' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isDebit(): bool
    {
        return $this->normal_balance === self::BALANCE_DEBIT;
    }

    public function isCredit(): bool
    {
        return $this->normal_balance === self::BALANCE_CREDIT;
    }
}
