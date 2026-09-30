<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\Shift;
use App\Services\Cash\CashBookService;
use App\Services\Security\BranchScopeService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashController extends Controller
{
    public function __construct(
        private readonly CashBookService $cashBook,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Daily Roznamcha (Cash Book Register) View.
     */
    public function index(Request $request): View
    {
        $branchId = (int) ($this->branchScope->activeBranchId($request) ?? $request->user()->defaultBranch()?->id);
        $date = $request->input('date', now()->toDateString());

        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();
        if (! $request->user()->isSuperAdmin()) {
            $branches = $request->user()->branches()->where('status', Branch::STATUS_ACTIVE)->get();
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
        }

        $activeShift = Shift::where('branch_id', $branchId)
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        $availableCash = $this->cashBook->availableCash($branchId, $activeShift?->id);
        $roznamcha = $this->cashBook->dailyRoznamcha($branchId, $date);

        return view('cash.index', [
            'branches' => $branches,
            'branchId' => $branchId,
            'date' => $date,
            'roznamcha' => $roznamcha,
            'availableCash' => $availableCash,
            'activeShift' => $activeShift,
        ]);
    }

    /**
     * Cash In (CRV) Form.
     */
    public function createIn(Request $request): View
    {
        $branchId = (int) ($this->branchScope->activeBranchId($request) ?? $request->user()->defaultBranch()?->id);

        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();
        if (! $request->user()->isSuperAdmin()) {
            $branches = $request->user()->branches()->where('status', Branch::STATUS_ACTIVE)->get();
        }

        $activeShift = Shift::where('branch_id', $branchId)
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        $categories = [
            'CUSTOMER_PAYMENT' => 'Customer Credit Payment / وصولی گاہک',
            'DIRECT_SALE' => 'Direct Cash Inflow / نقد وصولی',
            'OWNER_INJECTION' => 'Owner Capital Injection / سرمایہ مالک',
            'BANK_WITHDRAWAL' => 'Cash Withdrawn from Bank / بینک سے نکلوائی گئی رقم',
            'OTHER_INCOME' => 'Other Cash Inflow / دیگر آمدن',
        ];

        return view('cash.create-in', [
            'branches' => $branches,
            'branchId' => $branchId,
            'activeShift' => $activeShift,
            'categories' => $categories,
        ]);
    }

    /**
     * Store Cash In (CRV).
     */
    public function storeIn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:50'],
            'person_name' => ['required', 'string', 'max:150'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'entry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (! $request->user()->canAccessBranch((int) $data['branch_id'])) {
            abort(403, 'You do not have access to that branch.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cash_receipts', 'public');
        }

        try {
            $entry = $this->cashBook->recordCashIn(
                actor: $request->user(),
                branchId: (int) $data['branch_id'],
                amount: (string) $data['amount'],
                category: $data['category'],
                personName: $data['person_name'],
                shiftId: ! empty($data['shift_id']) ? (int) $data['shift_id'] : null,
                referenceNo: $data['reference_no'] ?? null,
                attachmentPath: $attachmentPath,
                notes: $data['notes'] ?? null,
                entryDate: $data['entry_date'] ?? null,
            );

            return redirect()
                ->route('cash.index', ['branch_id' => $data['branch_id'], 'date' => $data['entry_date'] ?? now()->toDateString()])
                ->with('success', "Cash In recorded successfully. Voucher: {$entry->voucher_number} (Rs. " . number_format((float)$entry->amount, 2) . ").");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Cash Out (CPV - Kharcha / Expense / Deposit) Form.
     */
    public function createOut(Request $request): View
    {
        $branchId = (int) ($this->branchScope->activeBranchId($request) ?? $request->user()->defaultBranch()?->id);

        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();
        if (! $request->user()->isSuperAdmin()) {
            $branches = $request->user()->branches()->where('status', Branch::STATUS_ACTIVE)->get();
        }

        $activeShift = Shift::where('branch_id', $branchId)
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        $availableCash = $this->cashBook->availableCash($branchId, $activeShift?->id);

        $categories = [
            'EXPENSE' => 'Daily Station Expense / روزمرہ اسٹیشن خرچہ',
            'SUPPLIER_PAYMENT' => 'Supplier Payment / سپلائر ادائیگی',
            'BANK_DEPOSIT' => 'Bank Cash Deposit / بینک میں جمع',
            'OWNER_DRAWING' => 'Owner Drawing / مالک کا ذاتی خرچ',
            'SALARY_ADVANCE' => 'Staff Salary Advance / تنخواہ ایڈوانس',
            'OTHER_PAYMENT' => 'Other Cash Outflow / دیگر ادائیگی',
        ];

        return view('cash.create-out', [
            'branches' => $branches,
            'branchId' => $branchId,
            'activeShift' => $activeShift,
            'availableCash' => $availableCash,
            'categories' => $categories,
        ]);
    }

    /**
     * Store Cash Out (CPV).
     * RULE: Cash cannot go negative!
     */
    public function storeOut(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:50'],
            'person_name' => ['required', 'string', 'max:150'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'entry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (! $request->user()->canAccessBranch((int) $data['branch_id'])) {
            abort(403, 'You do not have access to that branch.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cash_vouchers', 'public');
        }

        try {
            $entry = $this->cashBook->recordCashOut(
                actor: $request->user(),
                branchId: (int) $data['branch_id'],
                amount: (string) $data['amount'],
                category: $data['category'],
                personName: $data['person_name'],
                shiftId: ! empty($data['shift_id']) ? (int) $data['shift_id'] : null,
                referenceNo: $data['reference_no'] ?? null,
                attachmentPath: $attachmentPath,
                notes: $data['notes'] ?? null,
                entryDate: $data['entry_date'] ?? null,
            );

            return redirect()
                ->route('cash.index', ['branch_id' => $data['branch_id'], 'date' => $data['entry_date'] ?? now()->toDateString()])
                ->with('success', "Cash Out recorded successfully. Voucher: {$entry->voucher_number} (Rs. " . number_format((float)$entry->amount, 2) . ").");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Show Voucher Details.
     */
    public function show(Request $request, CashEntry $cashEntry): View
    {
        if (! $request->user()->canAccessBranch((int) $cashEntry->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $cashEntry->load(['branch', 'user', 'shift', 'approver']);

        return view('cash.show', [
            'entry' => $cashEntry,
        ]);
    }

    /**
     * Printable Voucher (Thermal 80mm or Slip).
     */
    public function print(Request $request, CashEntry $cashEntry): View
    {
        if (! $request->user()->canAccessBranch((int) $cashEntry->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $cashEntry->load(['branch', 'user', 'shift']);

        return view('cash.print', [
            'entry' => $cashEntry,
        ]);
    }
}
