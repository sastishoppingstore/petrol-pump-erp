<?php

namespace App\Http\Controllers;

use App\Services\Security\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Branch switcher in the top bar. A user can only ever select a branch they
 * are actually assigned to.
 */
class BranchSwitchController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Empty means "all branches", which only a super admin may choose.
            'branch_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $branchId = $validated['branch_id'] ?? null;

        if ($branchId === null) {
            if (! $request->user()->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'branch_id' => 'You are not allowed to view all branches.',
                ]);
            }

            $request->session()->forget('active_branch_id');

            return back()->with('success', 'Showing all branches.');
        }

        if (! $request->user()->canAccessBranch((int) $branchId)) {
            throw ValidationException::withMessages([
                'branch_id' => 'You do not have access to that branch.',
            ]);
        }

        $request->session()->put('active_branch_id', (int) $branchId);

        return back()->with('success', 'Branch switched.');
    }
}
