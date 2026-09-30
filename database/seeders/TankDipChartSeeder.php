<?php

namespace Database\Seeders;

use App\Models\Tank;
use App\Models\TankDipChart;
use App\Support\Quantity;
use Illuminate\Database\Seeder;

class TankDipChartSeeder extends Seeder
{
    public function run(): void
    {
        $tanks = Tank::all();

        foreach ($tanks as $tank) {
            $capacity = (float) $tank->capacity;
            if ($capacity <= 0) {
                continue;
            }

            // Standard cylindrical horizontal underground storage tank calibration curve
            // Height: 250 cm max dip
            // Step: every 25 cm up to 250 cm
            $maxCm = 250.0;

            for ($cm = 0; $cm <= $maxCm; $cm += 25) {
                // Cylindrical approximation:
                // volume ratio is non-linear S-curve, but let's approximate or use realistic curve:
                // h/H from 0 to 1
                $h = $cm / $maxCm;
                // Simplified horizontal cylinder segment area formula:
                // V/V_total = (acos(1 - 2h) - (1 - 2h)*sqrt(4h*(1-h))) / PI
                if ($h <= 0.0) {
                    $ratio = 0.0;
                } elseif ($h >= 1.0) {
                    $ratio = 1.0;
                } else {
                    $term1 = acos(1.0 - 2.0 * $h);
                    $term2 = (1.0 - 2.0 * $h) * sqrt(4.0 * $h * (1.0 - $h));
                    $ratio = ($term1 - $term2) / M_PI;
                }

                $litres = Quantity::round((string) ($capacity * $ratio));

                TankDipChart::updateOrCreate(
                    [
                        'tank_id' => $tank->id,
                        'dip_cm' => number_format($cm, 2, '.', ''),
                    ],
                    [
                        'litres' => $litres,
                    ]
                );
            }
        }
    }
}
