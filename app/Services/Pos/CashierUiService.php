<?php

namespace App\Services\Pos;

use App\Models\Sale;
use App\Models\Nozzle;
use App\Models\Tank;
use App\Models\FuelProduct;
use App\Models\Shift;
use Illuminate\Support\Facades\Cache;

class CashierUiService
{
    /**
     * Get dashboard data for cashier UI (4 massive tiles)
     * Tiles: Sales Today, Fuel Sold, Collections, Margin
     */
    public function getDashboardData(Shift $shift, ?int $cacheMinutes = 5): array
    {
        $cacheKey = "cashier_dashboard_{$shift->id}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes ?? 5), function () use ($shift) {
            $today = now()->toDateString();

            // Tile 1: Total Sales Today
            $todaySales = Sale::where('shift_id', $shift->id)
                ->whereDate('sale_date', $today)
                ->where('status', 'COMPLETED')
                ->sum('total');

            // Tile 2: Total Fuel Sold (Litres)
            $fuelSold = Sale::where('shift_id', $shift->id)
                ->whereDate('sale_date', $today)
                ->where('status', 'COMPLETED')
                ->with('items')
                ->get()
                ->sum(function ($sale) {
                    return $sale->items->sum('litres');
                });

            // Tile 3: Total Collections (Cash + Card + Bank)
            $collections = $this->getTodayCollections($shift);

            // Tile 4: Gross Margin
            $costOfGoodsToday = $this->calculateCostOfGoodsSold($shift, $today);
            $margin = $todaySales - $costOfGoodsToday;
            $marginPercent = $todaySales > 0 ? round(($margin / $todaySales) * 100, 2) : 0;

            return [
                'tiles' => [
                    'sales_today' => [
                        'label' => 'Sales Today',
                        'value' => round($todaySales, 2),
                        'currency' => 'PKR',
                        'icon' => 'chart-line',
                        'color' => '#2ecc71', // green
                        'trend' => $this->calculateTrend('sales', $shift),
                    ],
                    'fuel_sold' => [
                        'label' => 'Fuel Sold',
                        'value' => round($fuelSold, 3),
                        'unit' => 'Litres',
                        'icon' => 'pump',
                        'color' => '#3498db', // blue
                        'trend' => $this->calculateTrend('fuel', $shift),
                    ],
                    'collections' => [
                        'label' => 'Collections',
                        'value' => round($collections['total'], 2),
                        'currency' => 'PKR',
                        'icon' => 'money',
                        'color' => '#f39c12', // orange
                        'breakdown' => $collections['breakdown'],
                    ],
                    'margin' => [
                        'label' => 'Gross Margin',
                        'value' => round($margin, 2),
                        'percent' => $marginPercent,
                        'currency' => 'PKR',
                        'icon' => 'chart-pie',
                        'color' => $marginPercent >= 15 ? '#27ae60' : '#e74c3c', // green if good, red if bad
                    ],
                ],
                'shift_info' => [
                    'shift_number' => $shift->shift_number,
                    'opened_at' => $shift->opened_at->format('H:i'),
                    'opening_cash' => $shift->opening_cash,
                    'employee' => $shift->employee->name ?? 'Unknown',
                ],
                'timestamp' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * Get today's collections breakdown
     */
    private function getTodayCollections(Shift $shift): array
    {
        $today = now()->toDateString();

        $sales = Sale::where('shift_id', $shift->id)
            ->whereDate('sale_date', $today)
            ->where('status', 'COMPLETED')
            ->with('payments')
            ->get();

        $breakdown = [
            'CASH' => 0,
            'CARD' => 0,
            'BANK_TRANSFER' => 0,
            'MOBILE_WALLET' => 0,
            'CREDIT' => 0,
            'OTHER' => 0,
        ];

        foreach ($sales as $sale) {
            foreach ($sale->payments as $payment) {
                $method = $payment->payment_method ?? 'OTHER';
                if (isset($breakdown[$method])) {
                    $breakdown[$method] += $payment->amount;
                } else {
                    $breakdown[$method] = $payment->amount;
                }
            }
        }

        return [
            'total' => array_sum($breakdown),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate cost of goods sold for the shift
     */
    private function calculateCostOfGoodsSold(Shift $shift, string $date): float
    {
        $sales = Sale::where('shift_id', $shift->id)
            ->whereDate('sale_date', $date)
            ->where('status', 'COMPLETED')
            ->with('items')
            ->get();

        $cogs = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $cogs += ($item->litres * ($item->cost_rate ?? 0));
            }
        }

        return $cogs;
    }

    /**
     * Calculate trend (% change vs previous period)
     */
    private function calculateTrend(string $metric, Shift $shift): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($metric === 'sales') {
            $todayValue = Sale::where('shift_id', $shift->id)
                ->whereDate('sale_date', $today)
                ->where('status', 'COMPLETED')
                ->sum('total');

            $yesterdayValue = Sale::where('shift_id', $shift->id)
                ->whereDate('sale_date', $yesterday)
                ->where('status', 'COMPLETED')
                ->sum('total');
        } elseif ($metric === 'fuel') {
            $todayValue = Sale::where('shift_id', $shift->id)
                ->whereDate('sale_date', $today)
                ->where('status', 'COMPLETED')
                ->with('items')
                ->get()
                ->sum(function ($s) {
                    return $s->items->sum('litres');
                });

            $yesterdayValue = Sale::where('shift_id', $shift->id)
                ->whereDate('sale_date', $yesterday)
                ->where('status', 'COMPLETED')
                ->with('items')
                ->get()
                ->sum(function ($s) {
                    return $s->items->sum('litres');
                });
        } else {
            return ['percent' => 0, 'direction' => 'neutral'];
        }

        if ($yesterdayValue == 0) {
            return ['percent' => 0, 'direction' => 'neutral'];
        }

        $percent = round((($todayValue - $yesterdayValue) / $yesterdayValue) * 100, 2);
        $direction = $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'neutral');

        return ['percent' => abs($percent), 'direction' => $direction];
    }

