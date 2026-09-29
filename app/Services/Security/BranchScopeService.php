<?php

namespace App\Services\Security;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Branch scoping (spec section 5).
 *
 * Every station-owned query must pass through here so a manager or cashier
 * can only ever see the branches they are assigned to. A super admin is not
 * scoped at all.
 *
 * Empty `accessibleBranchIds()` means "all branches" and deliberately leaves
 * the query untouched, rather than returning an impossible filter.
 */
class BranchScopeService
{
    /**
     * The branch id the user is currently operating in, if any.
     */
    public function activeBranchId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $sessionKey = 'active_branch_id';
        $requested = Session::get($sessionKey);

        if ($requested !== null && $user->canAccessBranch((int) $requested)) {
            return (int) $requested;
        }

        // Session points at a branch the user no longer has access to.
        if ($requested !== null) {
            Session::forget($sessionKey);
        }

        return $user->defaultBranch()?->id;
    }

    /**
     * Restrict a branch-owned query to the branches this user may see.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, ?User $user, string $column = 'branch_id'): Builder
    {
        if (! $user) {
            // Never leak data: an unauthenticated query returns nothing.
            return $query->whereRaw('1 = 0');
        }

        $ids = $user->accessibleBranchIds();

        if ($ids === []) {
            return $query;
        }

        return $query->whereIn($column, $ids);
    }

    /**
     * Branch ids this user may switch between in the top bar.
     *
     * @return array<int, Branch>
     */
    public function selectableBranches(User $user)
    {
        if ($user->isSuperAdmin()) {
            return Branch::query()->where('status', Branch::STATUS_ACTIVE)->orderBy('name')->get();
        }

        return $user->branches()
            ->where('branches.status', Branch::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
    }
}
