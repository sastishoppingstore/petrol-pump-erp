<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\FuelProduct;
use App\Models\Nozzle;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Nozzle>
 */
class NozzleFactory extends Factory
{
    protected $model = Nozzle::class;

    public function definition(): array
    {
        $tank = Tank::factory();

        return [
            'branch_id' => Branch::factory(),
            'dispenser_id' => Dispenser::factory(),
            'tank_id' => $tank,
            'fuel_product_id' => function (array $attributes) {
                // Default to the tank's own fuel so the factory produces a
                // valid nozzle. Use ->mismatchedFuel() to test the rejection.
                return Tank::where('id', $attributes['tank_id'])->value('fuel_product_id');
            },
            'nozzle_number' => (string) fake()->unique()->numberBetween(1, 99999),
            'opening_meter' => '0.000',
            'current_meter' => '0.000',
            'meter_multiplier' => '1.000',
            'status' => Nozzle::STATUS_ACTIVE,
        ];
    }

    public function forTank(Tank $tank): static
    {
        return $this->state(fn () => [
            'tank_id' => $tank->id,
            'fuel_product_id' => $tank->fuel_product_id,
        ]);
    }

    public function withMeter(string $meter): static
    {
        return $this->state(fn () => [
            'opening_meter' => $meter,
            'current_meter' => $meter,
        ]);
    }
}
