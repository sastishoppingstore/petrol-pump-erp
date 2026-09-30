<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleRequest extends Model
{
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'request_token', 'user_id', 'status', 'sale_id',
        'invoice_number', 'ip_address',
    ];

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
