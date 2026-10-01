<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const METHOD_CASH = 'CASH';
    public const METHOD_CARD = 'CARD';
    public const METHOD_JAZZCASH = 'JAZZCASH';
    public const METHOD_EASYPAISA = 'EASYPAISA';
    public const METHOD_RAAST = 'RAAST';
    public const METHOD_VITAL_CARD = 'VITAL_CARD';
    public const METHOD_FLEET_CARD = 'FLEET_CARD';
    public const METHOD_CHEQUE = 'CHEQUE';
    public const METHOD_CREDIT = 'CREDIT';
    public const METHOD_BANK = 'BANK';
    public const METHOD_WALLET = 'WALLET';

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
            self::METHOD_CASH => 'Cash (نقد)',
            self::METHOD_CARD => 'Card / POS Machine (کارڈ)',
            self::METHOD_JAZZCASH => 'JazzCash (جاز کیش)',
            self::METHOD_EASYPAISA => 'Easypaisa (ایزی پیسہ)',
            self::METHOD_RAAST => 'Raast (راست)',
            self::METHOD_VITAL_CARD => 'Vital OMC Fuel Card (وائٹل کارڈ)',
            self::METHOD_FLEET_CARD => 'Fleet Card — OMC/PSO (فلیٹ کارڈ)',
            self::METHOD_CHEQUE => 'Cheque (چیک)',
            self::METHOD_CREDIT => 'Udhaar / Credit (ادھار کھاتہ)',
            self::METHOD_BANK => 'Bank Transfer (بینک ٹرانسفر)',
        ];
    }
}
