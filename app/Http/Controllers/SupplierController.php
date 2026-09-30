<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierPaymentRequest;
use App\Http\Requests\SupplierRequest;
use App\Models\BankAccount;
use App\Models\Supplier;
use App\Services\Purchase\PurchaseService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Supplier::query()
            ->withCount('purchases')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('name');

        $suppliers = $query->paginate(20)->withQueryString();

        $totalPayable = Money::round(Supplier::sum('current_balance'));
        $activeSuppliersCount = Supplier::where('status', Supplier::STATUS_ACTIVE)->count();

        return view('suppliers.index', compact(
            'suppliers',
            'totalPayable',
            'activeSuppliersCount',
            'search',
            'status'
        ));
    }

    public function create(): View
    {
        $nextNumber = Supplier::max('id') + 1;
        $suggestedCode = 'SUP-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        return view('suppliers.create', compact('suggestedCode'));
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $openingBalance = Money::round(Money::n($data['opening_balance'] ?? '0.00'));
        $data['opening_balance'] = $openingBalance;
        $data['current_balance'] = $openingBalance;
        $data['branch_id'] = session('active_branch_id');

        $supplier = Supplier::create($data);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', "Supplier {$supplier->name} ({$supplier->code}) created successfully.");
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['purchases' => fn ($q) => $q->latest()->limit(10), 'payments.bankAccount']);

        $ledgerEntries = $supplier->ledgerEntries()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'ledger_page');

        $bankAccounts = BankAccount::where('status', 'ACTIVE')->get();

        return view('suppliers.show', compact('supplier', 'ledgerEntries', 'bankAccounts'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', "Supplier {$supplier->name} updated successfully.");
    }

    public function recordPayment(SupplierPaymentRequest $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validated();
        $data['branch_id'] = session('active_branch_id') ?? $supplier->branch_id ?? 1;

        $payment = $this->purchaseService->recordSupplierPayment($supplier, $data, auth()->id());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', "Payment of Rs. {$payment->amount} made to supplier under voucher #{$payment->payment_number}.");
    }

    public function statement(Request $request, Supplier $supplier): View
    {
        $startDate = $request->query('start_date', today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $statementData = $this->purchaseService->generateSupplierStatement($supplier, $startDate, $endDate);

        return view('suppliers.statement', $statementData);
    }
}
