<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_code',
        'bank_name',
        'bank_name_ur',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
}
