<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    // bank_type values — migration 2026_01_01_004100 (banks.bank_type).
    public const TYPE_COMMERCIAL = 'COMMERCIAL';
    public const TYPE_ISLAMIC = 'ISLAMIC';
    public const TYPE_PUBLIC = 'PUBLIC';
    public const TYPE_DIGITAL = 'DIGITAL';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Columns of the canonical `banks` table (migration
     * 2026_01_01_004100). The older bank_code / bank_name / bank_name_ur
     * names belonged to a superseded schema draft and exist nowhere in
     * the migrated database.
     */
    protected $fillable = [
        'name',
        'short_name',
        'bank_type',
        'branch_name',
        'branch_code',
        'city',
        'address',
        'phone',
        'ntn',
        'status',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
