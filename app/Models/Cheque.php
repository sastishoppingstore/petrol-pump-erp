<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PRESENTED = 'PRESENTED';
    public const STATUS_CLEARED = 'CLEARED';
    public const STATUS_BOUNCED = 'BOUNCED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'bank_account_id',
        'cheque_number',
        'issued_to',
        'amount',
        'issue_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'string',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function isCleared(): bool
    {
        return $this->status === self::STATUS_CLEARED;
    }

    public function isBounced(): bool
    {
        return $this->status === self::STATUS_BOUNCED;
    }

    public function markCleared(): void
    {
        $this->update(['status' => self::STATUS_CLEARED]);
    }

    public function markBounced(): void
    {
        $this->update(['status' => self::STATUS_BOUNCED]);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
