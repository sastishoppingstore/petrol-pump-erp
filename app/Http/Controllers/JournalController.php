<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\AccountingService;
use App\Services\Security\BranchScopeService;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function index(Request $request): View
    {
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;

        $entries = JournalEntry::query()
            ->where('branch_id', $branchId)
            ->with(['lines.account', 'creator'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('journals.index', [
            'entries' => $entries,
        ]);
    }

    public function create(Request $request): View
    {
        $accounts = Account::where('status', 'ACTIVE')->orderBy('code')->get();

        return view('journals.create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'narration' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $entry = $this->accounting->post(array_merge($validated, [
                'branch_id' => $branchId,
                'created_by' => $request->user()->id,
            ]));

            return redirect()->route('journals.index')
                ->with('success', "Journal Entry #{$entry->entry_number} posted successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to post entry: ' . $e->getMessage())->withInput();
        }
    }

    public function void(Request $request, JournalEntry $entry): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:5']]);

        try {
            $this->accounting->voidEntry($entry, $request->input('reason'), $request->user());

            return redirect()->route('journals.index')
                ->with('success', "Journal Entry #{$entry->entry_number} voided and reversing entry posted.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function trialBalance(Request $request): View
    {
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        $data = $this->accounting->trialBalance($branchId, $asOfDate);

        return view('journals.trial-balance', [
            'data' => $data,
            'asOfDate' => $asOfDate,
        ]);
    }
}
