<?php

namespace App\Http\Controllers;

use App\Models\InternalFuelConsumption;
use App\Models\Tank;
use App\Services\Fuel\GeneratorFuelService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternalFuelConsumptionController extends Controller
{
    private GeneratorFuelService $fuelService;

    public function __construct(GeneratorFuelService $fuelService)
    {
        $this->fuelService = $fuelService;
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * View internal fuel consumption screen
     */
    public function index(): View
    {
        $tanks = Tank::where('status', 'ACTIVE')
            ->with('fuelProduct')
            ->get();

        $consumptionTypes = $this->fuelService->getValidConsumptionTypes();

        return view('fuel.internal-consumption.index', compact('tanks', 'consumptionTypes'));
    }

    /**
     * Record internal fuel consumption
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', InternalFuelConsumption::class);

        $validated = $request->validate([
            'tank_id' => 'required|exists:tanks,id',
            'consumption_type' => 'required|in:GENERATOR,STATION_VEHICLE,TESTING,CLEANING',
            'litres_consumed' => 'required|numeric|min:0.001|max:1000',
            'consumption_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
        ]);

        try {
            $tank = Tank::findOrFail($validated['tank_id']);
            $branchId = auth()->user()->branch_id ?? 1;

            $consumption = $this->fuelService->recordConsumption(
                $tank,
                $validated['consumption_type'],
                (float) $validated['litres_consumed'],
                new \DateTime($validated['consumption_date']),
                $validated['description'],
                $branchId
            );

            return response()->json([
                'success' => true,
                'message' => 'Fuel consumption recorded successfully',
                'consumption' => [
                    'id' => $consumption->id,
                    'tank' => $tank->name,
                    'type' => $consumption->getTypeLabel(),
                    'litres' => $consumption->litres_consumed,
                    'date' => $consumption->consumption_date->format('Y-m-d'),
                    'description' => $consumption->description,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record fuel consumption', [
                'error' => $e->getMessage(),
                'tank_id' => $validated['tank_id'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unable to record fuel consumption. No changes were saved.',
            ], 400);
        }
    }

    /**
     * Get daily consumption summary
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $this->authorize('view', InternalFuelConsumption::class);

        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $branchId = auth()->user()->branch_id ?? 1;

        $summary = $this->fuelService->getDailyConsumptionSummary(
            new \DateTime($validated['date']),
            $branchId
        );

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Get consumption history
     */
    public function history(Request $request): JsonResponse
    {
        $this->authorize('view', InternalFuelConsumption::class);

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'tank_id' => 'nullable|exists:tanks,id',
            'consumption_type' => 'nullable|in:GENERATOR,STATION_VEHICLE,TESTING,CLEANING',
        ]);

        $history = $this->fuelService->getConsumptionHistory(
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date']),
            $validated['tank_id'] ?? null,
            $validated['consumption_type'] ?? null
        );

        $data = $history->map(function ($consumption) {
            return [
                'id' => $consumption->id,
                'tank' => $consumption->tank->name,
                'type' => $consumption->getTypeLabel(),
                'litres' => $consumption->litres_consumed,
                'date' => $consumption->consumption_date->format('Y-m-d'),
                'description' => $consumption->description,
                'is_reversal' => $consumption->isReversal(),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $history->count(),
            'history' => $data,
        ]);
    }

    /**
     * Get generator fuel cost for date range
     */
    public function generatorCost(Request $request): JsonResponse
    {
        $this->authorize('view', InternalFuelConsumption::class);

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $branchId = auth()->user()->branch_id ?? 1;

        $totalCost = $this->fuelService->getGeneratorFuelCost(
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date']),
            $branchId
        );

        return response()->json([
            'success' => true,
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'total_cost' => $totalCost,
        ]);
    }

    /**
     * Get monthly generator report
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $this->authorize('view', InternalFuelConsumption::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $branchId = auth()->user()->branch_id ?? 1;

        $report = $this->fuelService->getMonthlyGeneratorReport(
            $validated['year'],
            $validated['month'],
            $branchId
        );

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Get budget estimate
     */
    public function budgetEstimate(Request $request): JsonResponse
    {
        $this->authorize('view', InternalFuelConsumption::class);

        $validated = $request->validate([
            'past_months' => 'nullable|integer|min:1|max:12',
        ]);

        $branchId = auth()->user()->branch_id ?? 1;

        $estimate = $this->fuelService->estimateBudget(
            $validated['past_months'] ?? 3,
            $branchId
        );

        return response()->json([
            'success' => true,
            'estimate' => $estimate,
        ]);
    }

    /**
     * Reverse fuel consumption entry
     */
    public function reverse(InternalFuelConsumption $consumption, Request $request): JsonResponse
    {
        $this->authorize('delete', $consumption);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $reversal = $this->fuelService->reverseConsumption(
                $consumption,
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Fuel consumption reversed successfully',
                'reversal' => [
                    'id' => $reversal->id,
                    'original_id' => $consumption->id,
                    'reason' => $validated['reason'],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to reverse fuel consumption', [
                'error' => $e->getMessage(),
                'consumption_id' => $consumption->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to reverse fuel consumption. No changes were saved.',
            ], 400);
        }
    }
}
