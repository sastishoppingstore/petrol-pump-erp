<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankAccountRequest;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BankController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly \App\Services\Banking\BankingService $banking,
    ) {
    }

    /** The bank reference list plus the station's own accounts. */
    public function index(Request $request): View
    {
        return view('banks.index', [
            'banks' => Bank::query()
                ->withCount('accounts')
                ->orderBy('bank_type')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (Bank $b) => match ($b->bank_type) {
                    Bank::TYPE_COMMERCIAL => 'Commercial Banks',
                    Bank::TYPE_ISLAMIC => 'Islamic Banks',
                    Bank::TYPE_PUBLIC => 'Public Sector Banks',
                    default => 'Digital / Specialised',
                }),
            'accounts' => BankAccount::query()
                ->with('bank')
                ->orderBy('account_title')
                ->paginate(20),
        ]);
    }

    public function createAccount(Request $request): View
    {
        return view('banks.account-form', [
            'account' => new BankAccount(['status' => 'ACTIVE', 'account_type' => 'CURRENT']),
            'banks' => Bank::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'branches' => $this->branchScope->selectableBranches($request->user()),
        ]);
    }

    public function storeAccount(BankAccountRequest $request): RedirectResponse
    {
        try {
            $account = BankAccount::create($request->validated());
        } catch (\Throwable $e) {
            Log::error('Bank account creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to save the bank account. No changes were saved.');
        }

        return redirect()
            ->route('banks.index')
            ->with('success', "Bank account '{$account->account_title}' added.");
    }

    public function editAccount(Request $request, BankAccount $bankAccount): View
    {
        return view('banks.account-form', [
            'account' => $bankAccount,
            'banks' => Bank::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'branches' => $this->branchScope->selectableBranches($request->user()),
        ]);
    }

    public function updateAccount(BankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->fill($request->validated())->save();

        return redirect()
            ->route('banks.index')
            ->with('success', "Bank account '{$bankAccount->account_title}' updated.");
    }

    /** A bank with deposits is never deleted — it is deactivated. */
    public function deactivateAccount(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update(['status' => 'INACTIVE']);

        return back()->with('success', 'Bank account deactivated. Its history is preserved.');
    }

    public function deposits(Request $request): View
    {
        $query = BankDeposit::query()
            ->with(['bankAccount.bank', 'depositor', 'shift', 'branch']);

        $this->branchScope->apply($query, $request->user());

        $query
            ->when($request->filled('bank_account_id'), fn ($q) => $q->where('bank_account_id', $request->input('bank_account_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('deposited_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('deposited_at', '<=', $request->input('to')))
            ->orderByDesc('deposited_at');

        return view('banks.deposits', [
            'deposits' => $query->paginate(25)->withQueryString(),
            'accounts' => BankAccount::query()->with('bank')->orderBy('account_title')->get(),
            'shifts' => \App\Models\Shift::query()->where('status', 'OPEN')->orderByDesc('id')->get(),
        ]);
    }

    /** Cash-to-bank deposit with slip photo upload */
    public function storeDeposit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
            'deposited_at' => ['nullable', 'date'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'slip' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ]);

        $account = BankAccount::findOrFail($validated['bank_account_id']);
        $branchId = $account->branch_id ?? $request->user()->branch_id ?? 1;

        $slipPath = null;
        if ($request->hasFile('slip')) {
            $slipPath = $request->file('slip')->store('slips', 'public');
        }

        $shift = ! empty($validated['shift_id']) ? \App\Models\Shift::find($validated['shift_id']) : null;
        $date = ! empty($validated['deposited_at']) ? \Carbon\Carbon::parse($validated['deposited_at']) : null;

        try {
            $this->banking->deposit(
                actor: $request->user(),
                branchId: $branchId,
                account: $account,
                amount: (string) $validated['amount'],
                shift: $shift,
                reference: $validated['reference'] ?? null,
                slipPath: $slipPath,
                notes: $validated['notes'] ?? null,
                date: $date,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('bank-deposits.index')
            ->with('success', "Cash deposit of Rs. " . number_format($validated['amount'], 2) . " to {$account->account_title} recorded successfully.");
    }

    /** Bank Withdrawal */
    public function withdraw(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:50'],
        ]);

        $branchId = $bankAccount->branch_id ?? $request->user()->branch_id ?? 1;

        try {
            $this->banking->withdraw(
                actor: $request->user(),
                branchId: $branchId,
                account: $bankAccount,
                amount: (string) $validated['amount'],
                reason: $validated['reason'],
                reference: $validated['reference'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Withdrawn Rs. " . number_format($validated['amount'], 2) . " from {$bankAccount->account_title}.");
    }

    /** Bank-to-Bank Transfer */
    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_account_id' => ['required', 'exists:bank_accounts,id'],
            'to_account_id' => ['required', 'exists:bank_accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'charges' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:50'],
        ]);

        $fromAccount = BankAccount::findOrFail($validated['from_account_id']);
        $toAccount = BankAccount::findOrFail($validated['to_account_id']);
        $branchId = $fromAccount->branch_id ?? $request->user()->branch_id ?? 1;

        try {
            $this->banking->transfer(
                actor: $request->user(),
                branchId: $branchId,
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                amount: (string) $validated['amount'],
                charges: (string) ($validated['charges'] ?? '0.00'),
                reference: $validated['reference'] ?? null,
                notes: $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Transferred Rs. " . number_format($validated['amount'], 2) . " from {$fromAccount->account_title} to {$toAccount->account_title}.");
    }

    /** Bank Charges / Markup */
    public function applyChargesOrMarkup(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:CHARGES,MARKUP'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:50'],
        ]);

        $branchId = $bankAccount->branch_id ?? $request->user()->branch_id ?? 1;

        try {
            $this->banking->applyChargesOrMarkup(
                actor: $request->user(),
                branchId: $branchId,
                account: $bankAccount,
                amount: (string) $validated['amount'],
                type: $validated['type'],
                reason: $validated['reason'],
                reference: $validated['reference'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = $validated['type'] === 'CHARGES' ? 'Bank charges applied' : 'Bank markup credited';
        return back()->with('success', "{$label}: Rs. " . number_format($validated['amount'], 2));
    }

    /** Bank Book with running transaction balance */
    public function bankBook(Request $request, BankAccount $bankAccount): View
    {
        $from = $request->input('from', today()->startOfMonth()->toDateString());
        $to = $request->input('to', today()->toDateString());

        $bookData = $this->banking->getBankBook($bankAccount, $from, $to);

        return view('banks.bank-book', array_merge($bookData, [
            'account' => $bankAccount,
            'allAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
        ]));
    }

    /** Bank Reconciliation Page */
    public function reconciliation(Request $request, BankAccount $bankAccount): View
    {
        $unreconciled = BankTransaction::query()
            ->where('bank_account_id', $bankAccount->id)
            ->where('reconciled', false)
            ->where('status', BankTransaction::STATUS_COMPLETED)
            ->orderBy('transaction_date')
            ->get();

        $history = \App\Models\BankReconciliation::query()
            ->where('bank_account_id', $bankAccount->id)
            ->orderByDesc('statement_date')
            ->paginate(10);

        return view('banks.reconciliation', [
            'account' => $bankAccount,
            'unreconciled' => $unreconciled,
            'history' => $history,
            'currentBalance' => $bankAccount->currentBalance(),
        ]);
    }

    /** Submit Bank Reconciliation */
    public function storeReconciliation(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'statement_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'matched_ids' => ['nullable', 'array'],
            'matched_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $branchId = $bankAccount->branch_id ?? $request->user()->branch_id ?? 1;

        try {
            $this->banking->reconcileStatement(
                actor: $request->user(),
                branchId: $branchId,
                account: $bankAccount,
                statementDate: $validated['statement_date'],
                statementBalance: (string) $validated['statement_balance'],
                matchedTransactionIds: $validated['matched_ids'] ?? [],
                notes: $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('banks.reconciliation', $bankAccount)->with('success', 'Bank statement reconciled successfully.');
    }

    /** Import Statement CSV */
    public function importCsv(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {
            $rows = $this->banking->importStatementCsv($request->file('csv_file'));
            return back()->with('success', 'Parsed ' . count($rows) . ' statement entries from CSV.');
        } catch (\Throwable $e) {
            return back()->with('error', 'CSV Import error: ' . $e->getMessage());
        }
    }
}
