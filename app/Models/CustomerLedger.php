<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedger extends Model
{
    protected $table = 'customer_ledger';

    public const TYPE_DEBIT = 'DEBIT';   // Increases customer debt (Sale, Cheque Bounce, Fine)
    public const TYPE_CREDIT = 'CREDIT'; // Decreases customer debt (Payment, Settlement, Cheque Received)

    protected $fillable = [
        'branch_id',
        'customer_id',
        'date',
        'reference_type',
        'reference_id',
        'description',
        'debit',
        'credit',
        'running_balance',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
