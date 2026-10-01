<?php

namespace App\Services\Fuel;

use App\Models\InternalFuelConsumption;
use App\Models\Tank;
use App\Models\FuelProduct;
use App\Models\TankMovement;
use App\Services\System\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class GeneratorFuelService
{
    /**
     * Record internal fuel consumption (generator, testing, station use)
     * Deducts from tank stock with movement tracking
     */
    public function recordConsumption(
        Tank $tank,
        string $consumptionType, // GENERATOR, STATION_VEHICLE, TESTING, CLEANING
        float $litresConsumed,
        \DateTime $consumptionDate,
        string $description,
        int $branchId
    ): InternalFuelConsumption {
        return DB::transaction(function () use (
            $tank,
            $consumptionType,
            $litresConsumed,
            $consumptionDate,
            $description,
            $branchId
        ) {
            // Validate tank has enough stock
            if ($tank->current_stock < $litresConsumed) {
                throw new \Exception(
                    "Insufficient stock in {$tank->name}. Available: {$tank->current_stock}L, Required: {$litresConsumed}L"
                );
            }

            // Record consumption
            $consumption = InternalFuelConsumption::create([
                'tank_id' => $tank->id,
                'consumption_type' => $consumptionType,
                'litres_consumed' => round($litresConsumed, 3),
                'consumption_date' => $consumptionDate,
                'description' => $description,
            ]);

            // Create stock movement (deduction)
            $movement = TankMovement::create([
                'tank_id' => $tank->id,
                'fuel_product_id' => $tank->fuel_product_id,
                'branch_id' => $branchId,
                'quantity' => round($litresConsumed, 3),
                'before_quantity' => round($tank->current_stock, 3),
                'after_quantity' => round($tank->current_stock - $litresConsumed, 3),
                'type' => 'LOSS', // Treat internal consumption as loss
                'reference_type' => 'internal_fuel_consumption',
                'reference_id' => $consumption->id,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update tank stock
            $tank->update([
                'current_stock' => DB::raw("current_stock - {$litresConsumed}"),
            ]);

            Log::info("Fuel consumption recorded", [
                'tank' => $tank->name,
                'type' => $consumptionType,
                'litres' => $litresConsumed,
                'consumption_id' => $consumption->id,
            ]);

            // Post journal entry for COGS/expense
            $this->postJournalEntry($consumption, $tank, $litresConsumed, $branchId);

            return $consumption;
        });
    }

    /**
     * Post journal entry for internal consumption
     * Debit: Fuel Expense / Generator Expense
     * Credit: Fuel Inventory
     */
    private function postJournalEntry(
        InternalFuelConsumption $consumption,
        Tank $tank,
        float $litresConsumed,
        int $branchId
    ): void {
        try {
            // Get account codes based on consumption type
            $expenseAccountCode = match ($consumption->consumption_type) {
                'GENERATOR' => '5201',     // Generator Fuel Expense
                'STATION_VEHICLE' => '5202', // Vehicle Fuel Expense
                'TESTING' => '5203',       // Testing/Quality Fuel Expense
                'CLEANING' => '5204',      // Cleaning Fuel Expense
                default => '5299',         // Other Fuel Expense
            };

            $inventoryAccountCode = '1201'; // Fuel Inventory

            // Calculate amount at current fuel cost rate
            // Get weighted average cost for this fuel
            $avgCost = $this->getAverageCostPerLitre($tank->fuel_product_id, $branchId);
            $expenseAmount = round($litresConsumed * $avgCost, 2);

            // Create journal entry
            $journalEntry = \App\Models\JournalEntry::create([
                'branch_id' => $branchId,
                'entry_number' => $this->generateEntryNumber($branchId),
                'entry_date' => now()->toDateString(),
                'entry_type' => 'ADJUSTMENT',
                'description' => "{$consumption->consumption_type}: {$consumption->description}",
                'total_debit' => $expenseAmount,
                'total_credit' => $expenseAmount,
                'reference_type' => 'internal_fuel_consumption',
                'reference_id' => $consumption->id,
                'posted_by' => auth()->id(),
                'status' => 'POSTED',
            ]);

            // Create journal lines
            // Debit: Expense account
            \App\Models\JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_code' => $expenseAccountCode,
                'account_name' => "Fuel - {$consumption->consumption_type}",
                'debit_amount' => $expenseAmount,
                'credit_amount' => 0,
                'description' => $consumption->description,
            ]);

            // Credit: Inventory account
            \App\Models\JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_code' => $inventoryAccountCode,
                'account_name' => 'Fuel Inventory',
                'debit_amount' => 0,
                'credit_amount' => $expenseAmount,
                'description' => "Stock out: {$litresConsumed}L @ {$avgCost}/L",
            ]);

            Log::info("Journal entry posted for fuel consumption", [
                'entry_id' => $journalEntry->id,
                'amount' => $expenseAmount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to post journal entry for fuel consumption', [
                'error' => $e->getMessage(),
                'consumption_id' => $consumption->id,
            ]);
            // Don't fail the transaction if journal posting fails
            // The consumption has already been recorded
        }
    }

    /**
     * Get weighted average cost per litre for a fuel product
     */
    private function getAverageCostPerLitre(int $fuelProductId, int $branchId): float
    {
        // This should be calculated from purchases or a cost tracking table
        // For now, return a default or query from settings
        $setting = \App\Models\Setting::where('key', "fuel_avg_cost_{$fuelProductId}_{$branchId}")
            ->first();

        return $setting ? (float) $setting->value : 250.00; // Default cost per litre
    }

    /**
     * Generate unique entry number
     */
    private function generateEntryNumber(int $branchId): string
    {
        // Canonical sequence service (config: erp.sequences.journal = JE-…).
        // Pehle yahan ek ghair-mojood \App\Models\NumberSequence model call
        // hota tha jo har journal posting par fatal deta tha.
        // Note: caller (recordConsumption) pehle se DB::transaction me hai,
        // jo is service ki lock shart hai.
        return app(NumberSequenceService::class)->next('journal');
    }

    /**
     * Get daily consumption summary
     */
    public function getDailyConsumptionSummary(
        \DateTime $date,
        int $branchId
    ): array {
        $consumptions = InternalFuelConsumption::whereDate('consumption_date', $date)
            ->with('tank')
            ->get();

        $summary = [
            'date' => $date->format('Y-m-d'),
            'total_litres' => 0,
            'by_type' => [],
            'by_tank' => [],
        ];

        foreach ($consumptions as $consumption) {
            $summary['total_litres'] += $consumption->litres_consumed;

            // By type
            if (!isset($summary['by_type'][$consumption->consumption_type])) {
                $summary['by_type'][$consumption->consumption_type] = 0;
            }
            $summary['by_type'][$consumption->consumption_type] += $consumption->litres_consumed;

            // By tank
            $tankName = $consumption->tank->name;
            if (!isset($summary['by_tank'][$tankName])) {
                $summary['by_tank'][$tankName] = 0;
            }
            $summary['by_tank'][$tankName] += $consumption->litres_consumed;
        }

        // Round all values
        $summary['total_litres'] = round($summary['total_litres'], 3);
        foreach ($summary['by_type'] as &$litres) {
            $litres = round($litres, 3);
        }
        foreach ($summary['by_tank'] as &$litres) {
            $litres = round($litres, 3);
        }

        return $summary;
    }

    /**
     * Get consumption history for a date range
     */
    public function getConsumptionHistory(
        Carbon $fromDate,
        Carbon $toDate,
        ?int $tankId = null,
        ?string $consumptionType = null,
        int $branchId = null
    ): Collection {
        $query = InternalFuelConsumption::whereBetween('consumption_date', [$fromDate, $toDate]);

        if ($tankId) {
            $query->where('tank_id', $tankId);
        }

        if ($consumptionType) {
            $query->where('consumption_type', $consumptionType);
        }

        return $query->with('tank')
            ->orderByDesc('consumption_date')
            ->get();
    }

    /**
     * Calculate generator fuel cost for a date range
     */
    public function getGeneratorFuelCost(
        Carbon $fromDate,
        Carbon $toDate,
        int $branchId
    ): float {
        $consumptions = InternalFuelConsumption::whereBetween('consumption_date', [$fromDate, $toDate])
            ->where('consumption_type', 'GENERATOR')
            ->with('tank')
            ->get();

        $totalCost = 0;
        foreach ($consumptions as $consumption) {
            $avgCost = $this->getAverageCostPerLitre($consumption->tank->fuel_product_id, $branchId);
            $totalCost += $consumption->litres_consumed * $avgCost;
        }

        return round($totalCost, 2);
    }

    /**
     * Get monthly generator consumption report
     */
    public function getMonthlyGeneratorReport(int $year, int $month, int $branchId): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $consumptions = InternalFuelConsumption::whereBetween('consumption_date', [$startDate, $endDate])
            ->where('consumption_type', 'GENERATOR')
            ->with('tank')
            ->get();

        $totalLitres = 0;
        $totalCost = 0;
        $dailyBreakdown = [];

        foreach ($consumptions as $consumption) {
            $date = $consumption->consumption_date->format('Y-m-d');
            $avgCost = $this->getAverageCostPerLitre($consumption->tank->fuel_product_id, $branchId);
            $litresCost = $consumption->litres_consumed * $avgCost;

            $totalLitres += $consumption->litres_consumed;
            $totalCost += $litresCost;

            if (!isset($dailyBreakdown[$date])) {
                $dailyBreakdown[$date] = [
                    'litres' => 0,
                    'cost' => 0,
                ];
            }

            $dailyBreakdown[$date]['litres'] += $consumption->litres_consumed;
            $dailyBreakdown[$date]['cost'] += $litresCost;
        }

        return [
            'month' => sprintf('%04d-%02d', $year, $month),
            'total_litres' => round($totalLitres, 3),
            'total_cost' => round($totalCost, 2),
            'average_daily_litres' => round($totalLitres / $endDate->day, 3),
            'average_daily_cost' => round($totalCost / $endDate->day, 2),
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    /**
     * Estimate generator fuel budget for a month
     * Based on historical consumption
     */
    public function estimateBudget(
        int $pastMonths = 3,
        int $branchId = null
    ): array {
        $estimates = [];

        for ($i = $pastMonths; $i >= 1; $i--) {
            $date = now()->subMonths($i);
            $report = $this->getMonthlyGeneratorReport($date->year, $date->month, $branchId ?? 1);
            $estimates[] = [
                'month' => $report['month'],
                'litres' => $report['total_litres'],
                'cost' => $report['total_cost'],
            ];
        }

        // Calculate average
        $avgLitres = 0;
        $avgCost = 0;

        foreach ($estimates as $est) {
            $avgLitres += $est['litres'];
            $avgCost += $est['cost'];
        }

        $avgLitres = round($avgLitres / count($estimates), 3);
        $avgCost = round($avgCost / count($estimates), 2);

        return [
            'historical_months' => $estimates,
            'average_monthly_litres' => $avgLitres,
            'average_monthly_cost' => $avgCost,
            'estimated_next_month_litres' => $avgLitres,
            'estimated_next_month_cost' => $avgCost,
        ];
    }

    /**
     * Reverse/undo a consumption entry
     * Used for corrections
     */
    public function reverseConsumption(InternalFuelConsumption $consumption, string $reason): InternalFuelConsumption
    {
        return DB::transaction(function () use ($consumption, $reason) {
            $tank = $consumption->tank;

            // Create reversing entry
            $reversal = InternalFuelConsumption::create([
                'tank_id' => $tank->id,
                'consumption_type' => $consumption->consumption_type,
                'litres_consumed' => -1 * $consumption->litres_consumed,
                'consumption_date' => now(),
                'description' => "REVERSAL: {$consumption->description} - {$reason}",
            ]);

            // Reverse stock movement
            TankMovement::create([
                'tank_id' => $tank->id,
                'fuel_product_id' => $tank->fuel_product_id,
                'branch_id' => $tank->branch_id,
                'quantity' => -1 * $consumption->litres_consumed,
                'before_quantity' => round($tank->current_stock, 3),
                'after_quantity' => round($tank->current_stock + $consumption->litres_consumed, 3),
                'type' => 'CORRECTION',
                'reference_type' => 'internal_fuel_consumption',
                'reference_id' => $reversal->id,
                'user_id' => auth()->id(),
            ]);

            // Update tank stock back
            $tank->update([
                'current_stock' => DB::raw("current_stock + {$consumption->litres_consumed}"),
            ]);

            Log::info("Fuel consumption reversed", [
                'original_id' => $consumption->id,
                'reversal_id' => $reversal->id,
                'reason' => $reason,
            ]);

            return $reversal;
        });
    }

    /**
     * Validate consumption type
     */
    public function isValidConsumptionType(string $type): bool
    {
        return in_array($type, ['GENERATOR', 'STATION_VEHICLE', 'TESTING', 'CLEANING']);
    }

    /**
     * Get all valid consumption types
     */
    public function getValidConsumptionTypes(): array
    {
        return ['GENERATOR', 'STATION_VEHICLE', 'TESTING', 'CLEANING'];
    }
}
