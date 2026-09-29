<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()
                ->with('roles')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(['status' => User::STATUS_ACTIVE]),
            'roles' => Role::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'branches' => \App\Models\Branch::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        try {
            $user = DB::transaction(function () use ($request) {
                $data = $request->validated();

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'employee_code' => $data['employee_code'] ?? null,
                    'password' => $data['password'],
                    'status' => $data['status'],
                    'password_changed_at' => now(),
                ]);

                $user->roles()->sync($data['roles']);
                $this->syncBranches($user, $data['branches'] ?? []);

                return $user;
            });
        } catch (Throwable $e) {
            Log::error('User creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to create the user. No changes were saved.');
        }

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$user->name}' created.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'branches' => \App\Models\Branch::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'selectedRoles' => $user->roles->pluck('id')->all(),
            'selectedBranches' => $user->branches->pluck('id')->all(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $user) {
                $data = $request->validated();

                $user->fill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'employee_code' => $data['employee_code'] ?? null,
                    'status' => $data['status'],
                ]);

                if (! empty($data['password'])) {
                    $user->password = $data['password'];
                    $user->password_changed_at = now();
                }

                $user->save();

                $user->roles()->sync($data['roles']);
                $this->syncBranches($user, $data['branches'] ?? []);
            });
        } catch (Throwable $e) {
            Log::error('User update failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Unable to update the user. No changes were saved.');
        }

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$user->name}' updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$name}' deleted.");
    }

    /**
     * @param  array<int, int>  $branchIds
     */
    private function syncBranches(User $user, array $branchIds): void
    {
        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));

        $sync = [];

        foreach ($branchIds as $branchId) {
            $sync[$branchId] = ['is_default' => $branchId === ($branchIds[0] ?? null)];
        }

        $user->branches()->sync($sync);
    }
}
