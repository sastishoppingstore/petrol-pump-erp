<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesHistoryController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Sale::query()
            ->with(['customer', 'employee', 'shift'])
            ->withSum('items as litres', 'litres');

        $this->branchScope->apply($query, $request->user());

        $query
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sale_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sale_date', '<=', $request->input('to')))
            ->when($request->filled('invoice'), fn ($q) => $q->where('invoice_number', 'like', '%'.$request->input('invoice').'%'))
            ->when($request->filled('customer'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->input('customer').'%')))
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->input('employee')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('sale_date')
            ->orderByDesc('id');

        return view('sales.index', [
            'sales' => $query->paginate(25)->withQueryString(),
            'employees' => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        return view('sales.show', [
            'sale' => $sale->load(['items.fuelProduct', 'items.nozzle', 'payments', 'customer', 'vehicle', 'employee', 'shift']),
        ]);
    }
}
