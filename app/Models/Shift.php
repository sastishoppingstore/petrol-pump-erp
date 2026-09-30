<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'shift_number',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'card_settlement',
        'status',
        'approved_by',
        'approved_at',
        'closing_notes',
        'notes',
        'opening_notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'approved_at' => 'datetime',
            // Money: strings, never floats.
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'card_settlement' => 'decimal:2',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->employee_id ? (int) $this->employee_id : null;
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['employee_id'] = $value;
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(ShiftNozzle::class);
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
            default => 'secondary',
        };
    }

    public function durationForHumans(): string
    {
        $end = $this->closed_at ?? now();

        return $this->opened_at?->diffForHumans($end, ['syntax' => 1]) ?? '—';
    }

    /**
     * Is the cash difference outside the configured tolerance?
     */
    public function varianceExceedsThreshold(): bool
    {
        if ($this->cash_difference === null) {
            return false;
        }

        return Money::exceedsTolerance(
            $this->cash_difference,
            Money::n((string) config('erp.shift_variance_threshold', 100))
        );
    }
}
