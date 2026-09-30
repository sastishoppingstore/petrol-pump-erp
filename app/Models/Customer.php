<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'branch_id', 'code', 'name', 'phone', 'email', 'address',
        'ntn_number', 'cnic', 'is_tax_liable',
        'credit_limit', 'opening_balance', 'current_balance', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_tax_liable' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $customer->code = 'CUST-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * A zero limit means unlimited credit.
     */
    public function creditLimitIsUnlimited(): bool
    {
        return Money::compare(Money::n($this->credit_limit), '0') <= 0;
    }

    /**
     * Outstanding = opening balance + sum of ledger debits - sum of credits.
     */
    public function outstandingBalance(): string
    {
        if (isset($this->attributes['current_balance']) && $this->attributes['current_balance'] !== null) {
            return Money::n($this->attributes['current_balance']);
        }

        $opening = Money::n($this->opening_balance);

        if (! \Illuminate\Support\Facades\Schema::hasTable('customer_ledger')) {
            $sales = Money::n($this->sales()
                ->where('status', Sale::STATUS_COMPLETED)
                ->sum('total'));

            $settled = Money::n(SalePayment::query()
                ->whereIn(
                    'sale_id',
                    $this->sales()
                        ->where('status', Sale::STATUS_COMPLETED)
                        ->select('id')
                )
                ->where('method', '!=', SalePayment::METHOD_CREDIT)
                ->sum('amount'));

            return Money::add($opening, Money::subtract($sales, $settled));
        }

        $debits = Money::n(
            \Illuminate\Support\Facades\DB::table('customer_ledger')
                ->where('customer_id', $this->id)
                ->sum('debit')
        );

        $credits = Money::n(
            \Illuminate\Support\Facades\DB::table('customer_ledger')
                ->where('customer_id', $this->id)
                ->sum('credit')
        );

        return Money::add($opening, Money::subtract($debits, $credits));
    }

    public function availableCredit(): string
    {
        if ($this->creditLimitIsUnlimited()) {
            return '0.00';
        }

        return Money::subtract(
            Money::n($this->credit_limit),
            $this->outstandingBalance()
        );
    }

    /**
     * Check if a proposed credit amount exceeds credit limit.
     */
    public function wouldExceedCreditLimit(string $additionalAmount): bool
    {
        if ($this->creditLimitIsUnlimited()) {
            return false;
        }

        $newTotal = Money::add($this->outstandingBalance(), Money::n($additionalAmount));
        return Money::compare($newTotal, Money::n($this->credit_limit)) > 0;
    }
}
