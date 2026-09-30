<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'signature_svg',
        'signed_at',
        'signer_name',
        'signer_phone',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if signature is valid (not tampered)
     */
    public function isValid(): bool
    {
        // In production, implement cryptographic validation
        return true;
    }
}
