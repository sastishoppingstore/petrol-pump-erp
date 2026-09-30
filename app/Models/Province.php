<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    // Tax authorities. Fuel is taxed by the provincial body, not FBR.
    public const PRA = 'PRA';
    public const SRB = 'SRB';
    public const KPRA = 'KPRA';
    public const BRA = 'BRA';

    protected $table = 'provinces';

    protected $fillable = ['code', 'name', 'tax_authority', 'authority_name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }
}
