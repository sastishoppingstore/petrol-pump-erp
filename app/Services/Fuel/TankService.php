<?php

namespace App\Services\Fuel;

use App\Models\Tank;
use App\Models\TankReading;
use App\Support\Quantity;
use Illuminate\Validation\ValidationException;

class TankService
{
    /**
     * Record a physical dip reading and its variance against expected stock.
     *
     * Expected stock is the tank's system quantity at read time; variance is
     * physical - expected (signed). A shortage is negative.
     */
    public function recordReading(
        Tank $tank,
        string $physicalQuantity,
        ?string $notes = null,
        ?int $userId = null,
        ?string $readingDate = null,
    ): TankReading {
        if (Quantity::isNegative($physicalQuantity)) {
            throw ValidationException::withMessages([
                'physical_quantity' => 'Physical quantity cannot be negative.',
            ]);
        }

        if (Quantity::compare($physicalQuantity, Quantity::n($tank->capacity)) > 0) {
            throw ValidationException::withMessages([
                'physical_quantity' => sprintf(
                    'Physical quantity (%s) cannot exceed the tank capacity (%s).',
                    Quantity::format($physicalQuantity),
                    Quantity::format($tank->capacity),
                ),
            ]);
        }

        $expected = Quantity::n($tank->current_stock);
        $variance = Quantity::subtract($physicalQuantity, $expected);

        return TankReading::create([
            'branch_id' => $tank->branch_id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $tank->fuel_product_id,
            'reading_date' => $readingDate ?? today()->toDateString(),
            'physical_quantity' => Quantity::round($physicalQuantity),
            'expected_quantity' => $expected,
            'variance_quantity' => $variance,
            'variance_type' => $this->varianceType($variance),
            'notes' => $notes,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    public function varianceType(string $variance): string
    {
        if (Quantity::isZero($variance)) {
            return TankReading::VARIANCE_MATCH;
        }

        return Quantity::isNegative($variance)
            ? TankReading::VARIANCE_SHORTAGE
            : TankReading::VARIANCE_SURPLUS;
    }

    /**
     * Reject a tank whose operating limits make no sense.
     */
    public function validateLevels(Tank $tank): void
    {
        $capacity = Quantity::n($tank->capacity);

        if (Quantity::compare($capacity, '0') <= 0) {
            throw ValidationException::withMessages([
                'capacity' => 'Tank capacity must be greater than zero.',
            ]);
        }

        if (Quantity::compare(Quantity::n($tank->min_level), $capacity) > 0) {
            throw ValidationException::withMessages([
                'min_level' => 'Minimum level cannot exceed the tank capacity.',
            ]);
        }

        if (Quantity::compare(Quantity::n($tank->max_level), $capacity) > 0) {
            throw ValidationException::withMessages([
                'max_level' => 'Maximum level cannot exceed the tank capacity.',
            ]);
        }

        if (Quantity::compare(Quantity::n($tank->min_level), Quantity::n($tank->max_level)) > 0) {
            throw ValidationException::withMessages([
                'min_level' => 'Minimum level cannot be greater than the maximum level.',
            ]);
        }

        if (Quantity::compare(Quantity::n($tank->opening_stock), $capacity) > 0) {
            throw ValidationException::withMessages([
                'opening_stock' => 'Opening stock cannot exceed the tank capacity.',
            ]);
        }
    }
}
