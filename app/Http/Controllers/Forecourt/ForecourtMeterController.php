<?php

namespace App\Http\Controllers\Forecourt;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\NozzleTest;
use App\Models\Shift;
use App\Services\Fuel\MeterService;
use App\Services\Security\BranchScopeService;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForecourtMeterController extends Controller
{
    public function __construct(
        private readonly MeterService $meterService,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Meter Reading Forecourt Wizard (Tile 1).
     * Color-coded nozzle cards by fuel with big touch keypad,
     * calibration nozzle test logging, rollover handling, and correction workflow.
     */
    public function index(Request $request): View
    {
        $branchId = (int) ($this->branchScope->activeBranchId($request) ?? $request->user()->defaultBranch()?->id);

        $nozzlesQuery = Nozzle::query()
            ->with(['fuelProduct', 'dispenser', 'tank'])
            ->where('status', Nozzle::STATUS_ACTIVE);

        if ($branchId) {
            $nozzlesQuery->where('branch_id', $branchId);
        }

        $nozzles = $nozzlesQuery->orderBy('dispenser_id')->orderBy('nozzle_number')->get();

        $activeShift = Shift::where('branch_id', $branchId)
            ->where('employee_id', $request->user()->id)
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        // Recent nozzle tests
        $recentTests = NozzleTest::query()
            ->with(['nozzle.fuelProduct', 'user'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('tested_at')
            ->limit(10)
            ->get();

        // Recent readings
        $recentReadings = MeterReading::query()
            ->with(['nozzle.fuelProduct', 'user'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->limit(15)
            ->get();

        return view('forecourt.meters', [
            'nozzles' => $nozzles,
            'branchId' => $branchId,
            'activeShift' => $activeShift,
            'recentTests' => $recentTests,
            'recentReadings' => $recentReadings,
        ]);
    }

    /**
     * Record Nozzle Calibration Test (Test Petrol / پیمانہ ٹیسٹ).
     * Fuel is returned to tank, recorded in nozzle_tests, and excluded from sales.
     */
    public function storeTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nozzle_id' => ['required', 'integer', 'exists:nozzles,id'],
            'litres' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'shift_id' => ['nullable', 'integer'],
        ]);

        $nozzle = Nozzle::findOrFail($data['nozzle_id']);

        if (! $request->user()->canAccessBranch((int) $nozzle->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $test = $this->meterService->recordNozzleTest(
                nozzle: $nozzle,
                litres: (string) $data['litres'],
                reason: $data['reason'] ?: 'Calibration test / پیمانہ ٹیسٹ',
                shiftId: $data['shift_id'] ?? null,
                actor: $request->user(),
                notes: $data['notes'] ?? null,
            );

            return back()->with('success', "Nozzle test {$test->test_number} recorded: {$test->litres} L returned to tank and excluded from sales.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Record Meter Rollover (e.g., from 99999.999 to 00005.000).
     */
    public function storeRollover(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nozzle_id' => ['required', 'integer', 'exists:nozzles,id'],
            'closing_meter' => ['required', 'numeric', 'min:0'],
            'rollover_max' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'shift_id' => ['nullable', 'integer'],
        ]);

        $nozzle = Nozzle::findOrFail($data['nozzle_id']);

        if (! $request->user()->canAccessBranch((int) $nozzle->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $reading = $this->meterService->recordRollover(
                nozzle: $nozzle,
                closingMeter: (string) $data['closing_meter'],
                rolloverMax: (string) $data['rollover_max'],
                reason: $data['reason'],
                actor: $request->user(),
                shiftId: $data['shift_id'] ?? null,
            );

            return back()->with('success', "Meter rollover recorded for Nozzle {$nozzle->nozzle_number}. Throughput: {$reading->quantity} L.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Authorised Meter Correction Workflow (requires supervisor permission).
     */
    public function storeCorrection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nozzle_id' => ['required', 'integer', 'exists:nozzles,id'],
            'new_meter' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $nozzle = Nozzle::findOrFail($data['nozzle_id']);

        if (! $request->user()->canAccessBranch((int) $nozzle->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $reading = $this->meterService->correct(
                nozzle: $nozzle,
                newMeter: (string) $data['new_meter'],
                reason: $data['reason'],
                userId: $request->user()->id,
            );

            return back()->with('success', "Meter corrected to {$reading->current_meter} for Nozzle {$nozzle->nozzle_number}.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Record Closing Meter Reading directly from Forecourt Terminal.
     */
    public function storeReading(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nozzle_id' => ['required', 'integer', 'exists:nozzles,id'],
            'closing_meter' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'shift_id' => ['nullable', 'integer'],
        ]);

        $nozzle = Nozzle::findOrFail($data['nozzle_id']);

        if (! $request->user()->canAccessBranch((int) $nozzle->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $reading = $this->meterService->recordPhysical(
                nozzle: $nozzle,
                meter: (string) $data['closing_meter'],
                type: MeterReading::TYPE_CLOSING,
                shiftId: $data['shift_id'] ?? null,
                reason: $data['reason'] ?? 'Closing meter entered via forecourt terminal',
            );

            return back()->with('success', "Closing reading recorded for Nozzle {$nozzle->nozzle_number}: {$reading->current_meter}.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }
}

