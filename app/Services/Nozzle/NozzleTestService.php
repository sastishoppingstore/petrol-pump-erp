<?php

namespace App\Services\Nozzle;

use App\Models\Nozzle;
use App\Models\NozzleTestReturn;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Nozzle Test Service
 * Handles nozzle calibration tests and return litres
 * Return litres are deducted from physical tank stock
 */
class NozzleTestService
{
    public function __construct(private StockService $stockService) {}

    /**
     * Record nozzle test/return litres
     * Meter must be strictly increasing
     */
    public function recordNozzleTest(Nozzle $nozzle, float $testLitres, string $reason, ?int $shiftId = null)
    {
        if ($testLitres < 0) {
            throw new \Exception("Test litres cannot be negative");
        }

        $meterBefore = $nozzle->current_meter;
        $meterAfter = bcadd($meterBefore, $testLitres, 3);

        // Validate monotonic increase
        if ($meterAfter <= $meterBefore) {
            throw new \Exception("Meter must strictly increase. Before: {$meterBefore}, After: {$meterAfter}");
        }

        // Handle rollover (e.g., 99999 -> 00001)
        if ($meterAfter > 999999) {
            // Meter rolled over - this is normal for mechanical meters
            $meterAfter = bcmod($meterAfter, 999999);
        }

        return DB::transaction(function () use ($nozzle, $testLitres, $meterBefore, $meterAfter, $reason, $shiftId) {
            // Record the test
            $test = NozzleTestReturn::create([
                'nozzle_id' => $nozzle->id,
                'shift_id' => $shiftId ?? 0, // Optional shift reference
                'test_litres' => $testLitres,
                'meter_before' => $meterBefore,
                'meter_after' => $meterAfter,
                'reason' => $reason,
            ]);

            // Update nozzle meter
            $nozzle->update(['current_meter' => $meterAfter]);

            // Deduct test litres from tank stock as return
            $this->stockService->move(
                tank: $nozzle->tank,
                quantity: bcsub(0, $testLitres, 3), // Negative = stock decrease
                type: 'CORRECTION',
                referenceType: 'NOZZLE_TEST',
                referenceId: $test->id,
                description: "Nozzle {$nozzle->nozzle_number} calibration test return"
            );

            return $test;
        });
    }

    /**
     * Reverse a nozzle test (e.g., if recorded by mistake)
     */
    public function reverseNozzleTest(NozzleTestReturn $test)
    {
        if ($test->status !== 'RECORDED') {
            throw new \Exception("Only RECORDED tests can be reversed");
        }

        return DB::transaction(function () use ($test) {
            $nozzle = $test->nozzle;

            // Reverse meter to before value
            $nozzle->update(['current_meter' => $test->meter_before]);

            // Reverse stock deduction (add back the litres)
            $this->stockService->move(
                tank: $nozzle->tank,
                quantity: $test->test_litres, // Positive = stock increase
                type: 'CORRECTION',
                referenceType: 'NOZZLE_TEST_REVERSAL',
                referenceId: $test->id,
                description: "Reversal: Nozzle {$nozzle->nozzle_number} test"
            );

            $test->update(['status' => 'REVERSED']);

            return $test;
        });
    }

    /**
     * Get calibration status of nozzle
     */
    public function getCalibrationStatus(Nozzle $nozzle): array
    {
        $recentTests = NozzleTestReturn::where('nozzle_id', $nozzle->id)
            ->where('status', 'RECORDED')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalTestLitres = $recentTests->sum('test_litres');
        $lastTestDate = $recentTests->first()?->created_at;

        return [
            'nozzle_id' => $nozzle->id,
            'current_meter' => $nozzle->current_meter,
            'last_test_date' => $lastTestDate,
            'recent_test_count' => $recentTests->count(),
            'total_test_litres_30days' => $totalTestLitres,
            'calibration_required' => $recentTests->count() == 0 || $totalTestLitres > 100, // Arbitrary threshold
        ];
    }
}
