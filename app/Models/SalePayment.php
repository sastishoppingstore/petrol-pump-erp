<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const METHOD_CASH = 'CASH';
    public const METHOD_CARD = 'CARD';
    public const METHOD_BANK = 'BANK';
    public const METHOD_WALLET = 'WALLET';
    public const METHOD_CREDIT = 'CREDIT';

    protected $fillable = ['sale_id', 'method', 'amount', 'reference'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public static function methods(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_CARD => 'Card',
            self::METHOD_BANK => 'Bank Transfer',
            self::METHOD_WALLET => 'Mobile Wallet',
            self::METHOD_CREDIT => 'Credit (udhaar)',
        ];
    }
}
