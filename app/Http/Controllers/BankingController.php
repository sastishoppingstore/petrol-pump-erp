<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Cheque;
use App\Models\BankReconciliation;
use App\Services\Banking\BankingService;
use App\Support\PermissionList;
use Illuminate\Http\Request;

class BankingController extends Controller
{
    protected BankingService $bankingService;

    public function __construct(BankingService $bankingService)
    {
        $this->bankingService = $bankingService;
    }

    /**
     * List bank accounts
     */
    public function bankAccounts(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_VIEW);

        $branchId = auth()->user()->branch_id ?? 1;
        $accounts = BankAccount::with('bank')
            ->orderBy('account_title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get bank account detail
     */
    public function bankAccountDetail(BankAccount $account)
    {
        $this->requirePermission(PermissionList::CASH_VIEW);

        return response()->json([
            'success' => true,
            'data' => $account->load('bank'),
        ]);
    }

    /**
     * Create bank account
     */
    public function createBankAccount(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_CREATE);

        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|unique:bank_accounts,account_number',
            'account_title' => 'required|string',
            'account_type' => 'required|in:CURRENT,SAVINGS,DEPOSIT',
            'opening_balance' => 'required|numeric',
        ]);

        try {
            $account = BankAccount::create($validated + [
                'status' => BankAccount::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank account created successfully.',
                'data' => $account,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create bank account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record bank deposit
     */
    public function recordDeposit(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_CREATE);

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        try {
            $transaction = $this->bankingService->recordDeposit(
                $validated['bank_account_id'],
                $validated['reference_number'],
                (string) $validated['amount'],
                $validated['description'],
                $validated['shift_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Deposit recorded successfully.',
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to record deposit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Issue cheque
     */
    public function issueCheque(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_CREATE);

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'cheque_number' => 'required|string',
            'issued_to' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ]);

        try {
            $cheque = $this->bankingService->issueCheque(
                $validated['bank_account_id'],
                $validated['cheque_number'],
                $validated['issued_to'],
                (string) $validated['amount'],
                new \DateTime($validated['issue_date']),
                new \DateTime($validated['due_date']),
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Cheque issued successfully.',
                'data' => $cheque,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to issue cheque.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cheque
     */
    public function clearCheque(Request $request, Cheque $cheque)
    {
        $this->requirePermission(PermissionList::CASH_CREATE);

        try {
            $this->bankingService->clearCheque($cheque, $cheque->bank_account_id);

            return response()->json([
                'success' => true,
                'message' => 'Cheque cleared successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to clear cheque.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bounce cheque
     */
    public function bounceCheque(Request $request, Cheque $cheque)
    {
        $this->requirePermission(PermissionList::CASH_CREATE);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $this->bankingService->bounceCheque($cheque, $cheque->bank_account_id, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Cheque marked as bounced.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to bounce cheque.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bank transactions
     */
    public function bankTransactions(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_VIEW);

        $bankAccountId = $request->get('bank_account_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = BankTransaction::query();

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get reconciliation report
     */
    public function reconciliationReport(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_VIEW);

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'as_of_date' => 'required|date',
        ]);

        try {
            $report = $this->bankingService->getReconciliationReport(
                $validated['bank_account_id'],
                new \DateTime($validated['as_of_date'])
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate reconciliation report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reconcile bank statement
     */
    public function reconcileStatement(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_CREATE);

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'matched_transaction_ids' => 'nullable|array',
            'matched_transaction_ids.*' => 'exists:bank_transactions,id',
        ]);

        try {
            $reconciliation = $this->bankingService->reconcileStatement(
                $validated['bank_account_id'],
                new \DateTime($validated['statement_date']),
                (string) $validated['statement_balance'],
                $validated['matched_transaction_ids'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Bank statement reconciled.',
                'data' => $reconciliation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reconcile statement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cheques for an account
     */
    public function cheques(Request $request)
    {
        $this->requirePermission(PermissionList::CASH_VIEW);

        $bankAccountId = $request->get('bank_account_id');
        $status = $request->get('status');

        $query = Cheque::query();

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $cheques = $query->orderBy('due_date', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $cheques,
        ]);
    }
    /**
     * The old code called Gate::authorize('view'|'create'|'edit', 'banking'),
     * but no 'banking' gate/policy exists in this app — authorization is
     * permission-string based (see PermissionList + permission middleware).
     */
    private function requirePermission(string $permission): void
    {
        abort_unless(auth()->user()?->hasPermission($permission), 403, 'This action is unauthorized.');
    }
}
