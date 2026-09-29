<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionList;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seeds the permission catalogue and the six built-in roles with their
     * default matrix (spec section 5). Safe to re-run: it upserts.
     */
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
    }

    private function seedPermissions(): void
    {
        foreach (PermissionList::allGrouped() as $module => $permissions) {
            foreach ($permissions as $name => $label) {
                [$moduleKey, $action] = explode('.', $name, 2);

                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'module' => $moduleKey,
                        'action' => $action,
                        'label' => $label,
                        'description' => "{$label} ({$module})",
                    ]
                );
            }
        }
    }

    private function seedRoles(): void
    {
        foreach (PermissionList::builtInRoles() as $name => $meta) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    // Only ADMIN is a super admin.
                    'is_super_admin' => $name === Role::ADMIN,
                    'status' => 'ACTIVE',
                ]
            );

            $role->syncPermissions(PermissionList::forRole($name));
        }
    }
}
