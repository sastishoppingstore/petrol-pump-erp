<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shift;
use App\Services\Accounts\ExpenseService;
use App\Services\Security\BranchScopeService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Display list of expenses with filters.
     */
    public function index(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;

        $query = Expense::query()
            ->with(['category', 'bankAccount.bank', 'shift', 'creator'])
            ->where('branch_id', $branchId);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->input('to'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('expense_number', 'like', $search)
                    ->orWhere('payee', 'like', $search)
                    ->orWhere('receipt_number', 'like', $search);
            });
        }

        $expenses = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->withQueryString();

        // Calculate statistics
        $allPeriodExpenses = (clone $query)->get();
        $totalAmount = $allPeriodExpenses->sum(fn ($e) => (float) $e->amount);
        $cashAmount = $allPeriodExpenses->where('payment_method', Expense::METHOD_CASH)->sum(fn ($e) => (float) $e->amount);
        $bankAmount = $allPeriodExpenses->where('payment_method', Expense::METHOD_BANK_TRANSFER)->sum(fn ($e) => (float) $e->amount);

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'totalAmount' => $totalAmount,
            'cashAmount' => $cashAmount,
            'bankAmount' => $bankAmount,
        ]);
    }

    /**
     * Show expense creation form.
     */
    public function create(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;

        return view('expenses.create', [
            'categories' => ExpenseCategory::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'bankAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
            'shifts' => Shift::query()->where('status', 'OPEN')->orderByDesc('id')->get(),
        ]);
    }

    /**
     * Store new expense with voucher photo upload.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:expense_categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER,CHEQUE'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'date' => ['nullable', 'date'],
            'payee' => ['nullable', 'string', 'max:150'],
            'receipt_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ]);

        $branchId = $request->user()->branch_id ?? 1;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('expenses', 'public');
        }

        try {
            $expense = $this->expenseService->createExpense(
                branchId: $branchId,
                categoryId: (int) $validated['category_id'],
                title: $validated['title'],
                amount: (string) $validated['amount'],
                paymentMethod: $validated['payment_method'],
                date: $validated['date'] ?? null,
                bankAccountId: ! empty($validated['bank_account_id']) ? (int) $validated['bank_account_id'] : null,
                shiftId: ! empty($validated['shift_id']) ? (int) $validated['shift_id'] : null,
                payee: $validated['payee'] ?? null,
                receiptNumber: $validated['receipt_number'] ?? null,
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                attachmentPath: $attachmentPath,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('expenses.index')
            ->with('success', "Expense voucher {$expense->expense_number} for Rs. " . number_format($validated['amount'], 2) . " created successfully.");
    }

    /**
     * Show single expense voucher.
     */
    public function show(Expense $expense): View
    {
        $expense->load(['category', 'branch', 'bankAccount.bank', 'shift', 'creator', 'approver']);

        return view('expenses.show', [
            'expense' => $expense,
        ]);
    }
}