    /**
     * Get nozzle status for cashier (quick access)
     */
    public function getNozzleStatus(Shift $shift): array
    {
        $nozzles = Nozzle::whereIn('id', $shift->nozzles->pluck('id'))
            ->with('tank', 'fuelProduct')
            ->get();

        return $nozzles->map(function ($nozzle) {
            return [
                'id' => $nozzle->id,
                'nozzle_number' => $nozzle->nozzle_number,
                'fuel' => $nozzle->fuelProduct->name ?? 'Unknown',
                'fuel_code' => $nozzle->fuelProduct->code ?? '',
                'current_meter' => $nozzle->current_meter,
                'tank_stock' => $nozzle->tank->current_stock ?? 0,
                'tank_capacity' => $nozzle->tank->capacity ?? 0,
                'stock_percent' => $nozzle->tank->capacity > 0 
                    ? round(($nozzle->tank->current_stock / $nozzle->tank->capacity) * 100, 1)
                    : 0,
                'low_stock' => ($nozzle->tank->current_stock / $nozzle->tank->capacity) < 0.2,
            ];
        })->toArray();
    }

    /**
     * Get keypad configuration for UI
     * Renders number pad + operation buttons
     */
    public function getKeypadConfig(): array
    {
        return [
            'numbers' => [
                ['value' => '1', 'label' => '1'],
                ['value' => '2', 'label' => '2'],
                ['value' => '3', 'label' => '3'],
                ['value' => '4', 'label' => '4'],
                ['value' => '5', 'label' => '5'],
                ['value' => '6', 'label' => '6'],
                ['value' => '7', 'label' => '7'],
                ['value' => '8', 'label' => '8'],
                ['value' => '9', 'label' => '9'],
                ['value' => '0', 'label' => '0'],
                ['value' => '.', 'label' => '.'],
                ['value' => '00', 'label' => '00'],
            ],
            'operations' => [
                ['value' => 'clear', 'label' => 'C', 'action' => 'clear', 'color' => '#e74c3c'],
                ['value' => 'backspace', 'label' => '⌫', 'action' => 'backspace', 'color' => '#e67e22'],
                ['value' => 'confirm', 'label' => '✓', 'action' => 'confirm', 'color' => '#27ae60'],
                ['value' => 'cancel', 'label' => '✗', 'action' => 'cancel', 'color' => '#c0392b'],
            ],
            'layout' => 'grid-4x3', // 4 columns, 3 rows for numbers + operations
        ];
    }

