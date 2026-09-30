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
        'credit_limit', 'opening_balance', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'is_tax_liable' => 'boolean',
        ];
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
     *
     * Until the ledger tables exist (Phase 6) this falls back to completed
     * credit sales, which is the same figure for a new system.
     */
    public function outstandingBalance(): string
    {
        $opening = Money::n($this->opening_balance);

        if (! \Illuminate\Support\Facades\Schema::hasTable('customer_ledger')) {
            // Fallback before the ledger exists (Phase 6): outstanding is
            // everything sold that has NOT been settled in cash/card/bank/
            // wallet. A CREDIT payment *is* the udhaar, so it must not reduce
            // the balance — only genuine settlements do.
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
                ->where('entry_type', 'DEBIT')
                ->sum('amount')
        );

        $credits = Money::n(
            \Illuminate\Support\Facades\DB::table('customer_ledger')
                ->where('customer_id', $this->id)
                ->where('entry_type', 'CREDIT')
                ->sum('amount')
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
}
