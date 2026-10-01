<?php

namespace App\Models;

use App\Support\AmountInWords;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_VOID = 'void';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_SALE = 'sale';
    public const TYPE_CREDIT_BILL = 'credit_bill';
    public const TYPE_BULK = 'bulk';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'branch_id',
        'sale_id',
        'customer_id',
        'vehicle_id',
        'shift_id',
        'user_id',
        'template_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'type',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'pos_fee',
        'total_amount',
        'paid_amount',
        'balance_due',
        'previous_balance',
        'closing_balance',
        'payment_method',
        'payment_details',
        'currency',
        'amount_in_words_ur',
        'amount_in_words_en',
        'hash',
        'qr_payload',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'pos_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'payment_details' => 'array',
            'qr_payload' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * The FBR fiscal document for this invoice's sale, if fiscalised.
     *
     * FBR documents hang off the sale (fbr_invoices.sale_id), not off the
     * invoice row, so the join is sale_id = sale_id. Views may rely on:
     * $invoice->fbrInvoice?->fiscal_number and
     * $invoice->fbrInvoice?->qr_payload (array-cast JSON).
     */
    public function fbrInvoice(): HasOne
    {
        return $this->hasOne(FbrInvoice::class, 'sale_id', 'sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InvoiceTemplate::class, 'template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(InvoiceSnapshot::class);
    }

    public function getVerificationUrlAttribute(): string
    {
        return route('invoice.verify', ['hash' => $this->hash]);
    }

    public function getFormattedTotalAttribute(): string
    {
        return AmountInWords::formatLakh($this->total_amount, true, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return AmountInWords::formatLakh($this->subtotal, true, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || Money::compare($this->balance_due, '0.00') <= 0;
    }

    public function isCredit(): bool
    {
        return $this->payment_method === 'credit' || Money::compare($this->balance_due, '0.00') > 0;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('balance_due', '>', 0);
    }
}
