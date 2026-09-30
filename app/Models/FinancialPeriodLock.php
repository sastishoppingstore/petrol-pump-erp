<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPeriodLock extends Model
{
    protected $fillable = [
        'branch_id',
        'locked_until_date',
        'reason',
        'locked_by',
        'is_locked',
    ];

    protected $casts = [
        'locked_until_date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
