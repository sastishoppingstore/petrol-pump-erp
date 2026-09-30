<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedger extends Model
{
    protected $table = 'supplier_ledger';

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'date',
        'reference_type',
        'reference_id',
        'description',
        'debit',
        'credit',
        'running_balance',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'string',
        'credit' => 'string',
        'running_balance' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
