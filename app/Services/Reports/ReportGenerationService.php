<?php

namespace App\Services\Reports;

use App\Models\AutoReport;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\TankMovement;
use App\Services\Admin\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Auto-Report Generation Engine
 * Generates 12h, 24h, 7d, 15d, 30d reports with real data
 */
class ReportGenerationService
{
    protected SettingsService $settings;
    
    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }
    
    /**
     * Period => settings key + fallback default (agar settings row
     * mojood hi na ho to bhi 24h/7d lazmi generate hon).
     */
    private const PERIOD_SETTINGS = [
        '12h' => ['key' => 'auto_report_12h', 'default' => true],
        '24h' => ['key' => 'auto_report_24h', 'default' => true],
        '7d' => ['key' => 'auto_report_7d', 'default' => true],
        '15d' => ['key' => 'auto_report_15d', 'default' => false],
        '30d' => ['key' => 'auto_report_30d', 'default' => true],
    ];

    /**
     * Generate all enabled reports for all branches
     * Called by scheduler every hour.
     *
     * @return int Number of reports generated/refreshed
     */
    public function generateAllScheduledReports()
    {
        // Branch::STATUS_ACTIVE = 'ACTIVE' — chhota 'active' likhne se
        // case-sensitive collations par ZERO branches milti theen.
        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();

        $generated = 0;

        foreach ($branches as $branch) {
            $generated += $this->generateReportsForBranch($branch);
        }

        return $generated;
    }

    /**
     * Generate enabled reports for a specific branch
     *
     * @return int Number of reports generated/refreshed
     */
    public function generateReportsForBranch(Branch $branch)
    {
        $generated = 0;

        foreach (self::PERIOD_SETTINGS as $period => $config) {
            $enabled = filter_var(
                $this->settings->get($config['key'], $config['default']),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($enabled && $this->generateReport($branch, $period)) {
                $generated++;
            }
        }

        return $generated;
    }
    
    /**
     * Generate single report
     */
    public function generateReport(Branch $branch, $period = '24h')
    {
        try {
            $now = now();
            $reportDate = $now->copy()->startOfDay();
            
            // Date range based on period
            $dateRange = $this->getDateRange($period, $now);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
            
            // Generate summary
            $summary = $this->generateSummary($branch, $startDate, $endDate);
            
            // Save or update report.
            // NOTE: summary_json model par 'array' cast hai — array hi
            // pass karna hai. json_encode() string pass karne se JSON
            // double-encode ho jata tha aur dashboard widget crash hoti thi.
            $report = AutoReport::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'period' => $period,
                    'report_date' => $reportDate,
                ],
                [
                    'summary_json' => $summary,
                    'status' => 'generated',
                ]
            );
            
            Log::info("Auto-report generated: {$branch->name} - {$period}", [
                'report_id' => $report->id,
                'summary' => $summary,
            ]);
            
            return $report;
        } catch (\Exception $e) {
            Log::error("Report generation failed: {$period}", [
                'branch' => $branch->name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Calculate date range for period
     */
    private function getDateRange($period, $now)
    {
        return match ($period) {
            '12h' => [
                'start' => $now->copy()->subHours(12),
                'end' => $now,
            ],
            '24h' => [
                'start' => $now->copy()->subDay(),
                'end' => $now,
            ],
            '7d' => [
                'start' => $now->copy()->subDays(7),
                'end' => $now,
            ],
            '15d' => [
                'start' => $now->copy()->subDays(15),
                'end' => $now,
            ],
            '30d' => [
                'start' => $now->copy()->subDays(30),
                'end' => $now,
            ],
            default => [
                'start' => $now->copy()->subDay(),
                'end' => $now,
            ]
        };
    }
    
    /**
     * Generate comprehensive report summary.
     *
     * Tamam figures RAW numeric (float/int) return hote hain — formatting
     * sirf display layer (Blade) ka kaam hai. Pehle number_format() yahin
     * lag jata tha jis se widget me dobara format karne par values
     * "12,345.00" -> 12 ho jati theen.
     *
     * Asal columns (verify kiye gaye):
     * - sales: total, total_litres, sale_date, status
     * - sale_payments: method (CASH/CARD/CREDIT/...), amount — sales par
     *   koi payment_method column NAHI hai
     * - sale_items: fuel_product_id, litres, amount, cost_rate — sales par
     *   koi fuel_product_id column NAHI hai
     * - expenses: date ('expense_date' column mojood NAHI — ye purani
     *   query har report ko SQL error se fail kar deti thi)
     * - tank_movements: branch_id, type, quantity
     */
    private function generateSummary(Branch $branch, $startDate, $endDate)
    {
        $completedSales = fn ($query) => $query
            ->where('sales.branch_id', $branch->id)
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sale_date', [$startDate, $endDate]);

        // --- Sales totals -------------------------------------------------
        $salesAgg = Sale::query()
            ->where('branch_id', $branch->id)
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as txn_count, COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(total_litres), 0) as total_litres')
            ->first();

        $transactionCount = (int) ($salesAgg->txn_count ?? 0);
        $totalSales = (float) ($salesAgg->total_amount ?? 0);
        $totalLitres = (float) ($salesAgg->total_litres ?? 0);

        // --- Payment breakdown (sale_payments.method se) ------------------
        $paymentTotals = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where($completedSales)
            ->groupBy('sale_payments.method')
            ->selectRaw('sale_payments.method as method, COALESCE(SUM(sale_payments.amount), 0) as total')
            ->pluck('total', 'method');

        $cashSales = (float) ($paymentTotals[SalePayment::METHOD_CASH] ?? 0);
        $cardSales = (float) ($paymentTotals[SalePayment::METHOD_CARD] ?? 0);
        $creditSales = (float) ($paymentTotals[SalePayment::METHOD_CREDIT] ?? 0);
        $otherSales = (float) collect($paymentTotals)
            ->except([SalePayment::METHOD_CASH, SalePayment::METHOD_CARD, SalePayment::METHOD_CREDIT])
            ->sum();

        // --- COGS (litres x historical cost_rate) -------------------------
        $cogsRow = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where($completedSales)
            ->selectRaw('COALESCE(SUM(sale_items.litres * sale_items.cost_rate), 0) as cogs')
            ->first();
        $cogs = (float) ($cogsRow->cogs ?? 0);

        $grossMargin = $totalSales - $cogs;

        // --- Expenses (column: date; VOID shamil nahi) --------------------
        $expenses = (float) Expense::query()
            ->where('branch_id', $branch->id)
            ->where('status', '!=', 'VOID')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $netProfit = $grossMargin - $expenses;

        // --- Stock movement ------------------------------------------------
        $movementTotals = TankMovement::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('type')
            ->selectRaw('type, COALESCE(SUM(quantity), 0) as total')
            ->pluck('total', 'type');

        $stockPurchases = (float) ($movementTotals[TankMovement::TYPE_PURCHASE] ?? 0);
        $stockSales = (float) ($movementTotals[TankMovement::TYPE_SALE] ?? 0);
        $stockVariance = (float) collect($movementTotals)
            ->only([
                TankMovement::TYPE_LOSS,
                TankMovement::TYPE_ADJUSTMENT_OUT,
                TankMovement::TYPE_ADJUSTMENT_IN,
                TankMovement::TYPE_CORRECTION,
            ])
            ->sum();

        // --- Fuel breakdown (sale_items se) --------------------------------
        $fuelBreakdown = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('fuel_products', 'fuel_products.id', '=', 'sale_items.fuel_product_id')
            ->where($completedSales)
            ->groupBy('sale_items.fuel_product_id', 'fuel_products.name')
            ->selectRaw('COALESCE(fuel_products.name, \'Unknown\') as fuel, COALESCE(SUM(sale_items.litres), 0) as litres, COALESCE(SUM(sale_items.amount), 0) as revenue')
            ->orderByDesc('litres')
            ->get()
            ->map(fn ($row) => [
                'fuel' => $row->fuel,
                'litres' => round((float) $row->litres, 3),
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();

        return [
            'period_start' => $startDate->toDateTimeString(),
            'period_end' => $endDate->toDateTimeString(),
            'branch' => $branch->name,
            'generated_at' => now()->toDateTimeString(),

            // Sales
            'total_sales' => round($totalSales, 2),
            'total_litres' => round($totalLitres, 3),
            'cash_sales' => round($cashSales, 2),
            'card_sales' => round($cardSales, 2),
            'credit_sales' => round($creditSales, 2),
            'other_sales' => round($otherSales, 2),
            'avg_price_per_liter' => $totalLitres > 0 ? round($totalSales / $totalLitres, 2) : 0,
            'transaction_count' => $transactionCount,

            // Profitability
            'total_revenue' => round($totalSales, 2),
            'cogs' => round($cogs, 2),
            'gross_margin' => round($grossMargin, 2),
            'gross_margin_percentage' => $totalSales > 0 ? round(($grossMargin / $totalSales) * 100, 2) : 0,
            'expenses' => round($expenses, 2),
            'net_profit' => round($netProfit, 2),
            'net_profit_percentage' => $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 2) : 0,

            // Stock
            'purchases' => round($stockPurchases, 3),
            'sales' => round($stockSales, 3),
            'variance' => round($stockVariance, 3),

            // Fuel breakdown
            'fuel_breakdown' => $fuelBreakdown,
        ];
    }
    
    /**
     * Get latest report for display
     */
    public function getLatestReport(Branch $branch, $period = '24h')
    {
        return AutoReport::where('branch_id', $branch->id)
            ->where('period', $period)
            ->latest('report_date')
            ->first();
    }
    
    /**
     * Get report history
     */
    public function getReportHistory(Branch $branch, $period = '24h', $days = 30)
    {
        return AutoReport::where('branch_id', $branch->id)
            ->where('period', $period)
            ->where('report_date', '>=', now()->subDays($days))
            ->orderBy('report_date', 'desc')
            ->get();
    }
}
