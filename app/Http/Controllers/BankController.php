<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankAccountRequest;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\BankTransaction;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Station banking UI: accounts directory, deposits, bank book,
 * withdrawals / transfers / charges and statement reconciliation.
 *
 * Money movement is recorded in `bank_transactions` (migration
 * 2026_01_01_007600): a single signed `amount` column whose direction is
 * given by `type`, plus a `balance_before` / `balance_after` chain.
 * The live balance of an account is therefore:
 *
 *     opening_balance + Σ(credit types) − Σ(debit types)
 *
 * (The `current_balance` column referenced by older code does not exist
 * on the canonical `bank_accounts` table built by migration 004100.)
 */
class BankController extends Controller
{
    /** Transaction types that ADD money to an account. */
    private const CREDIT_TYPES = ['DEPOSIT', 'TRANSFER_IN', 'MARKUP', 'CHEQUE_DEPOSIT'];

    /** Transaction types that TAKE money out of an account. */
    private const DEBIT_TYPES = ['WITHDRAWAL', 'TRANSFER_OUT', 'CHARGES', 'CHEQUE_BOUNCE'];

    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /** Live balance of one account: opening + signed transaction net. */
    private function accountBalance(BankAccount $account): string
    {
        $credits = (string) (DB::table('bank_transactions')
            ->where('bank_account_id', $account->id)
            ->whereIn('type', self::CREDIT_TYPES)
            ->sum('amount') ?? '0');
        $debits = (string) (DB::table('bank_transactions')
            ->where('bank_account_id', $account->id)
            ->whereIn('type', self::DEBIT_TYPES)
            ->sum('amount') ?? '0');

        return bcsub(bcadd((string) $account->opening_balance, $credits, 2), $debits, 2);
    }

