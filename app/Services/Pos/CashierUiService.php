<?php

namespace App\Services\Pos;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Nozzle;
use App\Models\Tank;
use App\Models\FuelProduct;
use App\Models\Shift;
use App\Services\System\SettingService;
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
                    'opened_at' => $shift->opened_at?->format('H:i') ?? '—',
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

        $breakdown = [];
        foreach (array_keys(SalePayment::methods()) as $knownMethod) {
            $breakdown[$knownMethod] = 0;
        }
        $breakdown['OTHER'] = 0;

        foreach ($sales as $sale) {
            foreach ($sale->payments as $payment) {
                // The column on sale_payments is `method` (see SalePayment model).
                $method = $payment->method ?: 'OTHER';
                if (! isset($breakdown[$method])) {
                    $breakdown[$method] = 0;
                }
                $breakdown[$method] += (float) $payment->amount;
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
        // $shift->nozzles is the ShiftNozzle assignment list — the nozzle ids
        // live in its `nozzle_id` column, NOT in the assignment row's own id.
        $nozzles = Nozzle::whereIn('id', $shift->nozzles->pluck('nozzle_id'))
            ->with('tank', 'fuelProduct', 'dispenser')
            ->get();

        return $nozzles->map(function ($nozzle) {
            $stock = $nozzle->tank?->current_stock;
            $capacity = $nozzle->tank?->capacity;
            $stockPercent = ($stock !== null && $capacity !== null && (float) $capacity > 0)
                ? round(((float) $stock / (float) $capacity) * 100, 1)
                : 0;

            return [
                'id' => $nozzle->id,
                'nozzle_number' => $nozzle->nozzle_number,
                'dispenser_number' => $nozzle->dispenser?->dispenser_number,
                'fuel' => $nozzle->fuelProduct->name ?? 'Unknown',
                'fuel_code' => $nozzle->fuelProduct->code ?? '',
                'fuel_product_id' => $nozzle->fuel_product_id,
                'rate' => $nozzle->fuelProduct?->currentPrice($nozzle->branch_id) ?? '0.00',
                'status' => $nozzle->status,
                'current_meter' => $nozzle->current_meter,
                'tank_stock' => $stock ?? 0,
                'tank_capacity' => $capacity ?? 0,
                'stock_percent' => $stockPercent,
                'low_stock' => ($stock !== null && (float) $stock <= 0)
                    || ($stockPercent > 0 && $stockPercent < 20),
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
     * Get available payment methods for UI.
     *
     * These MUST stay in sync with SalePayment::methods() — the codes are
     * stored in sale_payments.method and grouped by on receipts/reports.
     * (Previously this returned BANK_TRANSFER / MOBILE_WALLET, which do not
     * exist anywhere else in the system.)
     */
    public function getPaymentMethods(): array
    {
        return [
            [
                'method' => SalePayment::METHOD_CASH,
                'label' => 'Cash (نقد)',
                'icon' => '💵',
                'color' => '#27ae60',
                'order' => 1,
            ],
            [
                'method' => SalePayment::METHOD_CARD,
                'label' => 'Card / POS Machine (کارڈ)',
                'icon' => '💳',
                'color' => '#3498db',
                'order' => 2,
            ],
            [
                'method' => SalePayment::METHOD_JAZZCASH,
                'label' => 'JazzCash (جاز کیش)',
                'icon' => '📱',
                'color' => '#1abc9c',
                'order' => 3,
            ],
            [
                'method' => SalePayment::METHOD_EASYPAISA,
                'label' => 'Easypaisa (ایزی پیسہ)',
                'icon' => '🟢',
                'color' => '#16a34a',
                'order' => 4,
            ],
            [
                'method' => SalePayment::METHOD_RAAST,
                'label' => 'Raast (راست)',
                'icon' => '⚡',
                'color' => '#0d9488',
                'order' => 5,
            ],
            [
                'method' => SalePayment::METHOD_BANK,
                'label' => 'Bank Transfer (بینک ٹرانسفر)',
                'icon' => '🏦',
                'color' => '#9b59b6',
                'order' => 6,
            ],
            [
                'method' => SalePayment::METHOD_WALLET,
                'label' => 'Mobile Wallet (والیٹ)',
                'icon' => '👛',
                'color' => '#0ea5e9',
                'order' => 7,
            ],
            [
                'method' => SalePayment::METHOD_VITAL_CARD,
                'label' => 'Vital OMC Fuel Card (وائٹل کارڈ)',
                'icon' => '⛽',
                'color' => '#f59e0b',
                'order' => 8,
            ],
            [
                'method' => SalePayment::METHOD_FLEET_CARD,
                'label' => 'Fleet Card — OMC/PSO (فلیٹ کارڈ)',
                'icon' => '🚛',
                'color' => '#d97706',
                'order' => 9,
            ],
            [
                'method' => SalePayment::METHOD_CHEQUE,
                'label' => 'Cheque (چیک)',
                'icon' => '📑',
                'color' => '#64748b',
                'order' => 10,
            ],
            [
                'method' => SalePayment::METHOD_CREDIT,
                'label' => 'Credit / Udhaar (ادھار کھاتہ)',
                'icon' => '📋',
                'color' => '#f39c12',
                'order' => 11,
            ],
        ];
    }

    /**
     * Get signature capture config
     */
    public function getSignatureCaptureConfig(): array
    {
        // NOTE: there is no global setting() helper in this codebase —
        // settings are read through App\Services\System\SettingService.
        // (The old setting() call here was a fatal "undefined function"
        // error that took the whole cashier screen down with a 500.)
        $enabled = filter_var(
            app(SettingService::class)->get('require_customer_signature', '0'),
            FILTER_VALIDATE_BOOLEAN
        );

        return [
            'enabled' => $enabled,
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
