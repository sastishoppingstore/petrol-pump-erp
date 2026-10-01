<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoidSaleRequest;
use App\Models\Sale;
use App\Services\Sale\SaleVoidService;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleVoidController extends Controller
{
    public function __construct(
        private readonly SaleVoidService $voids,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function edit(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        return view('sales.void', [
            'sale' => $sale->load(['items.fuelProduct', 'payments', 'customer', 'employee', 'shift']),
        ]);
    }

    public function update(VoidSaleRequest $request, Sale $sale, SaleVoidService $voids): RedirectResponse
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $asRefund = $request->boolean('as_refund');

            $result = $voids->void(
                sale: $sale,
                reason: (string) $request->validated('reason'),
                actorId: $request->user()->id,
                asRefund: $asRefund,
                managerPin: $request->filled('manager_pin') ? (string) $request->input('manager_pin') : null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('sales.index')
            ->with('success', sprintf(
                'Sale %s %s. Stock, meter and cash have been reversed.',
                $result->invoice_number,
                strtolower($result->status),
            ));
    }
}
