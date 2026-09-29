<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftCash extends Model
{
    use HasFactory;

    public const TYPE_FLOAT_ADDITION = 'FLOAT_ADDITION';
    public const TYPE_DROP = 'DROP';
    public const TYPE_HANDOVER = 'HANDOVER';
    public const TYPE_EXPENSE_PAYOUT = 'EXPENSE_PAYOUT';

    protected $table = 'shift_cash';

    protected $fillable = [
        'shift_id',
        'user_id',
        'type',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_FLOAT_ADDITION => 'Float Addition',
            self::TYPE_DROP => 'Cash Drop',
            self::TYPE_HANDOVER => 'Handover',
            self::TYPE_EXPENSE_PAYOUT => 'Expense Payout',
            default => $this->type,
        };
    }
}