    /**
     * Get banknote denominations for quick cash entry
     */
    public function getBanknoteDenominations(): array
    {
        return [
            [
                'denomination' => 100,
                'count' => 0,
                'color' => '#3498db',
                'icon' => '💙',
            ],
            [
                'denomination' => 500,
                'count' => 0,
                'color' => '#e74c3c',
                'icon' => '❤️',
            ],
            [
                'denomination' => 1000,
                'count' => 0,
                'color' => '#2ecc71',
                'icon' => '💚',
            ],
            [
                'denomination' => 5000,
                'count' => 0,
                'color' => '#f39c12',
                'icon' => '🟨',
            ],
            [
                'denomination' => 10000,
                'count' => 0,
                'color' => '#9b59b6',
                'icon' => '💜',
            ],
        ];
    }

    /**
     * Calculate banknote total from denominations
     */
    public function calculateBanknoteTotal(array $denominations): float
    {
        $total = 0;
        foreach ($denominations as $denom) {
            if (isset($denom['denomination']) && isset($denom['count'])) {
                $total += $denom['denomination'] * $denom['count'];
            }
        }
        return round($total, 2);
    }

    /**
     * Get available payment methods for UI
     */
    public function getPaymentMethods(): array
    {
        return [
            [
                'method' => 'CASH',
                'label' => 'Cash',
                'icon' => '💵',
                'color' => '#27ae60',
                'order' => 1,
            ],
            [
                'method' => 'CARD',
                'label' => 'Debit/Credit Card',
                'icon' => '💳',
                'color' => '#3498db',
                'order' => 2,
            ],
            [
                'method' => 'BANK_TRANSFER',
                'label' => 'Bank Transfer',
                'icon' => '🏦',
                'color' => '#9b59b6',
                'order' => 3,
            ],
            [
                'method' => 'MOBILE_WALLET',
                'label' => 'Mobile Wallet',
                'icon' => '📱',
                'color' => '#1abc9c',
                'order' => 4,
            ],
            [
                'method' => 'CREDIT',
                'label' => 'Credit (Udhaar)',
                'icon' => '📋',
                'color' => '#f39c12',
                'order' => 5,
            ],
        ];
    }

    /**
     * Get signature capture config
     */
    public function getSignatureCaptureConfig(): array
    {
        return [
            'enabled' => setting('require_customer_signature', false),
            'required_for_credit' => true,
            'canvas_width' => 400,
            'canvas_height' => 150,
            'pen_color' => '#000000',
            'pen_width' => 2,
            'line_join' => 'round',
            'line_cap' => 'round',
        ];
    }

    /**
     * Get UI theme/colors
     */
    public function getThemeConfig(): array
    {
        return [
            'primary_color' => '#cc0000',    // Red
            'secondary_color' => '#ffffff',  // White
            'accent_color' => '#2ecc71',     // Green
            'danger_color' => '#e74c3c',     // Red (errors)
            'warning_color' => '#f39c12',    // Orange
            'info_color' => '#3498db',       // Blue
            'success_color' => '#27ae60',    // Dark Green
            'tile_bg' => '#ffffff',
            'tile_shadow' => '0 4px 6px rgba(0,0,0,0.1)',
            'border_radius' => '12px',
            'button_height' => '60px',       // Large touch targets
            'font_size_large' => '32px',
            'font_size_medium' => '18px',
            'font_size_small' => '14px',
        ];
    }

    /**
     * Clear cache for dashboard
     */
    public function invalidateDashboardCache(Shift $shift): void
    {
        $cacheKey = "cashier_dashboard_{$shift->id}_" . now()->format('YmdH');
        Cache::forget($cacheKey);
    }
}
