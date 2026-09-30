<?php

namespace App\Services\Reports;

use App\Models\AutoReport;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\TankMovement;
use App\Services\Admin\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Generate all enabled reports for all branches
     * Called by scheduler every hour
     */
    public function generateAllScheduledReports()
    {
        $branches = Branch::where('status', 'active')->get();
        
        foreach ($branches as $branch) {
            $this->generateReportsForBranch($branch);
        }
    }
    
    /**
     * Generate enabled reports for a specific branch
     */
    public function generateReportsForBranch(Branch $branch)
    {
        $periods = [
            '12h' => $this->settings->get('auto_report_12h'),
            '24h' => $this->settings->get('auto_report_24h'),
            '7d' => $this->settings->get('auto_report_7d'),
            '15d' => $this->settings->get('auto_report_15d'),
            '30d' => $this->settings->get('auto_report_30d'),
        ];
        
        foreach ($periods as $period => $enabled) {
            if ($enabled) {
                $this->generateReport($branch, $period);
            }
        }
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
            
            // Save or update report
            $report = AutoReport::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'period' => $period,
                    'report_date' => $reportDate,
                ],
                [
                    'summary_json' => json_encode($summary),
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
     * Generate comprehensive report summary
     */
    private function generateSummary(Branch $branch, $startDate, $endDate)
    {
        // Sales data
        $sales = Sale::where('branch_id', $branch->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->get();
        
        $totalSales = $sales->sum('total');
        $totalLitres = $sales->sum(function ($sale) {
            return $sale->items->sum('litres');
        });
        
        $cashSales = $sales->where('payment_method', 'cash')->sum('total');
        $cardSales = $sales->where('payment_method', 'card')->sum('total');
        $creditSales = $sales->where('payment_method', 'credit')->sum('total');
        
        // Calculate COGS
        $cogs = $sales->sum(function ($sale) {
            return $sale->items->sum(function ($item) {
                return $item->litres * $item->cost_rate;
            });
        });
        
        $grossMargin = $totalSales - $cogs;
        
        // Expenses
        $expenses = Expense::where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');
        
        // Net profit
        $netProfit = $grossMargin - $expenses;
        
        // Stock movement
        $stockPurchases = TankMovement::where('branch_id', $branch->id)
            ->where('type', 'PURCHASE')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');
        
        $stockSales = TankMovement::where('branch_id', $branch->id)
            ->where('type', 'SALE')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');
        
        $stockVariance = TankMovement::where('branch_id', $branch->id)
            ->whereIn('type', ['LOSS', 'ADJUSTMENT_OUT', 'ADJUSTMENT_IN', 'CORRECTION'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');
        
        // Fuel breakdown
        $fuelBreakdown = $sales->groupBy('fuel_product_id')->map(function ($items) {
            return [
                'fuel' => $items->first()->fuel?->name ?? 'Unknown',
                'litres' => $items->sum(function ($sale) {
                    return $sale->items->sum('litres');
                }),
                'revenue' => $items->sum('total'),
            ];
        });
        
        return [
            'period_start' => $startDate->toDateTimeString(),
            'period_end' => $endDate->toDateTimeString(),
            'branch' => $branch->name,
            'generated_at' => now()->toDateTimeString(),
            
            // Sales
            'total_sales' => number_format($totalSales, 2),
            'total_litres' => number_format($totalLitres, 3),
            'cash_sales' => number_format($cashSales, 2),
            'card_sales' => number_format($cardSales, 2),
            'credit_sales' => number_format($creditSales, 2),
            'avg_price_per_liter' => $totalLitres > 0 ? number_format($totalSales / $totalLitres, 2) : 0,
            'transaction_count' => $sales->count(),
            
            // Profitability
            'total_revenue' => number_format($totalSales, 2),
            'cogs' => number_format($cogs, 2),
            'gross_margin' => number_format($grossMargin, 2),
            'gross_margin_percentage' => $totalSales > 0 ? number_format(($grossMargin / $totalSales) * 100, 2) : 0,
            'expenses' => number_format($expenses, 2),
            'net_profit' => number_format($netProfit, 2),
            'net_profit_percentage' => $totalSales > 0 ? number_format(($netProfit / $totalSales) * 100, 2) : 0,
            
            // Stock
            'purchases' => number_format($stockPurchases, 3),
            'sales' => number_format($stockSales, 3),
            'variance' => number_format($stockVariance, 3),
            
            // Fuel breakdown
            'fuel_breakdown' => $fuelBreakdown->values(),
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
