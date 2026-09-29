<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_CRITICAL = 'CRITICAL';

    public const TYPE_LOW_STOCK = 'LOW_STOCK';
    public const TYPE_SHIFT_VARIANCE = 'SHIFT_VARIANCE';
    public const TYPE_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const TYPE_CREDIT_OVERDUE = 'CREDIT_OVERDUE';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'level',
        'module',
        'reference_type',
        'reference_id',
        'dedupe_key',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function levelBadgeClass(): string
    {
        return match ($this->level) {
            self::LEVEL_CRITICAL => 'danger',
            self::LEVEL_WARNING => 'warning',
            default => 'info',
        };
    }
}
