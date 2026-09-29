<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::query()
                ->withCount('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'role' => new Role(['status' => 'ACTIVE']),
            'matrix' => PermissionList::allGrouped(),
            'selected' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $role = Role::create([
                    'name' => $data['name'],
                    'label' => $data['label'],
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'],
                    'is_super_admin' => false,
                ]);

                $role->syncPermissions($data['permissions'] ?? []);
            });
        } catch (Throwable $e) {
            Log::error('Role creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to create the role. No changes were saved.');
        }

        return redirect()->route('roles.index')->with('success', "Role '{$data['label']}' created.");
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role,
            'matrix' => PermissionList::allGrouped(),
            'selected' => $role->permissions()->pluck('name')->all(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();

        // The six seeded roles are fixed by design (spec section 5) — their
        // names and super-admin flag must not drift.
        $isBuiltIn = array_key_exists($role->name, PermissionList::builtInRoles());

        if ($isBuiltIn && $data['name'] !== $role->name) {
            return back()->with('error', 'Built-in role names cannot be changed.');
        }

        if ($role->is_super_admin && $data['permissions'] !== PermissionList::allNames()) {
            return back()->with('error', 'The Administrator role always holds every permission.');
        }

        try {
            DB::transaction(function () use ($data, $role) {
                $role->update([
                    'name' => $data['name'],
                    'label' => $data['label'],
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'],
                ]);

                $role->syncPermissions($data['permissions'] ?? []);
            });
        } catch (Throwable $e) {
            Log::error('Role update failed', ['role_id' => $role->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Unable to update the role. No changes were saved.');
        }

        return redirect()->route('roles.index')->with('success', "Role '{$role->label}' updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_super_admin) {
            return back()->with('error', 'The Administrator role cannot be deleted.');
        }

        if (array_key_exists($role->name, PermissionList::builtInRoles())) {
            return back()->with('error', "Built-in role '{$role->label}' cannot be deleted. Set it to Inactive instead.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', "Role '{$role->label}' is still assigned to users. Reassign them first.");
        }

        $label = $role->label;
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$label}' deleted.");
    }
}
