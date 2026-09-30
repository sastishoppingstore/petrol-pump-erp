<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Immutable snapshot of station identity, customer, rates, payment, and theme at the second of issuance.
 * Ensures historical invoices never alter their legal facts or visual identity if settings change later.
 */
class InvoiceSnapshot extends Model
{
    protected $fillable = [
        'invoice_id',
        'station_snapshot',
        'customer_snapshot',
        'items_snapshot',
        'payment_snapshot',
        'theme_snapshot',
        'raw_snapshot',
        'snapshot_hash',
    ];

    protected function casts(): array
    {
        return [
            'station_snapshot' => 'array',
            'customer_snapshot' => 'array',
            'items_snapshot' => 'array',
            'payment_snapshot' => 'array',
            'theme_snapshot' => 'array',
            'raw_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Enforce absolute immutability: snapshots can NEVER be updated once stored
        static::updating(function (self $snapshot) {
            throw new RuntimeException('Invoice snapshots are immutable and cannot be updated once issued.');
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Compute SHA-256 integrity hash for an immutable payload.
     */
    public static function computeHash(array $payload): string
    {
        ksort($payload);
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Verify whether the snapshot content matches its cryptographic integrity hash.
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->snapshot_hash) || empty($this->raw_snapshot)) {
            return false;
        }

        return hash_equals($this->snapshot_hash, self::computeHash($this->raw_snapshot));
    }
}
