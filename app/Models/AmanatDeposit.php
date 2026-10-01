<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One append-only Amanat (customer prepaid deposit) ledger entry.
 *
 * `amount` is always stored positive for deposit/deduction; an
 * `adjustment` keeps the sign it was entered with. `balance_after`
 * is the customer's amanat balance immediately after this entry and
 * is never recalculated — corrections are new adjustment rows.
 */
class AmanatDeposit extends Model
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_DEDUCTION = 'deduction';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'branch_id', 'customer_id', 'type', 'amount', 'balance_after',
        'reference', 'sale_id', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Signed effect of this entry on the customer's balance, as a
     * decimal string (deposit +, deduction -, adjustment as stored).
     */
    public function signedAmount(): string
    {
        $amount = Money::round((string) $this->amount);

        return $this->type === self::TYPE_DEDUCTION
            ? Money::subtract('0', $amount)
            : $amount;
    }

    /**
     * Current amanat balance of a customer: the balance_after of
     * their latest entry, or 0.00 when they have no entries yet.
     */
    public static function currentBalanceFor(int $customerId): string
    {
        $latest = static::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->value('balance_after');

        return $latest === null ? '0.00' : Money::round((string) $latest);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'Deposit / جمع',
            self::TYPE_DEDUCTION => 'Deduction / کٹوتی',
            self::TYPE_ADJUSTMENT => 'Adjustment / تصحیح',
            default => ucfirst((string) $this->type),
        };
    }
}
