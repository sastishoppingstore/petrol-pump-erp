<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    public const TYPE_METER_CORRECTION = 'METER_CORRECTION';
    public const TYPE_CREDIT_OVERRIDE = 'CREDIT_OVERRIDE';
    public const TYPE_CASH_SHORTAGE = 'CASH_SHORTAGE';
    public const TYPE_LARGE_CASHOUT = 'LARGE_CASHOUT';
    public const TYPE_STOCK_ADJUSTMENT = 'STOCK_ADJUSTMENT';
    public const TYPE_EXPENSE = 'EXPENSE';
    public const TYPE_ADVANCE = 'ADVANCE';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    public const OWNER_WHATSAPP_PHONE = '923004342343';

    protected $fillable = [
        'branch_id',
        'request_type',
        'title',
        'description',
        'amount',
        'reference_type',
        'reference_id',
        'payload',
        'status',
        'requested_by',
        'actioned_by',
        'actioned_at',
        'action_reason',
        'whatsapp_url',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'actioned_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Generate owner WhatsApp notification link.
     */
    public function generateWhatsAppUrl(): string
    {
        $branchName = $this->branch?->name ?? 'Vital Petroleum - Mehar Filling Station';
        $amountStr = $this->amount ? 'Rs. ' . number_format((float) $this->amount, 2) : 'N/A';
        $requesterName = $this->requester?->name ?? 'Staff';

        $text = "🚨 *APPROVAL REQUEST* - Vital Petroleum\n"
              . "📍 *Branch:* {$branchName}\n"
              . "📋 *Type:* {$this->request_type}\n"
              . "📝 *Title:* {$this->title}\n"
              . "💰 *Amount:* {$amountStr}\n"
              . "👤 *Requested By:* {$requesterName}\n"
              . "ℹ️ *Details:* {$this->description}";

        return 'https://wa.me/' . self::OWNER_WHATSAPP_PHONE . '?text=' . urlencode($text);
    }
}
