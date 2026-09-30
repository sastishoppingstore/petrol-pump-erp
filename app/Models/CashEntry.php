<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashEntry extends Model
{
    use HasFactory;

    public const TYPE_CASH_IN = 'CASH_IN';
    public const TYPE_CASH_OUT = 'CASH_OUT';
    public const TYPE_IN = self::TYPE_CASH_IN;
    public const TYPE_OUT = self::TYPE_CASH_OUT;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'branch_id',
        'shift_id',
        'voucher_number',
        'type',
        'category',
        'amount',
        'person_name',
        'reference_no',
        'attachment_path',
        'notes',
        'user_id',
        'status',
        'approved_by',
        'entry_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isCashIn(): bool
    {
        return in_array($this->type, [self::TYPE_CASH_IN, 'IN', 'CASH_IN']);
    }

    public function isCashOut(): bool
    {
        return in_array($this->type, [self::TYPE_CASH_OUT, 'OUT', 'CASH_OUT']);
    }
}
