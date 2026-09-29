<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\Tank;
use App\Models\TankMovement;
use App\Services\Security\BranchScopeService;
use App\Services\Stock\StockAdjustmentService;
use App\Services\Stock\StockService;
use App\Support\Quantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly StockAdjustmentService $adjustments,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Tank stock overview with the expected/physical/variance position.
     */
    public function index(Request $request): View
    {
        $query = Tank::query()->with(['fuelProduct', 'branch'])->orderBy('branch_id')->orderBy('tank_number');
        $this->branchScope->apply($query, $request->user());

        $tanks = $query->get()->map(function (Tank $tank) {
            $expected = $this->stock->expected($tank->id);
            $current = Quantity::n($tank->current_stock);
            $lastPhysical = $tank->readings()
                ->orderByDesc('reading_date')
                ->orderByDesc('id')
                ->first();

            return [
                'tank' => $tank,
                'expected' => $expected,
                'drift' => Quantity::subtract($current, $expected),
                'lastPhysical' => $lastPhysical?->physical_quantity,
                'variance' => $lastPhysical
                    ? Quantity::subtract(Quantity::n($lastPhysical->physical_quantity), $expected)
                    : null,
            ];
        });

        return view('stock.index', ['rows' => $tanks]);
    }

    /**
     * Movement ledger with filters.
     */
    public function movements(Request $request): View
    {
        $query = TankMovement::query()
            ->with(['tank', 'fuelProduct', 'user', 'branch']);

        $this->branchScope->apply($query, $request->user());

        $query
            ->when($request->filled('tank_id'), fn ($q) => $q->where('tank_id', $request->input('tank_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('id');

        $movements = $query->paginate(40)->withQueryString();

        $tankQuery = Tank::query()->orderBy('tank_number');
        $this->branchScope->apply($tankQuery, $request->user());

        return view('stock.movements', [
            'movements' => $movements,
            'tanks' => $tankQuery->get(),
            'types' => [
                StockService::TYPE_PURCHASE, StockService::TYPE_SALE,
                StockService::TYPE_ADJUSTMENT_IN, StockService::TYPE_ADJUSTMENT_OUT,
                StockService::TYPE_TRANSFER_IN, StockService::TYPE_TRANSFER_OUT,
                StockService::TYPE_LOSS, StockService::TYPE_CORRECTION,
            ],
        ]);
    }

    // ---------------------------------------------------------------
    // Adjustments
    // ---------------------------------------------------------------

    public function adjustments(Request $request): View
    {
        $query = StockAdjustment::query()->with(['tank', 'fuelProduct', 'requester', 'approver']);

        $this->branchScope->apply($query, $request->user());

        $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id');

        return view('stock.adjustments', [
            'adjustments' => $query->paginate(25)->withQueryString(),
            'tanks' => $this->scopedTanks($request->user()),
        ]);
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],
            'type' => ['required', 'in:IN,OUT,LOSS,CORRECTION'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tank = Tank::findOrFail($data['tank_id']);

        if (! $request->user()->canAccessBranch((int) $tank->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $adjustment = $this->adjustments->request(
                tank: $tank,
                type: $data['type'],
                quantity: (string) $data['quantity'],
                reason: $data['reason'],
                userId: $request->user()->id,
                notes: $data['notes'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', "Adjustment {$adjustment->reference_number} submitted for approval.");
    }

    public function approveAdjustment(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        if (! $request->user()->canAccessBranch((int) $adjustment->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $this->adjustments->approve($adjustment, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Adjustment {$adjustment->reference_number} approved and stock updated.");
    }

    public function rejectAdjustment(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        if (! $request->user()->canAccessBranch((int) $adjustment->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $this->adjustments->reject($adjustment, $request->user()->id, $data['rejection_reason']);

        return back()->with('success', "Adjustment {$adjustment->reference_number} rejected.");
    }

    private function scopedTanks(?\App\Models\User $user)
    {
        $query = Tank::query()->with('fuelProduct')->orderBy('tank_number');
        $this->branchScope->apply($query, $user);

        return $query->get();
    }
}
