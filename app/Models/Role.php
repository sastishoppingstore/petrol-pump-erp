<?php

namespace App\Models;

use App\Support\PermissionList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const ADMIN = 'ADMIN';
    public const MANAGER = 'MANAGER';
    public const CASHIER = 'CASHIER';
    public const ATTENDANT = 'ATTENDANT';
    public const ACCOUNTANT = 'ACCOUNTANT';
    public const VIEWER = 'VIEWER';

    protected $fillable = [
        'name',
        'label',
        'description',
        'is_super_admin',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * Replace this role's permission set with the given permission names.
     *
     * @param  array<int, string>  $names  e.g. ["sales.create", "sales.view"]
     */
    public function syncPermissions(array $names): void
    {
        $ids = Permission::query()->whereIn('name', $names)->pluck('id');

        $this->permissions()->sync($ids);
    }

    /**
     * Default permission names for the built-in roles (spec section 5).
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsFor(string $roleName): array
    {
        return PermissionList::forRole($roleName);
    }
}
