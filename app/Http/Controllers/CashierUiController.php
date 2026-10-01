<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Shift;
use App\Services\Pos\CashierUiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashierUiController extends Controller
{
    private CashierUiService $uiService;

    public function __construct(CashierUiService $uiService)
    {
        $this->uiService = $uiService;
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Display cashier POS interface (picture-first UI)
     */
    public function index(): View
    {
        $shift = Shift::where('employee_id', auth()->id())
            ->where('status', 'OPEN')
            ->latest()
            ->first();

        if (!$shift) {
            abort(403, 'Aap ki koi shift open nahi hai. Pehle Shift kholen, phir Cashier screen khulegi. (No active shift — please open a shift first, then the cashier screen will work.)');
        }

        $dashboardData = $this->uiService->getDashboardData($shift, 1); // Cache for 1 minute
        $nozzleStatus = $this->uiService->getNozzleStatus($shift);
        $keypadConfig = $this->uiService->getKeypadConfig();
        $banknoteDenominations = $this->uiService->getBanknoteDenominations();
        $paymentMethods = $this->uiService->getPaymentMethods();
        $signatureConfig = $this->uiService->getSignatureCaptureConfig();
        $themeConfig = $this->uiService->getThemeConfig();

        // Credit (udhaar) sales on the cashier screen need a customer list,
        // and the sale form needs the shift's branch id.
        $customers = Customer::query()
            ->where('status', Customer::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'code']);
        $branchId = (int) $shift->branch_id;

        return view('pos.cashier-ui.index', compact(
            'shift',
            'dashboardData',
            'nozzleStatus',
            'keypadConfig',
            'banknoteDenominations',
            'paymentMethods',
            'signatureConfig',
            'themeConfig',
            'customers',
            'branchId'
        ));
    }

    /**
     * Get live dashboard data (AJAX)
     */
    public function getDashboard(Shift $shift): JsonResponse
    {
        $this->authorize('view', $shift);

        $dashboardData = $this->uiService->getDashboardData($shift, 1);

        return response()->json([
            'success' => true,
            'data' => $dashboardData,
        ]);
    }

    /**
     * Get nozzle status (AJAX)
     */
    public function getNozzleStatus(Shift $shift): JsonResponse
    {
        $this->authorize('view', $shift);

        $nozzleStatus = $this->uiService->getNozzleStatus($shift);

        return response()->json([
            'success' => true,
            'nozzles' => $nozzleStatus,
        ]);
    }

    /**
     * Get keypad configuration
     */
    public function getKeypad(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'keypad' => $this->uiService->getKeypadConfig(),
        ]);
    }

    /**
     * Get banknote denominations
     */
    public function getBanknotes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'denominations' => $this->uiService->getBanknoteDenominations(),
        ]);
    }

    /**
     * Calculate banknote total
     */
    public function calculateBanknoteTotal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'denominations' => 'required|array',
            'denominations.*.denomination' => 'required|integer',
            'denominations.*.count' => 'required|integer|min:0',
        ]);

        $total = $this->uiService->calculateBanknoteTotal($validated['denominations']);

        return response()->json([
            'success' => true,
            'total' => $total,
        ]);
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'methods' => $this->uiService->getPaymentMethods(),
        ]);
    }

    /**
     * Get signature capture config
     */
    public function getSignatureConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'config' => $this->uiService->getSignatureCaptureConfig(),
        ]);
    }

    /**
     * Get theme/style configuration
     */
    public function getTheme(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'theme' => $this->uiService->getThemeConfig(),
        ]);
    }

    /**
     * Refresh dashboard cache
     */
    public function refreshDashboard(Shift $shift): JsonResponse
    {
        $this->authorize('view', $shift);

        $this->uiService->invalidateDashboardCache($shift);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache cleared',
        ]);
    }
}
