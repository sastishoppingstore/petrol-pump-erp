<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\TankDipChart;
use App\Services\Fuel\TankCalibrationService;
use App\Support\Quantity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TankCalibrationController extends Controller
{
    public function __construct(
        protected TankCalibrationService $calibrationService
    ) {}

    public function index(Tank $tank): View
    {
        $tank->load('fuelProduct');
        $charts = $tank->dipCharts()->orderBy('dip_cm')->get();

        return view('tanks.calibration', compact('tank', 'charts'));
    }

    public function store(Request $request, Tank $tank): RedirectResponse
    {
        $validated = $request->validate([
            'dip_cm' => ['required', 'numeric', 'min:0'],
            'litres' => ['required', 'numeric', 'min:0'],
        ]);

        $this->calibrationService->setCalibrationPoint($tank, $validated['dip_cm'], $validated['litres']);

        return redirect()
            ->route('tanks.calibration', $tank)
            ->with('status', "Calibration point {$validated['dip_cm']} cm = {$validated['litres']} L saved.");
    }

    public function bulkStore(Request $request, Tank $tank): RedirectResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'string'],
        ]);

        // Lines format: "cm, litres" or "cm\tlitres"
        $lines = preg_split("/\r\n|\n|\r/", trim($validated['data']));
        $points = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = preg_split('/[\s,\t]+/', $line);
            if (count($parts) >= 2) {
                $points[] = [
                    'dip_cm' => (float) $parts[0],
                    'litres' => (float) $parts[1],
                ];
            }
        }

        $count = $this->calibrationService->bulkLoadCalibration($tank, $points);

        return redirect()
            ->route('tanks.calibration', $tank)
            ->with('status', "Successfully imported {$count} dip calibration points for Tank #{$tank->tank_number}.");
    }

    public function evaluateDip(Request $request, Tank $tank): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'dip_cm' => ['nullable', 'numeric', 'min:0'],
            'physical_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reading_date' => ['nullable', 'date'],
        ]);

        $reading = $this->calibrationService->evaluatePhysicalReading(
            tank: $tank,
            dipCm: $validated['dip_cm'] ?? null,
            physicalQuantity: $validated['physical_quantity'] ?? null,
            notes: $validated['notes'] ?? null,
            userId: auth()->id(),
            readingDate: $validated['reading_date'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'reading' => $reading,
                'calculated_litres' => $reading->physical_quantity,
                'expected_litres' => $reading->expected_quantity,
                'variance_litres' => $reading->variance_quantity,
                'variance_type' => $reading->variance_type,
                'is_within_tolerance' => $reading->is_within_tolerance,
                'allowable_loss' => $reading->allowable_loss_litres,
            ]);
        }

        $message = sprintf(
            'Physical dip recorded: %s L. Expected: %s L. Variance: %s L (%s). %s',
            Quantity::format($reading->physical_quantity),
            Quantity::format($reading->expected_quantity),
            Quantity::format($reading->variance_quantity),
            $reading->variance_type,
            $reading->is_within_tolerance
                ? 'Within 0.5% permissible loss allowance.'
                : 'WARNING: Exceeds 0.5% permissible evaporation loss threshold!'
        );

        if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), 'calibration')) {
            return redirect()
                ->route('tanks.calibration', $tank)
                ->with('status', $message);
        }

        return redirect()
            ->route('tank-readings.index')
            ->with('status', $message);
    }
}
