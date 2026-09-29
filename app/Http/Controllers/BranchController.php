<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('branches.index', [
            'branches' => Branch::query()
                ->withCount('users')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('branches.create', ['branch' => new Branch(['status' => 'ACTIVE'])]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = Branch::create($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('success', "Branch '{$branch->name}' created.");
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', ['branch' => $branch]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('success', "Branch '{$branch->name}' updated.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        // A branch that already owns transactions must never be hard-deleted.
        if ($branch->users()->exists()) {
            return back()->with(
                'error',
                "Branch '{$branch->name}' still has users assigned. Reassign them or set the branch to Inactive instead."
            );
        }

        $name = $branch->name;
        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with('success', "Branch '{$name}' deleted.");
    }
}
