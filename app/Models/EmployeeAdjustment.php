<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdjustment extends Model
{
    public const TYPE_OVERTIME = 'OVERTIME';
    public const TYPE_BONUS = 'BONUS';
    public const TYPE_FINE = 'FINE';
    public const TYPE_SHORTAGE_RECOVERY = 'SHORTAGE_RECOVERY';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'type',
        'amount',
        'effective_date',
        'payroll_month',
        'shift_id',
        'reason',
        'is_applied',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'effective_date' => 'date',
            'is_applied' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAddition(): bool
    {
        return in_array($this->type, [self::TYPE_OVERTIME, self::TYPE_BONUS], true);
    }

    public function isDeduction(): bool
    {
        return in_array($this->type, [self::TYPE_FINE, self::TYPE_SHORTAGE_RECOVERY], true);
    }
}
