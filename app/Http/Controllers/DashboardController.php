<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\User;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 1 dashboard: real counts of what exists in the database.
 * Phase 10 replaces the body with sales/expense/margin widgets and charts.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $branchQuery = Branch::query();
        // The branches table keys on "id", not the usual "branch_id" column.
        $this->branchScope->apply($branchQuery, $user, 'branches.id');

        $activeBranchId = $this->branchScope->activeBranchId($request);
        
        // Get auto-reports for dashboard
        $reportService = app(\App\Services\Reports\ReportGenerationService::class);
        $reports = [];
        
        if ($activeBranchId) {
            $branch = Branch::find($activeBranchId);
            if ($branch) {
                foreach (['12h', '24h', '7d', '15d', '30d'] as $period) {
                    $reports[$period] = $reportService->getLatestReport($branch, $period);
                }
            }
        }

        return view('dashboard', [
            'branchCount' => (clone $branchQuery)->where('status', Branch::STATUS_ACTIVE)->count(),
            'userCount' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
            'roleCount' => Role::query()->where('status', 'ACTIVE')->count(),
            'activeBranch' => $this->branchScope->activeBranchId($request),
            'activeBranchName' => $activeBranchId
                ? Branch::find($activeBranchId)?->name
                : null,
            'reports' => $reports,
            ...$this->cinematicAnalytics($request, $user),
        ]);
    }

    /**
     * Cinematic dashboard analytics — pichle 30 din ki asli (COMPLETED)
     * sales se charts aur aaj ke KPI tiles ka data.
     *
     * Chart payload format dashboard-motion.js presets ke mutabiq hai:
     * ['labels' => [...], 'datasets' => [['label' => ..., 'data' => [...]]]]
     * jo Blade me data-chart='@json(...)' se canvas tak jata hai.
     * Khali database par bhi tamam arrays mojood rehte hain taake
     * page/charts khali render hon — page kabhi nahi toot-ti.
     */
    private function cinematicAnalytics(Request $request, ?User $user): array
    {
        $until = now()->endOfDay();
        $since = now()->startOfDay()->subDays(29);

        // --- Sales trend: rozana kul litres (film-line chart) ---
        $trendQuery = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('sale_date', [$since, $until]);
        $this->branchScope->apply($trendQuery, $user);

        $daily = $trendQuery
            ->selectRaw('DATE(sale_date) AS day, SUM(total_litres) AS litres')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $trendLabels = [];
        $trendLitres = [];
        for ($day = $since->copy(); $day->lte($until); $day->addDay()) {
            $row = $daily->get($day->toDateString());
            $trendLabels[] = $day->format('d M');
            $trendLitres[] = $row ? round((float) $row->litres, 3) : 0;
        }

        // --- Fuel-wise sales: har product ke kul litres (gradient bars) ---
        $fuelQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('fuel_products', 'fuel_products.id', '=', 'sale_items.fuel_product_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sale_date', [$since, $until]);
        $this->branchScope->apply($fuelQuery, $user, 'sale_items.branch_id');

        $fuelRows = $fuelQuery
            ->selectRaw('fuel_products.name AS product_name, SUM(sale_items.litres) AS litres')
            ->groupBy('fuel_products.id', 'fuel_products.name')
            ->orderByDesc('litres')
            ->get();

        // --- Payment mix: har tareeqe ki kul raqam (donut) ---
        // sale_payments par branch_id nahi hota — scope parent sale se lagta hai.
        $paymentQuery = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sale_date', [$since, $until]);
        $this->branchScope->apply($paymentQuery, $user, 'sales.branch_id');

        $paymentRows = $paymentQuery
            ->selectRaw('sale_payments.method AS method, SUM(sale_payments.amount) AS amount')
            ->groupBy('sale_payments.method')
            ->orderByDesc('amount')
            ->get();

        $methodLabels = SalePayment::methods();

        // --- Aaj ke KPIs (count-up tiles) ---
        $todayQuery = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('sale_date', [now()->startOfDay(), $until]);
        $this->branchScope->apply($todayQuery, $user);

        $today = $todayQuery
            ->selectRaw('COUNT(*) AS txns, COALESCE(SUM(total), 0) AS amount, COALESCE(SUM(total_litres), 0) AS litres')
            ->first();

        $shiftQuery = Shift::query()->where('status', Shift::STATUS_OPEN);
        $this->branchScope->apply($shiftQuery, $user);

        return [
            'chartSalesTrend' => [
                'labels' => $trendLabels,
                'datasets' => [
                    ['label' => 'Litres Sold (لیٹر فروخت)', 'data' => $trendLitres],
                ],
            ],
            'chartFuelMix' => [
                'labels' => $fuelRows->map(fn ($r) => $r->product_name)->all(),
                'datasets' => [
                    ['label' => 'Litres (لیٹرز)', 'data' => $fuelRows->map(fn ($r) => round((float) $r->litres, 3))->all()],
                ],
            ],
            'chartPaymentMix' => [
                'labels' => $paymentRows->map(fn ($r) => $methodLabels[$r->method] ?? $r->method)->all(),
                'datasets' => [
                    ['label' => 'Amount Rs (رقم)', 'data' => $paymentRows->map(fn ($r) => round((float) $r->amount, 2))->all()],
                ],
            ],
            'paymentMixTotal' => round((float) $paymentRows->sum('amount'), 2),
            'kpiTodaySales' => round((float) ($today->amount ?? 0), 2),
            'kpiTodayLitres' => round((float) ($today->litres ?? 0), 3),
            'kpiTodayTransactions' => (int) ($today->txns ?? 0),
            'kpiActiveShifts' => $shiftQuery->count(),
        ];
    }
}