    /**
     * Balances for a set of accounts in two grouped queries.
     *
     * @param  iterable<BankAccount>  $accounts
     * @return array<int, string>
     */
    private function balancesFor(iterable $accounts): array
    {
        $accounts = collect($accounts);
        if ($accounts->isEmpty()) {
            return [];
        }

        $ids = $accounts->pluck('id')->all();

        $credits = DB::table('bank_transactions')
            ->whereIn('bank_account_id', $ids)
            ->whereIn('type', self::CREDIT_TYPES)
            ->groupBy('bank_account_id')
            ->selectRaw('bank_account_id, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'bank_account_id');

        $debits = DB::table('bank_transactions')
            ->whereIn('bank_account_id', $ids)
            ->whereIn('type', self::DEBIT_TYPES)
            ->groupBy('bank_account_id')
            ->selectRaw('bank_account_id, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'bank_account_id');

        $balances = [];
        foreach ($accounts as $account) {
            $balances[$account->id] = bcsub(
                bcadd((string) $account->opening_balance, (string) ($credits[$account->id] ?? '0'), 2),
                (string) ($debits[$account->id] ?? '0'),
                2
            );
        }

        return $balances;
    }

    /**
     * Append one row to the account's bank book, keeping the
     * balance_before / balance_after chain intact. Must be called
     * inside a DB transaction by the caller.
     */
    private function recordTransaction(
        BankAccount $account,
        string $type,
        string $amount,
        ?string $reference,
        ?string $description,
        ?int $shiftId = null,
        ?int $relatedAccountId = null,
        ?string $slipPath = null,
    ): string {
        $before = $this->accountBalance($account);
        $after = in_array($type, self::CREDIT_TYPES, true)
            ? bcadd($before, $amount, 2)
            : bcsub($before, $amount, 2);

        DB::table('bank_transactions')->insert([
            'branch_id' => $account->branch_id ?? auth()->user()?->branch_id ?? 1,
            'bank_account_id' => $account->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'reference_number' => $reference,
            'transaction_date' => now(),
            'description' => $description,
            'performed_by' => auth()->id(),
            'slip_path' => $slipPath,
            'shift_id' => $shiftId,
            'related_account_id' => $relatedAccountId,
            'reconciled' => false,
            'status' => 'COMPLETED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $after;
    }

    /** The bank reference list plus the station's own accounts. */
    public function index(Request $request): View
    {
        $accounts = BankAccount::query()
            ->with('bank')
            ->orderBy('account_title')
            ->paginate(20);

        return view('banks.index', [
            // bank_type values are plain strings on the banks table
            // (COMMERCIAL | ISLAMIC | PUBLIC | DIGITAL — migration 004100).
            'banks' => Bank::query()
                ->withCount('accounts')
                ->orderBy('bank_type')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (Bank $b) => match ($b->bank_type) {
                    'COMMERCIAL' => 'Commercial Banks',
                    'ISLAMIC' => 'Islamic Banks',
                    'PUBLIC' => 'Public Sector Banks',
                    default => 'Digital / Specialised',
                }),
            'accounts' => $accounts,
            'balances' => $this->balancesFor($accounts->getCollection()),
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
            $data = $request->validated();
            // branch_id / iban / notes are real columns but are not in the
            // model's $fillable, so mass assignment would silently drop
            // them. forceFill keeps the admin's input intact.
            $account = (new BankAccount())->forceFill([
                'bank_id' => $data['bank_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'account_title' => $data['account_title'],
                'account_number' => $data['account_number'],
                'iban' => $data['iban'] ?? null,
                'account_type' => $data['account_type'],
                'opening_balance' => $data['opening_balance'] ?? 0,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
            $account->save();
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
        $data = $request->validated();

        $bankAccount->forceFill([
            'bank_id' => $data['bank_id'],
            'branch_id' => $data['branch_id'] ?? $bankAccount->branch_id,
            'account_title' => $data['account_title'],
            'account_number' => $data['account_number'],
            'iban' => $data['iban'] ?? null,
            'account_type' => $data['account_type'],
            'opening_balance' => $data['opening_balance'] ?? $bankAccount->opening_balance,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $bankAccount->notes,
        ])->save();

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

        $accounts = BankAccount::query()->with('bank')->orderBy('account_title')->get();

        return view('banks.deposits', [
            'deposits' => $query->paginate(25)->withQueryString(),
            'accounts' => $accounts,
            'balances' => $this->balancesFor($accounts),
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
        $amount = number_format((float) $validated['amount'], 2, '.', '');
        $reference = $validated['reference'] ?? ('DEP-' . now()->format('YmdHis') . '-' . $account->id);
        $depositedAt = ! empty($validated['deposited_at']) ? \Carbon\Carbon::parse($validated['deposited_at']) : now();

        $slipPath = null;
        if ($request->hasFile('slip')) {
            $slipPath = $request->file('slip')->store('slips', 'public');
        }

        try {
            DB::transaction(function () use ($request, $account, $branchId, $amount, $reference, $depositedAt, $slipPath, $validated) {
                // Lock the account row so the balance chain cannot fork.
                DB::table('bank_accounts')->where('id', $account->id)->lockForUpdate()->first();

                $before = $this->accountBalance($account);
                $after = bcadd($before, $amount, 2);

                BankDeposit::create([
                    'branch_id' => $branchId,
                    'bank_account_id' => $account->id,
                    'bank_name' => $account->bank?->name ?? 'Unknown bank',
                    'shift_id' => $validated['shift_id'] ?? null,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'reference_number' => $reference,
                    'deposited_at' => $depositedAt,
                    'reason' => $validated['notes'] ?? null,
                    'deposit_type' => BankDeposit::TYPE_CASH,
                    'deposited_by' => $request->user()->id,
                    'slip_path' => $slipPath,
                    'status' => BankDeposit::STATUS_COMPLETED,
                ]);

                $this->recordTransaction(
                    $account,
                    'DEPOSIT',
                    $amount,
                    $reference,
                    'Cash deposit to bank' . (! empty($validated['notes']) ? ' — ' . $validated['notes'] : ''),
                    $validated['shift_id'] ?? null,
                    null,
                    $slipPath,
                );

                // Cash leaves the till: mirror BankDepositService so the
                // shift's closing reconciliation still balances.
                if (! empty($validated['shift_id'])) {
                    DB::table('shift_cash')->insert([
                        'shift_id' => $validated['shift_id'],
                        'entry_type' => 'BANK_DEPOSIT',
                        'amount' => $amount,
                        'notes' => "Deposit to {$account->bank?->name} ({$reference})",
                        'user_id' => $request->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Bank deposit failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to record the deposit. No changes were saved.')->withInput();
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

        $amount = number_format((float) $validated['amount'], 2, '.', '');

        if (bccomp($this->accountBalance($bankAccount), $amount, 2) < 0) {
            return back()->with('error', 'Withdrawal exceeds the available bank balance for this account.');
        }

        try {
            DB::transaction(function () use ($bankAccount, $amount, $validated) {
                DB::table('bank_accounts')->where('id', $bankAccount->id)->lockForUpdate()->first();
                $this->recordTransaction(
                    $bankAccount,
                    'WITHDRAWAL',
                    $amount,
                    $validated['reference'] ?? null,
                    $validated['reason'],
                );
            });
        } catch (\Throwable $e) {
            Log::error('Bank withdrawal failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to complete the withdrawal. No changes were saved.');
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
        $amount = number_format((float) $validated['amount'], 2, '.', '');
        $charges = number_format((float) ($validated['charges'] ?? 0), 2, '.', '');
        $totalOut = bcadd($amount, $charges, 2);

        if (bccomp($this->accountBalance($fromAccount), $totalOut, 2) < 0) {
            return back()->with('error', 'Transfer amount (plus charges) exceeds the source account balance.');
        }

        try {
            DB::transaction(function () use ($fromAccount, $toAccount, $amount, $charges, $validated) {
                DB::table('bank_accounts')->whereIn('id', [$fromAccount->id, $toAccount->id])->lockForUpdate()->get();

                $this->recordTransaction(
                    $fromAccount,
                    'TRANSFER_OUT',
                    $amount,
                    $validated['reference'] ?? null,
                    'Transfer to ' . $toAccount->account_title . (! empty($validated['notes']) ? ' — ' . $validated['notes'] : ''),
                    null,
                    $toAccount->id,
                );
                $this->recordTransaction(
                    $toAccount,
                    'TRANSFER_IN',
                    $amount,
                    $validated['reference'] ?? null,
                    'Transfer from ' . $fromAccount->account_title,
                    null,
                    $fromAccount->id,
                );
                if (bccomp($charges, '0', 2) > 0) {
                    $this->recordTransaction(
                        $fromAccount,
                        'CHARGES',
                        $charges,
                        $validated['reference'] ?? null,
                        'Bank transfer charges',
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('Bank transfer failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to complete the transfer. No changes were saved.');
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

        $amount = number_format((float) $validated['amount'], 2, '.', '');

        try {
            DB::transaction(function () use ($bankAccount, $amount, $validated) {
                DB::table('bank_accounts')->where('id', $bankAccount->id)->lockForUpdate()->first();
                $this->recordTransaction(
                    $bankAccount,
                    $validated['type'],
                    $amount,
                    $validated['reference'] ?? null,
                    $validated['reason'],
                );
            });
        } catch (\Throwable $e) {
            Log::error('Bank charges/markup failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to apply the entry. No changes were saved.');
        }

        $label = $validated['type'] === 'CHARGES' ? 'Bank charges applied' : 'Bank markup credited';
        return back()->with('success', "{$label}: Rs. " . number_format($validated['amount'], 2));
    }

    /** Bank Book with running transaction balance */
    public function bankBook(Request $request, BankAccount $bankAccount): View
    {
        $from = $request->input('from', today()->startOfMonth()->toDateString());
        $to = $request->input('to', today()->toDateString());

        // Opening balance: the balance_after of the last entry before the
        // period, falling back to the account's opening balance. Every
        // transaction row carries the chain, so this is exact.
        $prior = BankTransaction::query()
            ->where('bank_account_id', $bankAccount->id)
            ->where('transaction_date', '<', $from . ' 00:00:00')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();
        $opening = $prior ? (string) $prior->balance_after : (string) $bankAccount->opening_balance;

        $rows = BankTransaction::query()
            ->where('bank_account_id', $bankAccount->id)
            ->whereBetween('transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $totalCredits = '0.00';
        $totalDebits = '0.00';
        $closing = $opening;

        $items = $rows->map(function (BankTransaction $t) use (&$totalCredits, &$totalDebits, &$closing) {
            $isCredit = in_array($t->type, self::CREDIT_TYPES, true);
            $amount = (string) $t->amount;
            if ($isCredit) {
                $totalCredits = bcadd($totalCredits, $amount, 2);
            } else {
                $totalDebits = bcadd($totalDebits, $amount, 2);
            }
            $closing = (string) $t->balance_after;

            return [
                'date' => $t->transaction_date?->format('d M Y'),
                'type' => $t->type,
                'reference' => $t->reference_number,
                'description' => $t->description,
                'credit' => $isCredit ? $amount : '0.00',
                'debit' => $isCredit ? '0.00' : $amount,
                'running_balance' => (string) $t->balance_after,
                'reconciled' => (bool) $t->reconciled,
            ];
        })->all();

        return view('banks.bank-book', [
            'account' => $bankAccount,
            'allAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
            'start_date' => $from,
            'end_date' => $to,
            'opening_balance' => $opening,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'closing_balance' => $closing,
            'items' => $items,
        ]);
    }

    /** Bank Reconciliation Page */
    public function reconciliation(Request $request, BankAccount $bankAccount): View
    {
        $unreconciled = BankTransaction::query()
            ->where('bank_account_id', $bankAccount->id)
            ->where('reconciled', false)
            ->where('status', 'COMPLETED')
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
            'currentBalance' => $this->accountBalance($bankAccount),
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
        $statementBalance = number_format((float) $validated['statement_balance'], 2, '.', '');

        try {
            DB::transaction(function () use ($bankAccount, $branchId, $statementBalance, $validated) {
                $ledger = $this->accountBalance($bankAccount);

                // Columns follow migration 007600 (ledger_balance /
                // difference), written directly because the model's
                // $fillable still targets an older schema revision.
                $reconciliationId = DB::table('bank_reconciliations')->insertGetId([
                    'branch_id' => $branchId,
                    'bank_account_id' => $bankAccount->id,
                    'statement_date' => $validated['statement_date'],
                    'statement_balance' => $statementBalance,
                    'ledger_balance' => $ledger,
                    'difference' => bcsub($statementBalance, $ledger, 2),
                    'reconciled_by' => auth()->id(),
                    'status' => 'COMPLETED',
                    'notes' => $validated['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (! empty($validated['matched_ids'])) {
                    DB::table('bank_transactions')
                        ->where('bank_account_id', $bankAccount->id)
                        ->whereIn('id', $validated['matched_ids'])
                        ->update([
                            'reconciled' => true,
                            'reconciled_at' => now(),
                            'reconciliation_id' => $reconciliationId,
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Bank reconciliation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to save the reconciliation. No changes were saved.');
        }

        return redirect()->route('banks.reconciliation', $bankAccount)->with('success', 'Bank statement reconciled successfully.');
    }

    /** Import Statement CSV (parse + count only, nothing is posted) */
    public function importCsv(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {
            $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
            if ($handle === false) {
                throw new \RuntimeException('Unable to read the uploaded file.');
            }

            $count = 0;
            $firstRow = true;
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                    continue;
                }
                // Skip a header row such as "Date,Description,Amount".
                if ($firstRow && preg_match('/[a-zA-Z]/', (string) ($row[0] ?? '')) && ! preg_match('/\d{2,4}[-\\/]\d{1,2}/', (string) $row[0])) {
                    $firstRow = false;
                    continue;
                }
                $firstRow = false;
                $count++;
            }
            fclose($handle);

            return back()->with('success', "Parsed {$count} statement entries from CSV.");
        } catch (\Throwable $e) {
            return back()->with('error', 'CSV Import error: ' . $e->getMessage());
        }
    }
}
