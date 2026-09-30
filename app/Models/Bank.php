<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    public const TYPE_COMMERCIAL = 'COMMERCIAL';
    public const TYPE_ISLAMIC = 'ISLAMIC';
    public const TYPE_PUBLIC = 'PUBLIC';
    public const TYPE_DIGITAL = 'DIGITAL';

    protected $table = 'banks';

    protected $fillable = [
        'name', 'short_name', 'bank_type', 'branch_name', 'branch_code',
        'city', 'address', 'phone', 'ntn', 'status',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /** "HBL — Lahore Main Branch", or just "HBL" when no branch is recorded. */
    public function displayName(): string
    {
        return $this->branch_name
            ? "{$this->name} — {$this->branch_name}"
            : $this->name;
    }
}
