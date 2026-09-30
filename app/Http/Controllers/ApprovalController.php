<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Services\Approvals\ApprovalService;
use App\Services\Security\BranchScopeService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Display approvals centre.
     */
    public function index(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;

        $query = ApprovalRequest::query()
            ->with(['requester', 'actioner', 'branch'])
            ->where('branch_id', $branchId);

        $status = $request->input('status', 'PENDING');
        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($request->filled('type')) {
            $query->where('request_type', $request->input('type'));
        }

        $requests = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $pendingCount = ApprovalRequest::where('branch_id', $branchId)->where('status', ApprovalRequest::STATUS_PENDING)->count();
        $approvedToday = ApprovalRequest::where('branch_id', $branchId)->where('status', ApprovalRequest::STATUS_APPROVED)->whereDate('actioned_at', today())->count();
        $rejectedToday = ApprovalRequest::where('branch_id', $branchId)->where('status', ApprovalRequest::STATUS_REJECTED)->whereDate('actioned_at', today())->count();

        return view('approvals.index', [
            'requests' => $requests,
            'status' => $status,
            'pendingCount' => $pendingCount,
            'approvedToday' => $approvedToday,
            'rejectedToday' => $rejectedToday,
            'ownerPhone' => '0300-4342343',
        ]);
    }

    /**
     * Manually submit an approval request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'request_type' => ['required', 'in:METER_CORRECTION,CREDIT_OVERRIDE,CASH_SHORTAGE,LARGE_CASHOUT,STOCK_ADJUSTMENT,EXPENSE,ADVANCE'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branchId = $request->user()->branch_id ?? 1;

        try {
            $req = $this->approvals->request(
                requester: $request->user(),
                branchId: $branchId,
                type: $validated['request_type'],
                title: $validated['title'],
                description: $validated['description'],
                amount: ! empty($validated['amount']) ? (string) $validated['amount'] : null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('approvals.index')
            ->with('success', "Approval request #{$req->id} submitted. Owner WhatsApp alert ready.");
    }

    /**
     * Big Green Approve.
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->approvals->approve(
                approver: $request->user(),
                request: $approvalRequest,
                reason: $validated['reason'],
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Request #{$approvalRequest->id} ('{$approvalRequest->title}') APPROVED.");
    }

    /**
     * Big Red Reject.
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->approvals->reject(
                approver: $request->user(),
                request: $approvalRequest,
                reason: $validated['reason'],
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Request #{$approvalRequest->id} REJECTED.");
    }
}
