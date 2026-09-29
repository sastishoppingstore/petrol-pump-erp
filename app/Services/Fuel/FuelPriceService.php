<?php

namespace App\Services\Fuel;

use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Services\Audit\AuditLogService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class FuelPriceService
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * The rate a sale must use (spec section 3).
     *
     * Resolved server-side. The rate a browser sends is never trusted.
     */
    public function rateFor(FuelProduct $fuel, ?int $branchId = null): string
    {
        $rate = $fuel->currentPrice($branchId);

        if (Money::compare($rate, '0') <= 0) {
            throw ValidationException::withMessages([
                'rate' => "No selling price is set for '{$fuel->name}'. Set a price before selling it.",
            ]);
        }

        return $rate;
    }

    /**
     * Change a fuel's price.
     *
     * Requires the price_change permission, closes the current price window
     * and appends a new fuel_prices row. The history is never overwritten.
     */
    public function changePrice(
        FuelProduct $fuel,
        string $newPrice,
        ?int $branchId,
        int $userId,
        ?string $reason = null,
        ?\Carbon\CarbonInterface $effectiveFrom = null,
    ): FuelPrice {
        if (Money::compare($newPrice, '0') < 0) {
            throw ValidationException::withMessages([
                'selling_price' => 'Price cannot be negative.',
            ]);
        }

        $effectiveFrom ??= now();

        try {
            return DB::transaction(function () use ($fuel, $newPrice, $branchId, $userId, $reason, $effectiveFrom) {
                // Lock the current row so two concurrent price changes cannot
                // both open a window and leave overlapping history.
                $current = FuelPrice::query()
                    ->where('fuel_product_id', $fuel->id)
                    ->when(
                        $branchId,
                        fn ($q) => $q->where(fn ($q2) => $q2->where('branch_id', $branchId)->orWhereNull('branch_id'))
                    )
                    ->where('effective_from', '<=', $effectiveFrom)
                    ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>', $effectiveFrom))
                    ->orderByRaw('branch_id IS NULL')
                    ->orderByDesc('effective_from')
                    ->lockForUpdate()
                    ->first();

                if ($current) {
                    $current->update(['effective_to' => $effectiveFrom]);
                }

                $price = FuelPrice::create([
                    'fuel_product_id' => $fuel->id,
                    'branch_id' => $branchId,
                    'price' => Money::round($newPrice),
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'created_by' => $userId,
                    'reason' => $reason,
                ]);

                // Only the fuel product's own headline price is mirrored here;
                // branch-specific overrides live solely in fuel_prices.
                if ($branchId === null) {
                    $fuel->update(['selling_price' => Money::round($newPrice)]);
                }

                $this->audit->record(
                    userId: $userId,
                    action: 'price_change',
                    module: 'fuel',
                    referenceType: FuelPrice::class,
                    referenceId: $price->id,
                    oldData: ['price' => $current?->price, 'effective_to' => $current?->effective_to],
                    newData: ['price' => $price->price, 'effective_from' => $effectiveFrom, 'reason' => $reason],
                );

                return $price;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Fuel price change failed', [
                'fuel_product_id' => $fuel->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'selling_price' => 'Unable to change the price. No changes were saved.',
            ]);
        }
    }

    /**
     * Price history for the fuel products screen.
     */
    public function history(FuelProduct $fuel, int $limit = 50)
    {
        return $fuel->prices()
            ->with(['creator', 'branch'])
            ->orderByDesc('effective_from')
            ->limit($limit)
            ->get();
    }
}
