<?php

namespace App\Services\Supplier;

use App\Models\FuelPurchase;
use App\Models\SupplierShortageClaim;
use App\Models\Tank;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Tanker Unloading Service
 * Handles fuel purchases with dip before/after, evaporation, temperature adjustment
 * Calculates supplier shortage and creates claim
 */
class TankerUnloadingService
{
    public function __construct(private StockService $stockService) {}

    /**
     * Create tanker unloading record with shortage calculation
     */
    public function recordUnloading(
        Tank $tank,
        string $tankerNumber,
        string $supplierChallanNumber,
        float $challanLitres,
        float $dipBeforeLitres,
        float $dipAfterLitres,
        float $temperatureCelsius,
        float $ratePerLitre
    ): FuelPurchase
    {
        // Validate: dipAfter must be > dipBefore
        if ($dipAfterLitres <= $dipBeforeLitres) {
            throw new \Exception("Dip after must be greater than dip before");
        }

        $actualReceived = bcsub($dipAfterLitres, $dipBeforeLitres, 3);
        
        // Calculate evaporation/temperature loss
        $evaporationLoss = $this->calculateEvaporationLoss($actualReceived, $temperatureCelsius);
        
        // Calculate shortage
        $shortage = bcsub($challanLitres, bcsub($actualReceived, $evaporationLoss, 3), 3);
        
        $totalAmount = bcmul($challanLitres, $ratePerLitre, 2);
        $shortageAmount = bcmul($shortage, $ratePerLitre, 2);

        return DB::transaction(function () use (
            $tank, $tankerNumber, $supplierChallanNumber, $challanLitres, 
            $dipBeforeLitres, $dipAfterLitres, $actualReceived, $temperatureCelsius,
            $evaporationLoss, $shortage, $ratePerLitre, $totalAmount, $shortageAmount
        ) {
            // Create purchase record
            $purchase = FuelPurchase::create([
                'tank_id' => $tank->id,
                'tanker_id' => $tankerNumber, // Simplified for now
                'supplier_challan_number' => $supplierChallanNumber,
                'challan_litres' => $challanLitres,
                'dip_before_litres' => $dipBeforeLitres,
                'dip_after_litres' => $dipAfterLitres,
                'actual_received_litres' => $actualReceived,
                'temperature_celsius' => $temperatureCelsius,
                'evaporation_loss_litres' => $evaporationLoss,
                'shortage_litres' => $shortage,
                'rate_per_litre' => $ratePerLitre,
                'total_amount' => $totalAmount,
                'status' => 'VERIFIED',
            ]);

            // Add received quantity to tank stock
            $this->stockService->move(
                tank: $tank,
                quantity: $actualReceived,
                type: 'PURCHASE',
                referenceType: 'FUEL_PURCHASE',
                referenceId: $purchase->id,
                description: "Tanker {$tankerNumber}: {$actualReceived}L @ Rs.{$ratePerLitre}"
            );

            // Create shortage claim if shortage exists
            if ($shortage > 0) {
                SupplierShortageClaim::create([
                    'fuel_purchase_id' => $purchase->id,
                    'shortage_litres' => $shortage,
                    'shortage_amount' => $shortageAmount,
                    'claim_status' => 'PENDING',
                    'claimed_date' => now()->date(),
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Calculate evaporation loss (volatility + temperature)
     * Standard: ~0.3% per day + temperature adjustment
     */
    private function calculateEvaporationLoss(float $litres, float $temperatureCelsius): float
    {
        $baseEvaporationRate = 0.003; // 0.3% standard loss
        
        // Temperature adjustment: higher temp = more evaporation
        $tempAdjustment = 0;
        if ($temperatureCelsius > 25) {
            // Each degree above 25°C adds 0.1% loss
            $tempAdjustment = bcmul(bcsub($temperatureCelsius, 25, 2), 0.001, 4);
        }

        $totalRate = bcadd($baseEvaporationRate, $tempAdjustment, 4);
        return bcmul($litres, $totalRate, 3);
    }

    /**
     * Get shortage claim details
     */
    public function getShortageClaimStatus(FuelPurchase $purchase): array
    {
        $claim = $purchase->shortageClaim;

        return [
            'purchase_id' => $purchase->id,
            'challan_number' => $purchase->supplier_challan_number,
            'expected_litres' => $purchase->challan_litres,
            'received_litres' => $purchase->actual_received_litres,
            'shortage_litres' => $purchase->shortage_litres,
            'shortage_amount' => $purchase->shortage_litres * $purchase->rate_per_litre,
            'claim_status' => $claim?->claim_status,
            'claim_date' => $claim?->claimed_date,
        ];
    }

    /**
     * Mark shortage claim as settled
     */
    public function settleShortageC laim(SupplierShortageClaim $claim): void
    {
        $claim->update([
            'claim_status' => 'SETTLED',
            'settled_date' => now()->date(),
        ]);
    }
}
