<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Security\BranchScopeService;
use App\Support\PermissionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->branchA = Branch::factory()->create(['name' => 'Branch Alpha', 'code' => 'ALPHA']);
        $this->branchB = Branch::factory()->create(['name' => 'Branch Beta', 'code' => 'BETA']);
    }

    /**
     * A non-admin user assigned only to the given branches.
     */
    private function scopedUser(Branch ...$branches): User
    {
        $role = Role::create([
            'name' => 'SCOPED_'.strtoupper(uniqid('', false)),
            'label' => 'Scoped',
            'status' => 'ACTIVE',
        ]);

        $role->permissions()->sync(
            Permission::whereIn('name', [
                PermissionList::BRANCH_VIEW,
                PermissionList::BRANCH_CREATE,
            ])->pluck('id')->all()
        );

        $user = User::factory()->withRole($role->name)->create();

        foreach ($branches as $index => $branch) {
            $user->branches()->attach($branch->id, ['is_default' => $index === 0]);
        }

        return $user->fresh(['roles', 'branches']);
    }

    public function test_scoped_user_only_sees_assigned_branches(): void
    {
        $user = $this->scopedUser($this->branchA);

        $accessible = $user->accessibleBranchIds();

        $this->assertSame([$this->branchA->id], $accessible);
        $this->assertTrue($user->canAccessBranch($this->branchA->id));
        $this->assertFalse($user->canAccessBranch($this->branchB->id));
    }

    public function test_super_admin_is_not_branch_scoped(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        $this->assertSame([], $admin->accessibleBranchIds(), 'Empty means "all branches".');
        $this->assertTrue($admin->canAccessBranch($this->branchA->id));
        $this->assertTrue($admin->canAccessBranch($this->branchB->id));
    }

    public function test_scope_service_filters_a_query_to_assigned_branches(): void
    {
        $user = $this->scopedUser($this->branchA);
        $service = app(BranchScopeService::class);

        $query = Branch::query();
        $service->apply($query, $user, 'branches.id');

        $ids = $query->pluck('branches.id')->all();

        $this->assertSame([$this->branchA->id], $ids);
        $this->assertNotContains($this->branchB->id, $ids);
    }

    public function test_scope_service_leaves_admin_query_untouched(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();
        $service = app(BranchScopeService::class);

        $query = Branch::query();
        $service->apply($query, $admin);

        $this->assertCount(2, $query->get());
    }

    public function test_scope_service_returns_nothing_for_unauthenticated_request(): void
    {
        $service = app(BranchScopeService::class);

        $query = Branch::query();
        $service->apply($query, null);

        $this->assertCount(0, $query->get(), 'An unauthenticated query must never leak data.');
    }

    // ---------------------------------------------------------------
    // Branch switcher
    // ---------------------------------------------------------------

    public function test_user_can_switch_to_an_assigned_branch(): void
    {
        $user = $this->scopedUser($this->branchA, $this->branchB);

        $this->actingAs($user)
            ->post('/branch/switch', ['branch_id' => $this->branchB->id])
            ->assertRedirect();

        $this->assertSame($this->branchB->id, session('active_branch_id'));
    }

    public function test_user_cannot_switch_to_an_unassigned_branch(): void
    {
        $user = $this->scopedUser($this->branchA);

        $this->actingAs($user)
            ->post('/branch/switch', ['branch_id' => $this->branchB->id])
            ->assertSessionHasErrors('branch_id');

        $this->assertNull(session('active_branch_id'));
    }

    public function test_non_admin_cannot_select_all_branches(): void
    {
        $user = $this->scopedUser($this->branchA);

        $this->actingAs($user)
            ->post('/branch/switch', ['branch_id' => ''])
            ->assertSessionHasErrors('branch_id');
    }

    public function test_admin_can_select_all_branches(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        $this->actingAs($admin)->post('/branch/switch', ['branch_id' => ''])->assertRedirect();

        $this->assertNull(session('active_branch_id'));
    }

    public function test_active_branch_falls_back_when_session_points_at_a_forbidden_branch(): void
    {
        $user = $this->scopedUser($this->branchA);
        $service = app(BranchScopeService::class);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        // Session still holds Branch B, which this user may no longer access.
        session(['active_branch_id' => $this->branchB->id]);

        $active = $service->activeBranchId($request);

        $this->assertSame(
            $this->branchA->id,
            $active,
            'Must fall back to the default branch and drop the stale session value.'
        );
        $this->assertNull(session('active_branch_id'));
    }

    public function test_branch_switcher_only_offers_assigned_branches(): void
    {
        $user = $this->scopedUser($this->branchA);
        $service = app(BranchScopeService::class);

        $selectable = $service->selectableBranches($user)->pluck('id')->all();

        $this->assertSame([$this->branchA->id], $selectable);
    }

    public function test_branch_switcher_shows_all_branches_to_admin(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        $selectable = app(BranchScopeService::class)->selectableBranches($admin)->pluck('id')->all();

        $this->assertCount(2, $selectable);
    }

    public function test_inactive_branches_are_not_selectable(): void
    {
        $branchC = Branch::factory()->inactive()->create(['name' => 'Closed Branch']);
        $user = $this->scopedUser($this->branchA, $branchC);

        $selectable = app(BranchScopeService::class)->selectableBranches($user)->pluck('id')->all();

        $this->assertSame([$this->branchA->id], $selectable);
    }
}
