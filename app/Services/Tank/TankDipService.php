<?php

namespace App\Services\Tank;

use App\Models\Tank;
use App\Models\TankDipChart;
use App\Models\TankDipReading;
use App\Models\WaterTestLog;
use Illuminate\Support\Facades\DB;

/**
 * Tank Dip Service
 * Handles dip readings, temperature adjustments, water contamination detection
 */
class TankDipService
{
    /**
     * Create or update dip chart for a tank (calibration)
     */
    public function calibrateTankDipChart(Tank $tank, array $readings): void
    {
        DB::transaction(function () use ($tank, $readings) {
            foreach ($readings as $reading) {
                TankDipChart::updateOrCreate(
                    ['tank_id' => $tank->id, 'centimeters' => $reading['centimeters']],
                    ['litres' => $reading['litres'], 'notes' => $reading['notes'] ?? null]
                );
            }
        });
    }

    /**
     * Record dip reading with temperature adjustment
     */
    public function recordDipReading(Tank $tank, int $dipCentimeters, float $temperatureCelsius, ?string $notes = null)
    {
        $dip = TankDipChart::where('tank_id', $tank->id)
            ->where('centimeters', $dipCentimeters)
            ->first();

        if (!$dip) {
            throw new \Exception("Dip chart not calibrated for {$dipCentimeters}cm");
        }

        $calculatedLitres = $dip->litres;
        $temperatureAdjustment = $this->calculateTemperatureAdjustment($calculatedLitres, $temperatureCelsius);
        $finalStock = bcadd($calculatedLitres, $temperatureAdjustment, 3);

        return DB::transaction(function () use ($tank, $dipCentimeters, $calculatedLitres, $temperatureAdjustment, $finalStock, $temperatureCelsius, $notes) {
            return TankDipReading::create([
                'tank_id' => $tank->id,
                'user_id' => auth()->id(),
                'reading_date' => now()->date(),
                'dip_centimeters' => $dipCentimeters,
                'calculated_litres' => $calculatedLitres,
                'temperature_celsius' => $temperatureCelsius,
                'temperature_adjustment_litres' => $temperatureAdjustment,
                'final_stock_litres' => $finalStock,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Calculate temperature adjustment (volume expands/contracts with temp)
     * Using standard fuel thermal expansion coefficient: 0.0008/°C
     */
    private function calculateTemperatureAdjustment(float $litres, float $temperatureCelsius): float
    {
        $referenceTemp = 15.0; // Standard reference temperature
        $thermalCoefficient = 0.0008; // Per degree Celsius
        $tempDifference = $temperatureCelsius - $referenceTemp;
        
        return bcmul($litres, bcmul($tempDifference, $thermalCoefficient, 6), 3);
    }

    /**
     * Record water contamination test
     */
    public function recordWaterTest(Tank $tank, string $waterStatus, ?float $waterPercentage = null, ?string $actionTaken = null): WaterTestLog
    {
        return DB::transaction(function () use ($tank, $waterStatus, $waterPercentage, $actionTaken) {
            return WaterTestLog::create([
                'tank_id' => $tank->id,
                'user_id' => auth()->id(),
                'test_date' => now()->date(),
                'water_status' => $waterStatus,
                'water_percentage' => $waterPercentage,
                'action_taken' => $actionTaken,
            ]);
        });
    }

    /**
     * Calculate expected stock based on dip chart
     */
    public function calculateExpectedStock(Tank $tank, int $latestDipCm, float $temperature): float
    {
        $dip = TankDipChart::where('tank_id', $tank->id)
            ->where('centimeters', $latestDipCm)
            ->first();

        if (!$dip) {
            return 0;
        }

        $baseStock = $dip->litres;
        $tempAdjustment = $this->calculateTemperatureAdjustment($baseStock, $temperature);
        
        return bcadd($baseStock, $tempAdjustment, 3);
    }

    /**
     * Calculate stock variance (expected vs physical)
     */
    public function calculateStockVariance(Tank $tank, float $expectedStock, float $physicalStock): float
    {
        return bcsub($physicalStock, $expectedStock, 3);
    }
}
