<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Services\Visualization\Tank3dGaugeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Tank3dGaugeController extends Controller
{
    private Tank3dGaugeService $gaugeService;

    public function __construct(Tank3dGaugeService $gaugeService)
    {
        $this->gaugeService = $gaugeService;
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Display tank gauges dashboard
     */
    public function index(): View
    {
        $this->authorize('view', Tank::class);

        $tanks = Tank::where('status', 'ACTIVE')
            ->with('fuelProduct')
            ->get();

        $gauges = $this->gaugeService->generateMultipleGauges($tanks);
        $updateInterval = $this->gaugeService->getUpdateInterval();

        return view('fuel.gauges.index', compact('gauges', 'updateInterval'));
    }

    /**
     * Get gauge data for a specific tank
     */
    public function show(Tank $tank): JsonResponse
    {
        $this->authorize('view', $tank);

        $gaugeData = $this->gaugeService->generateGaugeData($tank);

        return response()->json([
            'success' => true,
            'gauge' => $gaugeData,
        ]);
    }

    /**
     * Get all tank gauges (AJAX)
     */
    public function getAll(): JsonResponse
    {
        $this->authorize('view', Tank::class);

        $tanks = Tank::where('status', 'ACTIVE')
            ->with('fuelProduct')
            ->get();

        $gauges = $this->gaugeService->generateMultipleGauges($tanks);

        return response()->json([
            'success' => true,
            'gauges' => $gauges,
            'update_interval' => $this->gaugeService->getUpdateInterval(),
        ]);
    }

    /**
     * Get wave animation keyframes
     */
    public function getWaveKeyframes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'duration_ms' => 'nullable|integer|min:100|max:10000',
        ]);

        $durationMs = $validated['duration_ms'] ?? 2000;
        $keyframes = $this->gaugeService->getWaveKeyframes($durationMs);

        return response()->json([
            'success' => true,
            'keyframes' => $keyframes,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Get wave animation CSS
     */
    public function getWaveAnimationCss(): JsonResponse
    {
        $css = $this->gaugeService->getWaveAnimationCss('tank-wave-animation');

        return response()->json([
            'success' => true,
            'css' => $css,
        ]);
    }

    /**
     * Get chart.js compatible data
     */
    public function getChartData(): JsonResponse
    {
        $this->authorize('view', Tank::class);

        $tanks = Tank::where('status', 'ACTIVE')
            ->with('fuelProduct')
            ->get();

        $chartData = $this->gaugeService->getChartJsData($tanks);

        return response()->json([
            'success' => true,
            'chart' => $chartData,
        ]);
    }

    /**
     * Get tooltip for tank gauge
     */
    public function getTooltip(Tank $tank): JsonResponse
    {
        $this->authorize('view', $tank);

        $tooltip = $this->gaugeService->generateTooltip($tank);

        return response()->json([
            'success' => true,
            'tooltip' => $tooltip,
        ]);
    }

    /**
     * Get real-time gauge data stream (polling endpoint)
     */
    public function getRealtime(Tank $tank): JsonResponse
    {
        $this->authorize('view', $tank);

        $tank->refresh(); // Refresh from DB

        $gaugeData = $this->gaugeService->generateGaugeData($tank);
        $tooltip = $this->gaugeService->generateTooltip($tank);

        return response()->json([
            'success' => true,
            'tank_id' => $tank->id,
            'gauge' => $gaugeData,
            'tooltip' => $tooltip,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get gauge visual configuration (colors, sizes, etc)
     */
    public function getVisualConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'config' => [
                'canvas_width' => 400,
                'canvas_height' => 500,
                'wave_amplitude_base' => 10,
                'wave_frequency' => 0.015,
                'wave_speed' => 0.02,
                'animation_duration_ms' => 2000,
                'update_interval_ms' => $this->gaugeService->getUpdateInterval(),
                'colors' => [
                    'optimal' => '#27ae60',
                    'warning' => '#f39c12',
                    'critical' => '#e74c3c',
                    'border' => '#333333',
                    'background' => '#f5f5f5',
                ],
            ],
        ]);
    }
}
