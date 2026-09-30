<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\Purchase;
use App\Services\Accounting\AccountingEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountingController extends Controller
{
    protected AccountingEngineService $accountingService;

    public function __construct(AccountingEngineService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Get trial balance for a branch as of date
     */
    public function trialBalance(Request $request)
    {
        Gate::authorize('view', 'accounting');

        $branchId = auth()->user()->branch_id ?? 1;
        $asOfDate = $request->get('as_of_date', now()->toDateString());

        try {
            $trialBalance = $this->accountingService->getTrialBalance($branchId, $asOfDate);

            return response()->json([
                'success' => true,
                'data' => $trialBalance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate trial balance. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get financial summary (P&L-like)
     */
    public function financialSummary(Request $request)
    {
        Gate::authorize('view', 'accounting');

        $branchId = auth()->user()->branch_id ?? 1;
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        try {
            $summary = $this->accountingService->getFinancialSummary($branchId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate financial summary.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get account transactions (ledger)
     */
    public function accountLedger(Request $request, Account $account)
    {
        Gate::authorize('view', 'accounting');

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        try {
            $transactions = $this->accountingService->getAccountTransactions($account, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'account' => [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                ],
                'transactions' => $transactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve account transactions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List journal entries
     */
    public function journalEntries(Request $request)
    {
        Gate::authorize('view', 'accounting');

        $branchId = auth()->user()->branch_id ?? 1;
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status', 'POSTED');

        $query = JournalEntry::where('branch_id', $branchId)
            ->where('status', $status)
            ->with('lines.account');

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        $entries = $query->orderBy('entry_date', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }

    /**
     * Get single journal entry detail
     */
    public function journalEntryDetail(JournalEntry $entry)
    {
        Gate::authorize('view', 'accounting');

        $isBalanced = $this->accountingService->isEntryBalanced($entry);

        return response()->json([
            'success' => true,
            'data' => [
                'entry' => $entry->load('lines.account'),
                'is_balanced' => $isBalanced,
            ],
        ]);
    }

    /**
     * Manually post a sale transaction (for testing or manual override)
     */
    public function postSaleTransaction(Request $request, Sale $sale)
    {
        Gate::authorize('create', 'journal_entry');

        try {
            $branchId = auth()->user()->branch_id ?? 1;
            $entry = $this->accountingService->postSaleTransaction($sale, $branchId);

            return response()->json([
                'success' => true,
                'message' => 'Sale transaction posted successfully.',
                'data' => $entry->load('lines'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to post sale transaction.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually post a purchase transaction
     */
    public function postPurchaseTransaction(Request $request, Purchase $purchase)
    {
        Gate::authorize('create', 'journal_entry');

        try {
            $branchId = auth()->user()->branch_id ?? 1;
            $entry = $this->accountingService->postPurchaseTransaction($purchase, $branchId);

            return response()->json([
                'success' => true,
                'message' => 'Purchase transaction posted successfully.',
                'data' => $entry->load('lines'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to post purchase transaction.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new account (for admin)
     */
    public function createAccount(Request $request)
    {
        Gate::authorize('create', 'account');

        $validated = $request->validate([
            'code' => 'required|string|unique:accounts,code',
            'name' => 'required|string',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'classification' => 'required|string',
        ]);

        try {
            $account = Account::create([
                'branch_id' => auth()->user()->branch_id ?? 1,
                'code' => $validated['code'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'classification' => $validated['classification'],
                'normal_balance' => in_array($validated['type'], ['ASSET', 'EXPENSE']) ? 'DEBIT' : 'CREDIT',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully.',
                'data' => $account,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
