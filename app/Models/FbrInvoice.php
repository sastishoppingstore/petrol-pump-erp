<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbrInvoice extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'fbr_invoices';

    /**
     * `attempts` and the lifecycle fields are writable because only
     * FbrInvoiceService drives this record's state. Without them here a
     * create/update would silently discard them.
     */
    protected $fillable = [
        'branch_id', 'sale_id', 'pos_branch_code', 'fiscal_number',
        'qr_payload', 'buyer_ntn', 'buyer_cnic', 'buyer_name',
        'buyer_details_required', 'total_amount', 'tax_amount',
        'discount_amount', 'pos_service_fee', 'status',
        'submitted_at', 'fbr_response', 'error_message', 'attempts', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'qr_payload' => 'array',
            'buyer_details_required' => 'boolean',
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'pos_service_fee' => 'decimal:2',
            'submitted_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
}
