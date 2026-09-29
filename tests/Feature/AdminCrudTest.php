<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = User::factory()->withRole(Role::ADMIN)->create();
    }

    private function validBranchData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'BR-01',
            'name' => 'Main Station',
            'address' => 'GT Road',
            'city' => 'Lahore',
            'status' => 'ACTIVE',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Branches
    // ---------------------------------------------------------------

    public function test_admin_can_view_branch_list(): void
    {
        Branch::factory()->create(['name' => 'Branch Alpha', 'code' => 'ALPHA']);
        Branch::factory()->create(['name' => 'Branch Beta', 'code' => 'BETA']);

        $this->actingAs($this->admin)
            ->get('/branches')
            ->assertOk()
            ->assertSee('Branch Alpha')
            ->assertSee('Branch Beta')
            ->assertSee('ALPHA')
            ->assertSee('BETA');
    }

    public function test_branch_list_renders_when_empty(): void
    {
        $this->actingAs($this->admin)
            ->get('/branches')
            ->assertOk()
            ->assertSee('No branches yet');
    }

    public function test_admin_can_create_a_branch(): void
    {
        $this->actingAs($this->admin)
            ->post('/branches', $this->validBranchData())
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['code' => 'BR-01', 'name' => 'Main Station']);
    }

    public function test_branch_code_is_unique(): void
    {
        Branch::factory()->create(['code' => 'BR-01']);

        $this->actingAs($this->admin)
            ->post('/branches', $this->validBranchData())
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('branches', 1);
    }

    public function test_branch_code_must_be_alphanumeric(): void
    {
        $this->actingAs($this->admin)
            ->post('/branches', $this->validBranchData(['code' => 'bad code!']))
            ->assertSessionHasErrors('code');
    }

    public function test_branch_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post('/branches', $this->validBranchData(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_a_branch(): void
    {
        $branch = Branch::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put('/branches/'.$branch->id, $this->validBranchData([
                'code' => $branch->code,
                'name' => 'New Name',
                'status' => 'ACTIVE',
            ]))
            ->assertRedirect(route('branches.index'));

        $this->assertSame('New Name', $branch->fresh()->name);
    }

    public function test_branch_with_users_cannot_be_deleted(): void
    {
        $branch = Branch::factory()->create();
        $this->admin->branches()->attach($branch->id, ['is_default' => true]);

        $this->actingAs($this->admin)
            ->delete('/branches/'.$branch->id)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_empty_branch_can_be_deleted(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->admin)->delete('/branches/'.$branch->id)->assertRedirect();

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    // ---------------------------------------------------------------
    // Users
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_user_with_roles_and_branches(): void
    {
        $branch = Branch::factory()->create();
        $roleId = Role::where('name', Role::CASHIER)->value('id');

        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Ali Cashier',
                'email' => 'ali@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'status' => 'ACTIVE',
                'roles' => [$roleId],
                'branches' => [$branch->id],
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'ali@test.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Ali Cashier', $user->name);
        $this->assertTrue($user->hasRole(Role::CASHIER));
        $this->assertTrue($user->canAccessBranch($branch->id));
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);
        $roleId = Role::where('name', Role::VIEWER)->value('id');

        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Duplicate',
                'email' => 'taken@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'status' => 'ACTIVE',
                'roles' => [$roleId],
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_user_requires_at_least_one_role(): void
    {
        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'No Role',
                'email' => 'norole@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'status' => 'ACTIVE',
                'roles' => [],
            ])
            ->assertSessionHasErrors('roles');
    }

    public function test_user_password_must_be_confirmed(): void
    {
        $roleId = Role::where('name', Role::VIEWER)->value('id');

        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Mismatch',
                'email' => 'mismatch@test.com',
                'password' => 'password123',
                'password_confirmation' => 'different123',
                'status' => 'ACTIVE',
                'roles' => [$roleId],
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_user_password_must_be_at_least_8_characters(): void
    {
        $roleId = Role::where('name', Role::VIEWER)->value('id');

        $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Short',
                'email' => 'short@test.com',
                'password' => 'abc',
                'password_confirmation' => 'abc',
                'status' => 'ACTIVE',
                'roles' => [$roleId],
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_editing_a_user_without_a_password_keeps_the_current_one(): void
    {
        $roleId = Role::where('name', Role::VIEWER)->value('id');

        $user = User::factory()->withRole(Role::CASHIER)->create([
            'email' => 'keep@test.com',
            'password' => 'originalpass123',
        ]);

        $originalHash = $user->password;

        $this->actingAs($this->admin)
            ->put('/users/'.$user->id, [
                'name' => 'Renamed',
                'email' => 'keep@test.com',
                'status' => 'ACTIVE',
                'roles' => [$roleId],
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('Renamed', $user->name);
        $this->assertSame($originalHash, $user->password, 'Password must be unchanged.');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->delete('/users/'.$this->admin->id)
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_user_can_be_disabled(): void
    {
        $user = User::factory()->withRole(Role::VIEWER)->create();

        $this->actingAs($this->admin)
            ->put('/users/'.$user->id, [
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'DISABLED',
                'roles' => [$user->roles->first()->id],
            ])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->isActive());
    }

    // ---------------------------------------------------------------
    // Roles
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_custom_role_with_permissions(): void
    {
        $this->actingAs($this->admin)
            ->post('/roles', [
                'name' => 'AUDITOR',
                'label' => 'Auditor',
                'description' => 'Read only auditor',
                'status' => 'ACTIVE',
                'permissions' => ['sales.view', 'journal.view', 'audit.view'],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'AUDITOR')->first();

        $this->assertNotNull($role);
        $this->assertCount(3, $role->permissions);
        $this->assertFalse($role->is_super_admin);
    }

    public function test_role_name_must_be_uppercase(): void
    {
        $this->actingAs($this->admin)
            ->post('/roles', [
                'name' => 'lowercase role',
                'label' => 'Bad',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_role_name_must_be_unique(): void
    {
        $this->actingAs($this->admin)
            ->post('/roles', [
                'name' => Role::MANAGER,
                'label' => 'Duplicate',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_role_name_cannot_be_changed_for_built_in_roles(): void
    {
        $role = Role::where('name', Role::CASHIER)->first();

        $this->actingAs($this->admin)
            ->put('/roles/'.$role->id, [
                'name' => 'RENAMED_CASHIER',
                'label' => 'Cashier',
                'status' => 'ACTIVE',
                'permissions' => ['sales.view'],
            ])
            ->assertRedirect();

        $this->assertSame(Role::CASHIER, $role->fresh()->name);
    }

    public function test_admin_role_permissions_cannot_be_reduced(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->first();

        $this->actingAs($this->admin)
            ->put('/roles/'.$adminRole->id, [
                'name' => Role::ADMIN,
                'label' => 'Administrator',
                'status' => 'ACTIVE',
                'permissions' => ['sales.view'],
            ])
            ->assertRedirect();

        $this->assertGreaterThan(
            1,
            $adminRole->fresh()->permissions()->count(),
            'The Administrator role must always keep every permission.'
        );
    }

    public function test_built_in_roles_cannot_be_deleted(): void
    {
        $role = Role::where('name', Role::VIEWER)->first();

        $this->actingAs($this->admin)->delete('/roles/'.$role->id)->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => Role::VIEWER]);
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'TEMP_ROLE', 'label' => 'Temp', 'status' => 'ACTIVE']);
        User::factory()->withRole('TEMP_ROLE')->create();

        $this->actingAs($this->admin)->delete('/roles/'.$role->id)->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => 'TEMP_ROLE']);
    }

    public function test_unused_custom_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'ORPHAN_ROLE', 'label' => 'Orphan', 'status' => 'ACTIVE']);

        $this->actingAs($this->admin)->delete('/roles/'.$role->id)->assertRedirect();

        $this->assertDatabaseMissing('roles', ['name' => 'ORPHAN_ROLE']);
    }

    // ---------------------------------------------------------------
    // Permissions catalogue
    // ---------------------------------------------------------------

    public function test_permission_catalogue_shows_the_matrix(): void
    {
        $this->actingAs($this->admin)
            ->get('/permissions')
            ->assertOk()
            ->assertSee('sales.create')
            ->assertSee('Sales');
    }
}
