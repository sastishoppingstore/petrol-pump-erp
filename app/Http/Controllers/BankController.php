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
        $bankAccount->update(['status' => BankAccount::STATUS_INACTIVE ?? 'INACTIVE']);

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
        ]);
    }
}
