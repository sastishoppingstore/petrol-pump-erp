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
use App\Services\Shift\ShiftClosingService;
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
        private readonly ShiftClosingService $shiftClosingService,
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

        // Handover sign-off: is branch ki pichli band shift ka cash
        // handover pending ho to open screen par accept card dikhta hai.
        $pendingHandover = null;
        if ($targetBranchId) {
            $targetBranch = $branches->firstWhere('id', (int) $targetBranchId) ?? Branch::find($targetBranchId);
            if ($targetBranch) {
                $pendingHandover = $this->shiftService->pendingHandover($targetBranch);
            }
        }

        return view('shifts.create', [
            'branches' => $branches,
            'targetBranchId' => $targetBranchId,
            'employees' => $employees,
            'nozzles' => $nozzles,
            'busyNozzleIds' => $busyNozzleIds,
            'pendingHandover' => $pendingHandover,
        ]);
    }

    /**
     * Incoming cashier pichli band shift ka cash handover accept karta
     * hai (counted cash + apna PIN). ShiftService tamam usool lagata hai.
     */
    public function acceptHandover(Request $request, Shift $shift): RedirectResponse
    {
        if (! $request->user()->canAccessBranch((int) $shift->branch_id)) {
            abort(403, 'You do not have access to this branch.');
        }

        $data = $request->validate([
            'handover_cash_counted' => ['required', 'numeric', 'min:0'],
            'pin' => ['required', 'string', 'max:20'],
        ]);

        try {
            $accepted = $this->shiftService->acceptHandover(
                closedShift: $shift,
                acceptor: $request->user(),
                countedCash: (string) $data['handover_cash_counted'],
                pin: (string) $data['pin'],
            );

            return redirect()
                ->route('shifts.create', ['branch_id' => $accepted->branch_id])
                ->with('success', "Handover for shift {$accepted->shift_number} accepted. You can now open the new shift.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Open shift action.
     */
    public function store(OpenShiftRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = User::findOrFail($validated['user_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        $nozzleIds = [];
        $openingMeters = [];
        foreach ($validated['nozzles'] as $key => $val) {
            if (is_array($val)) {
                $nid = (int) ($val['nozzle_id'] ?? $key);
                if ($nid) {
                    $nozzleIds[] = $nid;
                    if (isset($val['opening_meter']) && $val['opening_meter'] !== '') {
                        $openingMeters[$nid] = (string) $val['opening_meter'];
                    }
                }
            } else {
                $nid = (int) $val;
                if ($nid) {
                    $nozzleIds[] = $nid;
                }
            }
        }

        try {
            $shift = $this->shiftService->open(
                employee: $employee,
                branch: $branch,
                openingCash: (string) $validated['opening_cash'],
                nozzleIds: $nozzleIds,
                openingMeters: $openingMeters,
                notes: $validated['opening_notes'] ?? null,
            );

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

        $validated = $request->validated();
        $closingMeters = [];
        foreach ($validated['nozzles'] as $key => $n) {
            if (is_array($n)) {
                $nid = $n['nozzle_id'] ?? $key;
                if (isset($n['closing_meter'])) {
                    $closingMeters[$nid] = (string) $n['closing_meter'];
                }
            } else {
                $closingMeters[$key] = (string) $n;
            }
        }

        try {
            $closedShift = $this->shiftClosingService->close(
                shift: $shift,
                closingMeters: $closingMeters,
                actualCash: (string) $validated['actual_cash'],
                actor: $request->user(),
                notes: $validated['closing_notes'] ?? null,
                cardSettlement: (string) ($validated['card_total'] ?? '0'),
            );

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
            $this->shiftClosingService->approve($shift, $user, $notes);

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

        if ((int) ($shift->employee_id ?? $shift->user_id) !== (int) $user->id && ! $user->hasPermission(PermissionList::SHIFT_CLOSE)) {
            abort(403, 'You are not authorized to close this shift.');
        }
    }
}
