<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseShiftRequest;
use App\Http\Requests\OpenShiftRequest;
use App\Http\Requests\ShiftCashRequest;
use App\Models\Branch;
use App\Models\Nozzle;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Security\BranchScopeService;
use App\Services\Shift\ShiftService;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Shifts register with filtering.
     */
    public function index(Request $request): View
    {
        $query = Shift::query()
            ->with(['branch', 'user', 'approver', 'shiftNozzles'])
            ->latest('opened_at');

        $this->branchScope->apply($query, $request->user());

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('opened_at', $date);
        }

        $shifts = $query->paginate(20)->withQueryString();

        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();
        $employees = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get();

        return view('shifts.index', [
            'shifts' => $shifts,
            'branches' => $branches,
            'employees' => $employees,
            'filters' => $request->only(['status', 'user_id', 'branch_id', 'date']),
        ]);
    }

    /**
     * Open shift form.
     */
    public function create(Request $request): View
    {
        $activeBranchId = $this->branchScope->activeBranchId($request);

        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();
        if (! $request->user()->isSuperAdmin()) {
            $branches = $request->user()->branches()->where('status', Branch::STATUS_ACTIVE)->get();
        }

        $targetBranchId = $request->input('branch_id', $activeBranchId ?? $branches->first()?->id);

        // Get available nozzles for target branch (excluding those in active open shifts)
        $busyNozzleIds = \App\Models\ShiftNozzle::whereHas('shift', fn ($q) => $q->where('status', Shift::STATUS_OPEN))
            ->pluck('nozzle_id')
            ->all();

        $nozzles = Nozzle::where('branch_id', $targetBranchId)
            ->where('status', Nozzle::STATUS_ACTIVE)
            ->with(['dispenser', 'fuelProduct', 'tank'])
            ->get();

        // Eligible shift operators: attendants, cashiers, managers, admins
        $employees = User::where('status', User::STATUS_ACTIVE)
            ->whereDoesntHave('shifts', fn ($q) => $q->where('status', Shift::STATUS_OPEN))
            ->orderBy('name')
            ->get();

        return view('shifts.create', [
            'branches' => $branches,
            'targetBranchId' => $targetBranchId,
            'employees' => $employees,
            'nozzles' => $nozzles,
            'busyNozzleIds' => $busyNozzleIds,
        ]);
    }

    /**
     * Open shift action.
     */
    public function store(OpenShiftRequest $request): RedirectResponse
    {
        try {
            $shift = $this->shiftService->open($request->validated(), $request->user());

            return redirect()
                ->route('shifts.show', $shift)
                ->with('success', "Shift {$shift->shift_number} opened successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Shift details & active status.
     */
    public function show(Request $request, Shift $shift): View
    {
        $this->authorizeShift($request->user(), $shift);

        $shift->load([
            'branch',
            'user',
            'approver',
            'shiftNozzles.nozzle.fuelProduct',
            'shiftNozzles.nozzle.dispenser',
            'shiftCash.user',
            'meterReadings.nozzle.fuelProduct',
        ]);

        $liveExpectedCash = $this->shiftService->calculateExpectedCash($shift);
        $totalDrops = $this->shiftService->calculateTotalDrops($shift);

        return view('shifts.show', [
            'shift' => $shift,
            'liveExpectedCash' => $liveExpectedCash,
            'totalDrops' => $totalDrops,
        ]);
    }

    /**
     * Shift closing form.
     */
    public function closeForm(Request $request, Shift $shift): View
    {
        $this->authorizeShiftClose($request->user(), $shift);

        if (! $shift->isOpen()) {
            return redirect()
                ->route('shifts.show', $shift)
                ->with('error', 'This shift is already closed.');
        }

        $shift->load(['branch', 'user', 'shiftNozzles.nozzle.fuelProduct', 'shiftCash']);
        $expectedCash = $this->shiftService->calculateExpectedCash($shift);

        return view('shifts.close', [
            'shift' => $shift,
            'expectedCash' => $expectedCash,
            'threshold' => config('erp.shift_variance_threshold', 100),
        ]);
    }

    /**
     * Shift closing action.
     */
    public function close(CloseShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->authorizeShiftClose($request->user(), $shift);

        try {
            $closedShift = $this->shiftService->close($shift, $request->validated(), $request->user());

            $msg = $closedShift->isPendingApproval()
                ? "Shift {$closedShift->shift_number} closed with high variance and is pending manager approval."
                : "Shift {$closedShift->shift_number} closed successfully.";

            return redirect()
                ->route('shifts.show', $closedShift)
                ->with('success', $msg);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Manager approval for excessive shift variance.
     */
    public function approve(Request $request, Shift $shift): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole(Role::ADMIN) && ! $user->hasRole(Role::MANAGER) && ! $user->hasPermission(PermissionList::SHIFT_CLOSE)) {
            abort(403, 'Unauthorized. Only station managers and admins can approve shift variances.');
        }

        $notes = $request->input('notes');

        try {
            $this->shiftService->approve($shift, $user, $notes);

            return redirect()
                ->route('shifts.show', $shift)
                ->with('success', "Shift {$shift->shift_number} has been approved.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Record cash float addition or handover/drop.
     */
    public function addCash(ShiftCashRequest $request, Shift $shift): RedirectResponse
    {
        $this->authorizeShift($request->user(), $shift);

        try {
            $this->shiftService->addCashMovement(
                shift: $shift,
                amount: (string) $request->input('amount'),
                type: (string) $request->input('type'),
                user: $request->user(),
                notes: $request->input('notes'),
            );

            return back()->with('success', 'Cash movement recorded successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Printable shift report (supports 80mm thermal and A4 print CSS).
     */
    public function print(Request $request, Shift $shift): View
    {
        $this->authorizeShift($request->user(), $shift);

        $shift->load([
            'branch',
            'user',
            'approver',
            'shiftNozzles.nozzle.fuelProduct',
            'shiftNozzles.nozzle.dispenser',
            'shiftCash.user',
        ]);

        return view('shifts.print', [
            'shift' => $shift,
        ]);
    }

    private function authorizeShift(User $user, Shift $shift): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if (! $user->canAccessBranch($shift->branch_id)) {
            abort(403, 'You do not have access to this branch.');
        }
    }

    private function authorizeShiftClose(User $user, Shift $shift): void
    {
        $this->authorizeShift($user, $shift);

        if ($shift->user_id !== $user->id && ! $user->hasPermission(PermissionList::SHIFT_CLOSE)) {
            abort(403, 'You are not authorized to close this shift.');
        }
    }
}
