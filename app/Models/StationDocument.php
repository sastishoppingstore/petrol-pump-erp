<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A compliance document stored in the Document Vault: OGRA licence,
 * dealership agreement, NOC, calibration certificate, etc.
 */
class StationDocument extends Model
{
    public const CATEGORY_OGRA_LICENCE = 'ogra_licence';
    public const CATEGORY_DEALERSHIP = 'dealership';
    public const CATEGORY_NOC = 'noc';
    public const CATEGORY_CALIBRATION = 'calibration';
    public const CATEGORY_OTHER = 'other';

    /** Days before expiry at which a document counts as "expiring". */
    public const EXPIRING_SOON_DAYS = 30;

    protected $fillable = [
        'branch_id', 'title', 'category', 'file_path',
        'issue_date', 'expiry_date', 'note', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return array<string, string> category => label
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_OGRA_LICENCE => 'OGRA Licence / اوگرا لائسنس',
            self::CATEGORY_DEALERSHIP => 'Dealership Agreement / ڈیلرشپ معاہدہ',
            self::CATEGORY_NOC => 'NOC / این او سی',
            self::CATEGORY_CALIBRATION => 'Calibration Certificate / کیلیبریشن سرٹیفکیٹ',
            self::CATEGORY_OTHER => 'Other / دیگر',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? ucfirst((string) $this->category);
    }

    /**
     * Expiry state for badges: `no-expiry`, `valid`, `expiring`
     * (within EXPIRING_SOON_DAYS) or `expired`.
     */
    public function expiryStatus(): string
    {
        if ($this->expiry_date === null) {
            return 'no-expiry';
        }

        $today = Carbon::today();

        if ($this->expiry_date->lt($today)) {
            return 'expired';
        }

        if ($this->expiry_date->lte($today->copy()->addDays(self::EXPIRING_SOON_DAYS))) {
            return 'expiring';
        }

        return 'valid';
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays(self::EXPIRING_SOON_DAYS));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', Carbon::today());
    }
}
