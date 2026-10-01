<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    use HasFactory;

    public const TYPE_RECEIVED = 'RECEIVED';
    public const TYPE_ISSUED = 'ISSUED';

    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_DEPOSITED = 'DEPOSITED';
    public const STATUS_CLEARED = 'CLEARED';
    public const STATUS_BOUNCED = 'BOUNCED';
    public const STATUS_CANCELLED = 'CANCELLED';

    // Legacy aliases — sirf dormant BankingService inhe istemal karta hai;
    // live cheque flow (ChequeService/ChequeController) upar wale canonical
    // constants use karta hai jo migration 007600 ke enums se match karte hain.
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PRESENTED = 'PRESENTED';

    protected $fillable = [
        'branch_id',
        'type',
        'cheque_number',
        'bank_name',
        'bank_account_id',
        'customer_id',
        'supplier_id',
        'payee_name',
        'amount',
        'cheque_date',
        'due_date',
        'is_pdc',
        'status',
        'deposit_date',
        'cleared_date',
        'bounced_date',
        'bounce_reason',
        'bank_charges',
        'image_path',
        'notes',
        'created_by',
        'actioned_by',
    ];

    protected $casts = [
        'amount' => 'string',
        'bank_charges' => 'string',
        'is_pdc' => 'boolean',
        'cheque_date' => 'date',
        'due_date' => 'date',
        'deposit_date' => 'date',
        'cleared_date' => 'date',
        'bounced_date' => 'date',
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
