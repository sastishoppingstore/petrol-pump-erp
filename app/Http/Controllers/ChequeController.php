<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\Cheques\ChequeService;
use App\Services\Security\BranchScopeService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChequeController extends Controller
{
    public function __construct(
        private readonly ChequeService $cheques,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Display cheque register with filtering & PDC calendar.
     */
    public function index(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;
        $viewMode = $request->input('view_mode', 'list');

        if ($viewMode === 'calendar') {
            $month = (int) $request->input('month', date('n'));
            $year = (int) $request->input('year', date('Y'));
            $calendarData = $this->cheques->getPdcCalendar($branchId, $month, $year);

            return view('cheques.index', array_merge($calendarData, [
                'view_mode' => 'calendar',
                'branchId' => $branchId,
                'stats' => $this->getStats($branchId),
                'bankAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
            ]));
        }

        $query = Cheque::query()
            ->with(['customer', 'supplier', 'bankAccount.bank', 'creator'])
            ->where('branch_id', $branchId);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('cheque_number', 'like', $search)
                    ->orWhere('payee_name', 'like', $search)
                    ->orWhere('bank_name', 'like', $search)
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', $search))
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', $search));
            });
        }

        if ($request->filled('due_from')) {
            $query->whereDate('due_date', '>=', $request->input('due_from'));
        }

        if ($request->filled('due_to')) {
            $query->whereDate('due_date', '<=', $request->input('due_to'));
        }

        if ($request->boolean('is_pdc')) {
            $query->where('is_pdc', true);
        }

        $cheques = $query->orderBy('due_date')->paginate(20)->withQueryString();

        return view('cheques.index', [
            'view_mode' => 'list',
            'cheques' => $cheques,
            'stats' => $this->getStats($branchId),
            'bankAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
        ]);
    }

    /**
     * Show form to receive customer cheque or issue supplier cheque.
     */
    public function create(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;

        return view('cheques.create', [
            'type' => $request->input('type', Cheque::TYPE_RECEIVED),
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'bankAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
        ]);
    }

    /**
     * Store newly received or issued cheque.
     */
    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type', Cheque::TYPE_RECEIVED);
        $branchId = $request->user()->branch_id ?? 1;

        if ($type === Cheque::TYPE_RECEIVED) {
            $validated = $request->validate([
                'customer_id' => ['required', 'exists:customers,id'],
                'amount' => ['required', 'numeric', 'min:1'],
                'cheque_number' => ['required', 'string', 'max:50'],
                'bank_name' => ['required', 'string', 'max:100'],
                'cheque_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date'],
                'payee_name' => ['nullable', 'string', 'max:150'],
                'notes' => ['nullable', 'string', 'max:500'],
                'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
            ]);

            $customer = Customer::findOrFail($validated['customer_id']);
            $imagePath = $request->hasFile('image') ? $request->file('image')->store('cheques', 'public') : null;

            try {
                $cheque = $this->cheques->receiveCustomerCheque(
                    actor: $request->user(),
                    branchId: $branchId,
                    customer: $customer,
                    amount: (string) $validated['amount'],
                    chequeNumber: $validated['cheque_number'],
                    bankName: $validated['bank_name'],
                    chequeDate: $validated['cheque_date'],
                    dueDate: $validated['due_date'] ?? null,
                    payeeName: $validated['payee_name'] ?? null,
                    notes: $validated['notes'] ?? null,
                    imagePath: $imagePath,
                );
            } catch (\Throwable $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }

            return redirect()->route('cheques.show', $cheque)
                ->with('success', "Cheque #{$cheque->cheque_number} received from {$customer->name}. Customer ledger credited.");
        }

        // TYPE_ISSUED
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'cheque_number' => ['required', 'string', 'max:50'],
            'cheque_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'payee_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);
        $stationAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $imagePath = $request->hasFile('image') ? $request->file('image')->store('cheques', 'public') : null;

        try {
            $cheque = $this->cheques->issueSupplierCheque(
                actor: $request->user(),
                branchId: $branchId,
                supplier: $supplier,
                stationAccount: $stationAccount,
                amount: (string) $validated['amount'],
                chequeNumber: $validated['cheque_number'],
                chequeDate: $validated['cheque_date'],
                dueDate: $validated['due_date'] ?? null,
                payeeName: $validated['payee_name'] ?? null,
                notes: $validated['notes'] ?? null,
                imagePath: $imagePath,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('cheques.show', $cheque)
            ->with('success', "Cheque #{$cheque->cheque_number} issued to {$supplier->name}.");
    }

    /**
     * Show cheque detail and actions.
     */
    public function show(Cheque $cheque): View
    {
        $cheque->load(['customer', 'supplier', 'bankAccount.bank', 'creator', 'actioner']);
        $bankAccounts = BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get();

        return view('cheques.show', [
            'cheque' => $cheque,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Deposit received cheque into bank account.
     */
    public function deposit(Request $request, Cheque $cheque): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'deposit_date' => ['nullable', 'date'],
        ]);

        $account = BankAccount::findOrFail($validated['bank_account_id']);

        try {
            $this->cheques->depositCheque(
                actor: $request->user(),
                cheque: $cheque,
                account: $account,
                depositDate: $validated['deposit_date'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cheque #{$cheque->cheque_number} marked as deposited into {$account->account_title}.");
    }

    /**
     * Mark cheque as cleared.
     */
    public function clear(Request $request, Cheque $cheque): RedirectResponse
    {
        $validated = $request->validate([
            'cleared_date' => ['nullable', 'date'],
        ]);

        try {
            $this->cheques->clearCheque(
                actor: $request->user(),
                cheque: $cheque,
                clearedDate: $validated['cleared_date'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cheque #{$cheque->cheque_number} cleared successfully. Bank transaction posted.");
    }

    /**
     * Mark cheque as bounced: reverses customer ledger & applies bank charges.
     */
    public function bounce(Request $request, Cheque $cheque): RedirectResponse
    {
        $validated = $request->validate([
            'bounce_reason' => ['required', 'string', 'max:255'],
            'bank_charges' => ['nullable', 'numeric', 'min:0'],
            'bounced_date' => ['nullable', 'date'],
        ]);

        try {
            $this->cheques->bounceCheque(
                actor: $request->user(),
                cheque: $cheque,
                bounceReason: $validated['bounce_reason'],
                bankCharges: (string) ($validated['bank_charges'] ?? '0.00'),
                bouncedDate: $validated['bounced_date'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cheque #{$cheque->cheque_number} marked as BOUNCED. Customer ledger reversed and bank charges recorded.");
    }

    /**
     * Cancel a cheque.
     */
    public function cancel(Request $request, Cheque $cheque): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->cheques->cancelCheque(
                actor: $request->user(),
                cheque: $cheque,
                reason: $validated['reason'],
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cheque #{$cheque->cheque_number} has been cancelled.");
    }

    /**
     * Helper to compute header stats for Cheque register.
     */
    private function getStats(int $branchId): array
    {
        $received = Cheque::where('branch_id', $branchId)->where('type', Cheque::TYPE_RECEIVED);
        $issued = Cheque::where('branch_id', $branchId)->where('type', Cheque::TYPE_ISSUED);

        return [
            'total_received_amount' => (clone $received)->sum('amount'),
            'total_issued_amount' => (clone $issued)->sum('amount'),
            'pdc_count' => Cheque::where('branch_id', $branchId)->where('status', Cheque::STATUS_RECEIVED)->where('due_date', '>', today())->count(),
            'pdc_amount' => Cheque::where('branch_id', $branchId)->where('status', Cheque::STATUS_RECEIVED)->where('due_date', '>', today())->sum('amount'),
            'bounced_count' => Cheque::where('branch_id', $branchId)->where('status', Cheque::STATUS_BOUNCED)->count(),
            'cleared_amount' => Cheque::where('branch_id', $branchId)->where('status', Cheque::STATUS_CLEARED)->sum('amount'),
        ];
    }
}
