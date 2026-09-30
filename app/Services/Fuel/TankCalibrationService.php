<?php

namespace App\Services\Fuel;

use App\Models\Tank;
use App\Models\TankDipChart;
use App\Models\TankReading;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TankCalibrationService
{
    /**
     * Permissible transit/storage evaporation loss standard (0.5% = 0.005).
     */
    public const PERMISSIBLE_EVAPORATION_RATE = '0.005';

    /**
     * Add or update a calibration chart point (cm -> litres).
     */
    public function setCalibrationPoint(Tank $tank, string|float $dipCm, string|float $litres): TankDipChart
    {
        $cm = bcadd((string) $dipCm, '0', 2);
        $ltrs = Quantity::round((string) $litres);

        if (bccomp($cm, '0', 2) < 0) {
            throw ValidationException::withMessages([
                'dip_cm' => 'Dip height in cm cannot be negative.',
            ]);
        }

        if (Quantity::compare($ltrs, '0.000') < 0) {
            throw ValidationException::withMessages([
                'litres' => 'Litres cannot be negative.',
            ]);
        }

        return TankDipChart::updateOrCreate(
            ['tank_id' => $tank->id, 'dip_cm' => $cm],
            ['litres' => $ltrs]
        );
    }

    /**
     * Bulk load calibration points for a tank.
     */
    public function bulkLoadCalibration(Tank $tank, array $points): int
    {
        return DB::transaction(function () use ($tank, $points) {
            $count = 0;
            foreach ($points as $point) {
                if (isset($point['dip_cm']) && isset($point['litres'])) {
                    $this->setCalibrationPoint($tank, $point['dip_cm'], $point['litres']);
                    $count++;
                }
            }
            return $count;
        });
    }

    /**
     * Convert dip in centimeters to litres using the tank's calibration curve.
     * Uses linear interpolation between calibration points.
     */
    public function calculateLitresFromDip(Tank $tank, string|float $dipCm): string
    {
        $cm = bcadd((string) $dipCm, '0', 2);

        if (bccomp($cm, '0.00', 2) <= 0) {
            return '0.000';
        }

        $points = TankDipChart::query()
            ->where('tank_id', $tank->id)
            ->orderBy('dip_cm')
            ->get();

        if ($points->isEmpty()) {
            // No calibration chart available: return 0.000
            return '0.000';
        }

        // Check if exact match
        $exact = $points->firstWhere('dip_cm', $cm);
        if ($exact) {
            return Quantity::round($exact->litres);
        }

        $firstPoint = $points->first();
        $lastPoint = $points->last();

        // Dip is below the lowest recorded calibration point
        if (bccomp($cm, (string) $firstPoint->dip_cm, 2) < 0) {
            // Linear interpolation between (0, 0) and firstPoint
            if (bccomp((string) $firstPoint->dip_cm, '0.00', 2) === 0) {
                return Quantity::round($firstPoint->litres);
            }
            $slope = bcdiv((string) $firstPoint->litres, (string) $firstPoint->dip_cm, 6);
            return Quantity::round(bcmul($cm, $slope, 3));
        }

        // Dip is above the highest recorded calibration point
        if (bccomp($cm, (string) $lastPoint->dip_cm, 2) >= 0) {
            // Clamp to max of lastPoint litres and tank capacity
            $maxLitres = Quantity::compare(Quantity::n($lastPoint->litres), Quantity::n($tank->capacity)) > 0
                ? Quantity::n($tank->capacity)
                : Quantity::n($lastPoint->litres);
            return Quantity::round($maxLitres);
        }

        // Linear interpolation between two adjacent points (d1, L1) and (d2, L2)
        $lowerPoint = null;
        $upperPoint = null;

        foreach ($points as $point) {
            if (bccomp((string) $point->dip_cm, $cm, 2) <= 0) {
                $lowerPoint = $point;
            } elseif (bccomp((string) $point->dip_cm, $cm, 2) > 0 && $upperPoint === null) {
                $upperPoint = $point;
                break;
            }
        }

        if (! $lowerPoint || ! $upperPoint) {
            return Quantity::round($lastPoint->litres);
        }

        $d1 = (string) $lowerPoint->dip_cm;
        $d2 = (string) $upperPoint->dip_cm;
        $l1 = (string) $lowerPoint->litres;
        $l2 = (string) $upperPoint->litres;

        $deltaD = bcsub($d2, $d1, 4);
        $deltaL = bcsub($l2, $l1, 4);

        if (bccomp($deltaD, '0.00', 2) === 0) {
            return Quantity::round($l1);
        }

        // L = L1 + (cm - d1) * (deltaL / deltaD)
        $rate = bcdiv($deltaL, $deltaD, 6);
        $offset = bcsub($cm, $d1, 4);
        $interpolated = bcadd($l1, bcmul($offset, $rate, 4), 3);

        return Quantity::round($interpolated);
    }

    /**
     * Evaluate physical dip reading against expected stock and permissible evaporation allowance (0.5%).
     */
    public function evaluatePhysicalReading(
        Tank $tank,
        ?string $dipCm = null,
        ?string $physicalQuantity = null,
        ?string $notes = null,
        ?int $userId = null,
        ?string $readingDate = null,
    ): TankReading {
        // If physical quantity not explicitly provided, calculate from dip cm
        if ($physicalQuantity === null || $physicalQuantity === '') {
            if ($dipCm === null || $dipCm === '') {
                throw ValidationException::withMessages([
                    'dip_cm' => 'Please provide either dip reading in cm or physical quantity in litres.',
                ]);
            }
            $physicalQuantity = $this->calculateLitresFromDip($tank, $dipCm);
        } else {
            $physicalQuantity = Quantity::round($physicalQuantity);
        }

        if (Quantity::isNegative($physicalQuantity)) {
            throw ValidationException::withMessages([
                'physical_quantity' => 'Physical quantity cannot be negative.',
            ]);
        }

        if (Quantity::compare($physicalQuantity, Quantity::n($tank->capacity)) > 0) {
            throw ValidationException::withMessages([
                'physical_quantity' => sprintf(
                    'Physical quantity (%s L) cannot exceed tank capacity (%s L).',
                    Quantity::format($physicalQuantity),
                    Quantity::format($tank->capacity)
                ),
            ]);
        }

        $expected = Quantity::n($tank->current_stock);
        $variance = Quantity::subtract($physicalQuantity, $expected);

        // Permissible evaporation allowance: 0.5% (0.005) of expected quantity
        $allowableLoss = Quantity::round(bcmul($expected, self::PERMISSIBLE_EVAPORATION_RATE, 4));

        $isWithinTolerance = true;
        $varianceType = TankReading::VARIANCE_MATCH;

        if (Quantity::isZero($variance)) {
            $varianceType = TankReading::VARIANCE_MATCH;
            $isWithinTolerance = true;
        } elseif (Quantity::compare($variance, '0.000') > 0) {
            $varianceType = TankReading::VARIANCE_SURPLUS;
            $isWithinTolerance = true;
        } else {
            // Negative variance = shortage
            $lossAmount = Quantity::abs($variance);
            if (Quantity::compare($lossAmount, $allowableLoss) <= 0) {
                $isWithinTolerance = true;
                $varianceType = 'NORMAL_EVAPORATION';
            } else {
                $isWithinTolerance = false;
                $varianceType = TankReading::VARIANCE_SHORTAGE;
            }
        }

        return TankReading::create([
            'branch_id' => $tank->branch_id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $tank->fuel_product_id,
            'reading_date' => $readingDate ?? today()->toDateString(),
            'physical_quantity' => $physicalQuantity,
            'expected_quantity' => $expected,
            'variance_quantity' => $variance,
            'variance_type' => $varianceType,
            'dip_cm' => $dipCm ? bcadd((string) $dipCm, '0', 2) : null,
            'allowable_loss_litres' => $allowableLoss,
            'is_within_tolerance' => $isWithinTolerance,
            'notes' => $notes,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }
}
