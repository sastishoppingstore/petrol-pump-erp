<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Services\Security\BranchScopeService;
use App\Services\Visualization\Tank3dGaugeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 3D tank gauges screen (/gauges).
 *
 * Authorization is handled by the route group (auth + permission:fuel.view
 * in routes/web.php / routes/fuel.php). This controller previously called
 * $this->authorize('view', Tank::class), but the app has no policy classes
 * at all (app/Policies does not exist), so every request was denied.
 *
 * It also called Tank3dGaugeService::getUpdateInterval(), which depends on
 * a global setting() helper that does not exist in this codebase and
 * fatals. The service's own documented default (5000 ms) is used instead.
 */
class Tank3dGaugeController extends Controller
{
    /** Poll interval for the gauges screen, ms (service default). */
    private const UPDATE_INTERVAL_MS = 5000;

    public function __construct(
        private readonly Tank3dGaugeService $gaugeService,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /** Active tanks visible to the current user (branch scoped). */
    private function visibleTanks(Request $request)
    {
        $query = Tank::query()
            ->where('status', Tank::STATUS_ACTIVE)
            ->with('fuelProduct')
            ->orderBy('tank_number');

        $this->branchScope->apply($query, $request->user());

        return $query->get();
    }

    /** A tank outside the user's branch scope behaves as not found. */
    private function ensureTankVisible(Tank $tank): void
    {
        $visible = $this->branchScope
            ->apply(Tank::query(), auth()->user())
            ->whereKey($tank->id)
            ->exists();

        abort_unless($visible, 404);
    }

    /**
     * Display tank gauges dashboard
     */
    public function index(Request $request): View
    {
        $tanks = $this->visibleTanks($request);

        $gauges = $this->gaugeService->generateMultipleGauges($tanks);

        return view('fuel.gauges.index', [
            'gauges' => $gauges,
            'updateInterval' => self::UPDATE_INTERVAL_MS,
        ]);
    }

    /**
     * Get gauge data for a specific tank
     */
    public function show(Tank $tank): JsonResponse
    {
        $this->ensureTankVisible($tank);

        $gaugeData = $this->gaugeService->generateGaugeData($tank);

        return response()->json([
            'success' => true,
            'gauge' => $gaugeData,
        ]);
    }

    /**
     * Get all tank gauges (AJAX)
     */
    public function getAll(Request $request): JsonResponse
    {
        $tanks = $this->visibleTanks($request);

        $gauges = $this->gaugeService->generateMultipleGauges($tanks);

        return response()->json([
            'success' => true,
            'gauges' => $gauges,
            'update_interval' => self::UPDATE_INTERVAL_MS,
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
    public function getChartData(Request $request): JsonResponse
    {
        $tanks = $this->visibleTanks($request);

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
        $this->ensureTankVisible($tank);

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
        $this->ensureTankVisible($tank);

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
                'update_interval_ms' => self::UPDATE_INTERVAL_MS,
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
