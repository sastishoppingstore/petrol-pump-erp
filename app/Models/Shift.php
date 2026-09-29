<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';

    protected $fillable = [
        'branch_id',
        'user_id',
        'shift_number',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'card_total',
        'credit_total',
        'other_total',
        'total_sales',
        'total_litres',
        'expenses_total',
        'cash_drops_total',
        'status',
        'approved_by',
        'approved_at',
        'opening_notes',
        'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'approved_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'card_total' => 'decimal:2',
            'credit_total' => 'decimal:2',
            'other_total' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_litres' => 'decimal:3',
            'expenses_total' => 'decimal:2',
            'cash_drops_total' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shiftNozzles(): HasMany
    {
        return $this->hasMany(ShiftNozzle::class);
    }

    public function shiftCash(): HasMany
    {
        return $this->hasMany(ShiftCash::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'success',
            self::STATUS_CLOSED => 'secondary',
            self::STATUS_PENDING_APPROVAL => 'warning',
            default => 'dark',
        };
    }
}
