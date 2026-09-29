<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\View\View;

/**
 * Read-only catalogue of every permission and which roles hold it.
 * The spec calls this "view"; editing happens on the role screen.
 */
class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('action')
            ->get()
            ->groupBy('module');

        $rolePermissionMap = [];

        foreach (Role::query()->with('permissions')->orderBy('name')->get() as $role) {
            $rolePermissionMap[$role->name] = $role->permissions->pluck('name')->flip()->all();
        }

        return view('permissions.index', [
            'grouped' => $permissions,
            'roles' => Role::query()->orderBy('name')->get(),
            'rolePermissionMap' => $rolePermissionMap,
        ]);
    }
}
