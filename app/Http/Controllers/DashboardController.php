<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 1 dashboard: real counts of what exists in the database.
 * Phase 10 replaces the body with sales/expense/margin widgets and charts.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $branchQuery = Branch::query();
        // The branches table keys on "id", not the usual "branch_id" column.
        $this->branchScope->apply($branchQuery, $user, 'branches.id');

        return view('dashboard', [
            'branchCount' => (clone $branchQuery)->where('status', Branch::STATUS_ACTIVE)->count(),
            'userCount' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
            'roleCount' => Role::query()->where('status', 'ACTIVE')->count(),
            'activeBranch' => $this->branchScope->activeBranchId($request),
        ]);
    }
}
