<?php

namespace App\Services\Visualization;

use App\Models\Tank;
use Illuminate\Support\Collection;

class Tank3dGaugeService
{
    /**
     * Generate 3D gauge data for a tank with wave animation
     */
    public function generateGaugeData(Tank $tank): array
    {
        $stockPercent = $this->calculateStockPercent($tank);
        $color = $this->getColorByStockLevel($stockPercent);

        return [
            'tank_id' => $tank->id,
            'tank_name' => $tank->name,
            'fuel' => $tank->fuelProduct->name ?? 'Unknown',
            'fuel_code' => $tank->fuelProduct->code ?? '',
            
            // Stock information
            'current_stock' => round($tank->current_stock, 3),
            'capacity' => round($tank->capacity, 3),
            'stock_percent' => round($stockPercent, 2),
            
            // 3D rendering
            '3d' => [
                'canvas_width' => 400,
                'canvas_height' => 500,
                'container_bg' => '#f5f5f5',
                'border_color' => '#333333',
                'border_width' => 3,
            ],

            // Wave animation
            'wave' => [
                'enabled' => true,
                'amplitude' => 10 + ($stockPercent * 0.5), // Dynamic amplitude based on level
                'frequency' => 0.015,
                'speed' => 0.02,
                'number_of_waves' => 3,
                'phase' => 0,
            ],

            // Gradient colors
            'gradient' => $this->generateGradient($color, $stockPercent),

            // Fill information
            'fill' => [
                'color' => $color['primary'],
                'opacity' => 0.8,
                'wave_color' => $color['wave'],
                'shadow' => true,
                'glow' => $stockPercent < 20 ? true : false, // Glow if low
            ],

            // Status indicators
            'status' => [
                'level_label' => $this->getLevelLabel($stockPercent),
                'level_status' => $this->getLevelStatus($stockPercent),
                'warning' => $this->getWarningStatus($tank),
                'critical' => $stockPercent < 10,
                'full' => $stockPercent >= 95,
            ],

            // Gauge markings (calibration lines)
            'markings' => $this->generateMarkings($tank->capacity),

            // Animation config
            'animation' => [
                'duration_ms' => 2000,
                'easing' => 'ease-in-out',
                'loop' => true,
                'auto_play' => true,
            ],

            // Accessibility
            'aria' => [
                'label' => "{$tank->name}: {$stockPercent}% full ({$tank->current_stock}L of {$tank->capacity}L)",
                'role' => 'img',
                'live' => 'polite',
            ],
        ];
    }

    /**
     * Calculate stock percentage
     */
    private function calculateStockPercent(Tank $tank): float
    {
        if ($tank->capacity <= 0) {
            return 0;
        }

        $percent = ($tank->current_stock / $tank->capacity) * 100;
        return min(100, max(0, $percent)); // Clamp 0-100
    }

    /**
     * Get color based on stock level
     * Green (good) -> Yellow (warning) -> Red (critical)
     */
    private function getColorByStockLevel(float $percent): array
    {
        if ($percent >= 50) {
            // Green zone (50-100%)
            return [
                'primary' => '#27ae60',      // Dark green
                'wave' => '#2ecc71',         // Light green
                'background' => '#d5f4e6',   // Very light green
                'accent' => '#16a085',       // Darker green
            ];
        } elseif ($percent >= 20) {
            // Yellow zone (20-50%)
            return [
                'primary' => '#f39c12',      // Orange
                'wave' => '#f1c40f',         // Yellow
                'background' => '#fef5e7',   // Very light yellow
                'accent' => '#d68910',       // Darker orange
            ];
        } else {
            // Red zone (0-20%)
            return [
                'primary' => '#e74c3c',      // Red
                'wave' => '#ec7063',         // Light red
                'background' => '#fadbd8',   // Very light red
                'accent' => '#cb4335',       // Darker red
            ];
        }
    }

    /**
     * Generate gradient stops for canvas rendering
     */
    private function generateGradient(array $colors, float $percent): array
    {
        $fillLevel = $percent / 100;

        return [
            'type' => 'linear',
            'direction' => 'to top',
            'stops' => [
                [
                    'offset' => 0,
                    'color' => $colors['primary'],
                    'opacity' => 0.9,
                ],
                [
                    'offset' => $fillLevel,
                    'color' => $colors['wave'],
                    'opacity' => 0.7,
                ],
                [
                    'offset' => $fillLevel + 0.05,
                    'color' => $colors['background'],
                    'opacity' => 0.3,
                ],
                [
                    'offset' => 1,
                    'color' => '#ffffff',
                    'opacity' => 0.1,
                ],
            ],
        ];
    }

    /**
     * Get human-readable level label
     */
    private function getLevelLabel(float $percent): string
    {
        if ($percent >= 90) {
            return 'Full';
        } elseif ($percent >= 70) {
            return 'High';
        } elseif ($percent >= 50) {
            return 'Good';
        } elseif ($percent >= 30) {
            return 'Medium';
        } elseif ($percent >= 10) {
            return 'Low';
        } else {
            return 'Critical';
        }
    }

