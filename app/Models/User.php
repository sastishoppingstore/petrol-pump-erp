<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DISABLED = 'DISABLED';

    protected $fillable = [
        'employee_code',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'must_change_password',
    ];

    /**
     * Hidden from array/JSON output so the hash and IP never leak.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'last_login_ip',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Roles & permissions
    // -----------------------------------------------------------------

    /**
     * All permission names granted to this user through any of their roles.
     *
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        return $this->roles()
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->distinct()
            ->pluck('permissions.name')
            ->all();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('is_super_admin', true)->exists();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = array_values(array_filter($permissions));

        if ($permissions === []) {
            return false;
        }

        return count(array_intersect($permissions, $this->permissionNames())) > 0;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasAnyPermission([$permission]);
    }

    public function hasEveryPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = array_values(array_filter($permissions));

        if ($permissions === []) {
            return true;
        }

        return array_diff($permissions, $this->permissionNames()) === [];
    }

    // -----------------------------------------------------------------
    // Branch scope
    // -----------------------------------------------------------------

    /**
     * A super admin sees every branch; everyone else only their assigned ones.
     *
     * @return array<int, int> branch ids, empty means "all branches"
     */
    public function accessibleBranchIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        return $this->branches()->pluck('branches.id')->all();
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->branches()->where('branches.id', $branchId)->exists();
    }

    /**
     * The branch the user is currently operating in, if any.
     */
    public function defaultBranch(): ?Branch
    {
        $pivot = $this->branches()
            ->wherePivot('is_default', true)
            ->first();

        return $pivot ?? $this->branches()->first();
    }

    // -----------------------------------------------------------------
    // Status
    // -----------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function roleLabel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Administrator';
        }

        return $this->roles->pluck('label')->join(', ') ?: '—';
    }
}
