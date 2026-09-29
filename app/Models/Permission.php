<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'module',
        'action',
        'label',
        'description',
    ];

    /**
     * Permissions are referenced as plain "module.action" strings all over the
     * app (gates, middleware, Blade). This lookup keeps a name -> model map
     * for the places that need the row (e.g. the roles screen).
     *
     * @return array<string, int>
     */
    public static function idMap(): array
    {
        return static::query()->pluck('id', 'name')->all();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }
}