    /**
     * Get level status for UI display
     */
    private function getLevelStatus(float $percent): string
    {
        if ($percent >= 50) {
            return 'optimal';
        } elseif ($percent >= 20) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Get warning status for the tank
     */
    private function getWarningStatus(Tank $tank): ?string
    {
        $percent = $this->calculateStockPercent($tank);

        if ($percent < 10) {
            return 'critical_low_stock';
        } elseif ($percent < 20) {
            return 'low_stock';
        } elseif ($tank->current_stock < ($tank->minimum_level ?? 500)) {
            return 'below_minimum';
        }

        return null;
    }

    /**
     * Generate gauge markings (calibration lines)
     */
    private function generateMarkings(float $capacity): array
    {
        $markings = [];
        $intervals = [25, 50, 75, 100]; // 25%, 50%, 75%, 100%

        foreach ($intervals as $percent) {
            $litres = ($capacity * $percent) / 100;
            $markings[] = [
                'percent' => $percent,
                'litres' => round($litres, 1),
                'label' => "{$percent}%",
                'position' => ($percent / 100),
                'size' => $percent % 50 == 0 ? 'large' : 'small',
                'color' => '#666666',
            ];
        }

        return $markings;
    }

    /**
     * Generate gauges for multiple tanks (dashboard view)
     */
    public function generateMultipleGauges(Collection $tanks): array
    {
        return $tanks->map(function ($tank) {
            return $this->generateGaugeData($tank);
        })->toArray();
    }

    /**
     * Get animation keyframes for wave effect
     */
    public function getWaveKeyframes(int $durationMs = 2000): array
    {
        $steps = 60; // Number of keyframes
        $keyframes = [];

        for ($i = 0; $i < $steps; $i++) {
            $percent = ($i / $steps) * 100;
            $phase = (($i / $steps) * 2 * M_PI);

            $keyframes[] = [
                'percent' => round($percent, 2),
                'phase' => $phase,
                'offset_x' => sin($phase) * 20,
                'offset_y' => cos($phase) * 5,
            ];
        }

        return $keyframes;
    }

    /**
     * Get CSS animation for wave
     */
    public function getWaveAnimationCss(string $animationName = 'wave-animation'): string
    {
        return <<<CSS
        @keyframes {$animationName} {
            0% {
                transform: translateX(0) translateY(0);
                opacity: 1;
            }
            25% {
                transform: translateX(10px) translateY(5px);
                opacity: 0.8;
            }
            50% {
                transform: translateX(0) translateY(10px);
                opacity: 0.6;
            }
            75% {
                transform: translateX(-10px) translateY(5px);
                opacity: 0.8;
            }
            100% {
                transform: translateX(0) translateY(0);
                opacity: 1;
            }
        }

        .tank-gauge-wave {
            animation: {$animationName} 2s ease-in-out infinite;
        }
        CSS;
    }

    /**
     * Get chart.js compatible data for tank comparison
     */
    public function getChartJsData(Collection $tanks): array
    {
        $labels = $tanks->pluck('name')->toArray();
        $data = $tanks->map(function ($tank) {
            return round($this->calculateStockPercent($tank), 2);
        })->toArray();
        $colors = array_map(function ($percent) {
            $color = $this->getColorByStockLevel($percent);
            return $color['primary'];
        }, $data);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Tank Stock Levels (%)',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#333333',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
        ];
    }

    /**
     * Get SVG path for gauge arc
     */
    public function generateGaugeSvgArc(float $percent): string
    {
        $startAngle = -Math_PI / 2; // Top
        $endAngle = $startAngle + (($percent / 100) * 2 * M_PI);

        $radius = 150;
        $centerX = 200;
        $centerY = 200;

        $startX = $centerX + $radius * cos($startAngle);
        $startY = $centerY + $radius * sin($startAngle);
        $endX = $centerX + $radius * cos($endAngle);
        $endY = $centerY + $radius * sin($endAngle);

        $largeArc = $percent > 50 ? 1 : 0;

        return "M {$startX} {$startY} A {$radius} {$radius} 0 {$largeArc} 1 {$endX} {$endY}";
    }

    /**
     * Get real-time update interval (ms)
     */
    public function getUpdateInterval(): int
    {
        return setting('gauge_update_interval_ms', 5000); // Default 5 seconds
    }

    /**
     * Generate tooltip data for gauge
     */
    public function generateTooltip(Tank $tank): string
    {
        $percent = $this->calculateStockPercent($tank);
        $status = $this->getLevelStatus($percent);
        $statusEmoji = $status === 'optimal' ? '✓' : ($status === 'warning' ? '⚠' : '🔴');

        return "{$tank->name}: {$tank->current_stock}L / {$tank->capacity}L ({$percent}%) {$statusEmoji}";
    }
}
