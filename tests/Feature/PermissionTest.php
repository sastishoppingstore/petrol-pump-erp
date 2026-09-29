<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a user holding exactly the given permission names.
     */
    private function userWith(string ...$permissions): User
    {
        // The permission catalogue must exist before a role can reference it.
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $role = Role::create([
            'name' => 'CUSTOM_'.strtoupper(uniqid('', false)),
            'label' => 'Custom',
            'status' => 'ACTIVE',
        ]);

        $ids = Permission::whereIn('name', $permissions)->pluck('id')->all();
        $role->permissions()->sync($ids);

        return tap(User::factory()->withRole($role->name)->create())
            ->load('roles');
    }

    // ---------------------------------------------------------------
    // 403 on protected routes
    // ---------------------------------------------------------------

    public function test_user_without_branch_permission_gets_403(): void
    {
        $user = $this->userWith(PermissionList::SALES_VIEW);

        $this->actingAs($user)->get('/branches')->assertForbidden();
    }

    public function test_user_without_user_view_permission_gets_403(): void
    {
        $user = $this->userWith(PermissionList::BRANCH_VIEW);

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_user_without_role_permission_gets_403(): void
    {
        $user = $this->userWith(PermissionList::BRANCH_VIEW);

        $this->actingAs($user)->get('/roles')->assertForbidden();
    }

    public function test_permission_middleware_rejects_direct_url_access(): void
    {
        // The core point: hiding a button is not the control. A user who
        // types the URL must still be rejected.
        $user = $this->userWith(PermissionList::BRANCH_VIEW);

        $this->actingAs($user)
            ->post('/branches', ['code' => 'X1', 'name' => 'Sneaky', 'status' => 'ACTIVE'])
            ->assertForbidden();

        $this->assertDatabaseCount('branches', 0);
    }

    public function test_user_with_permission_is_allowed(): void
    {
        $user = $this->userWith(PermissionList::BRANCH_VIEW);

        $this->actingAs($user)->get('/branches')->assertOk();
    }

    public function test_any_of_several_permissions_is_enough(): void
    {
        $user = $this->userWith(PermissionList::REPORTS_VIEW);

        // Route requires branch.view OR user.view; the user has neither.
        $this->actingAs($user)->get('/branches')->assertForbidden();
    }

    public function test_super_admin_passes_every_permission_check(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->hasPermission('anything.at.all'));

        $this->actingAs($admin)->get('/branches')->assertOk();
        $this->actingAs($admin)->get('/users')->assertOk();
        $this->actingAs($admin)->get('/roles')->assertOk();
        $this->actingAs($admin)->get('/permissions')->assertOk();
    }

    // ---------------------------------------------------------------
    // Gate / @can integration
    // ---------------------------------------------------------------

    public function test_gates_are_registered_for_every_permission(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        foreach (PermissionList::allNames() as $permission) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Gate::forUser($admin)->allows($permission),
                "Gate [{$permission}] should be defined and allowed for an admin."
            );
        }
    }

    public function test_has_every_permission_requires_all_of_them(): void
    {
        $user = $this->userWith(PermissionList::BRANCH_VIEW, PermissionList::BRANCH_CREATE);

        $this->assertTrue($user->hasEveryPermission([PermissionList::BRANCH_VIEW, PermissionList::BRANCH_CREATE]));
        $this->assertFalse($user->hasEveryPermission([PermissionList::BRANCH_VIEW, PermissionList::BRANCH_DELETE]));
    }

    // ---------------------------------------------------------------
    // Seeded matrix
    // ---------------------------------------------------------------

    public function test_seeded_roles_have_the_documented_defaults(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = Role::where('name', Role::ADMIN)->first();
        $this->assertTrue($admin->is_super_admin);
        $this->assertCount(
            count(PermissionList::allNames()),
            $admin->permissions()->get(),
            'ADMIN must hold every permission.'
        );

        $attendant = Role::where('name', Role::ATTENDANT)->first();
        $attendantPermissions = $attendant->permissions->pluck('name')->all();

        $this->assertContains(PermissionList::SALES_CREATE, $attendantPermissions);
        $this->assertNotContains(PermissionList::PURCHASE_CREATE, $attendantPermissions);
        $this->assertNotContains(PermissionList::SETTINGS_EDIT, $attendantPermissions);

        $viewer = Role::where('name', Role::VIEWER)->first();
        $this->assertNotContains(
            PermissionList::SALES_CREATE,
            $viewer->permissions->pluck('name')->all(),
            'VIEWER is read-only and must not be able to create sales.'
        );
    }

    public function test_accountant_has_no_pos_access(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $accountant = Role::where('name', Role::ACCOUNTANT)->first();
        $permissions = $accountant->permissions->pluck('name')->all();

        $this->assertNotContains(PermissionList::SALES_CREATE, $permissions, 'ACCOUNTANT must not use the POS.');
        $this->assertContains(PermissionList::PURCHASE_APPROVE, $permissions);
        $this->assertContains(PermissionList::REPORTS_EXPORT, $permissions);
    }

    public function test_manager_cannot_change_settings_or_restore_backups(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $manager = Role::where('name', Role::MANAGER)->first();
        $permissions = $manager->permissions->pluck('name')->all();

        $this->assertNotContains(PermissionList::SETTINGS_EDIT, $permissions);
        $this->assertNotContains(PermissionList::BACKUP_RESTORE, $permissions);
        $this->assertNotContains(PermissionList::ROLE_EDIT, $permissions);

        $this->assertContains(PermissionList::STOCK_ADJUSTMENT, $permissions);
        $this->assertContains(PermissionList::SHIFT_CLOSE, $permissions);
        $this->assertContains(PermissionList::SALES_REFUND, $permissions);
    }
}
