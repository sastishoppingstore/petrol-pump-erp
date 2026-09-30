<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\FuelProduct;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tank;
use App\Services\Purchase\PurchaseService;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $supplierId = $request->query('supplier_id');
        $shortageOnly = $request->boolean('shortage_only');

        $query = Purchase::query()
            ->with(['supplier', 'tank', 'fuelProduct', 'creator', 'approver'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($shortageOnly, fn ($q) => $q->where('shortage_claimed', true))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        $purchases = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->get();

        $totalPurchasedValue = Money::round(Purchase::where('status', Purchase::STATUS_APPROVED)->sum('total_amount'));
        $totalLitresReceived = Quantity::round(Purchase::where('status', Purchase::STATUS_APPROVED)->sum('volume_received'));
        $pendingApprovalsCount = Purchase::where('status', Purchase::STATUS_RECEIVED)->count();
        $activeShortagesCount = Purchase::where('shortage_claimed', true)->where('shortage_claim_status', 'PENDING')->count();

        return view('purchases.index', compact(
            'purchases',
            'suppliers',
            'status',
            'supplierId',
            'shortageOnly',
            'totalPurchasedValue',
            'totalLitresReceived',
            'pendingApprovalsCount',
            'activeShortagesCount'
        ));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->get();
        $fuelProducts = FuelProduct::where('status', FuelProduct::STATUS_ACTIVE)->get();
        $tanks = Tank::where('status', Tank::STATUS_ACTIVE)->with('fuelProduct')->get();

        return view('purchases.create', compact('suppliers', 'fuelProducts', 'tanks'));
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['branch_id'] = session('active_branch_id') ?? 1;

        if ($request->hasFile('bill_photo')) {
            $path = $request->file('bill_photo')->store('purchases/bills', 'public');
            $data['bill_photo_path'] = $path;
        }

        $purchase = $this->purchaseService->createPurchase($data, auth()->id());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', "Fuel Purchase #{$purchase->purchase_number} recorded. Please verify decantation and click Approve.");
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'tank', 'fuelProduct', 'items', 'creator', 'approver']);

        return view('purchases.show', compact('purchase'));
    }

    public function approve(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->purchaseService->approve($purchase, auth()->id());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', "Purchase #{$purchase->purchase_number} approved! Tank stock updated, supplier credited, and average cost updated.");
    }

    public function resolveShortageClaim(Request $request, Purchase $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:APPROVED,SETTLED,REJECTED'],
            'notes' => ['nullable', 'string', 'max:500'],
            'debit_supplier' => ['nullable', 'boolean'],
        ]);

        $this->purchaseService->resolveShortageClaim(
            purchase: $purchase,
            status: $validated['status'],
            notes: $validated['notes'] ?? null,
            debitSupplier: $request->boolean('debit_supplier'),
            userId: auth()->id(),
        );

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', "Shortage claim updated to {$validated['status']}.");
    }
}
