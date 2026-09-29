<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tank>
 */
class TankFactory extends Factory
{
    protected $model = Tank::class;

    public function definition(): array
    {
        $capacity = fake()->numberBetween(5000, 40000);

        return [
            'branch_id' => Branch::factory(),
            'fuel_product_id' => FuelProduct::factory(),
            'tank_number' => 'TK-'.fake()->unique()->numberBetween(1, 999999),
            'name' => fake()->word().' Tank',
            'capacity' => number_format($capacity, 3, '.', ''),
            'min_level' => number_format($capacity * 0.10, 3, '.', ''),
            'max_level' => number_format($capacity * 0.95, 3, '.', ''),
            'opening_stock' => number_format($capacity * 0.5, 3, '.', ''),
            'current_stock' => number_format($capacity * 0.5, 3, '.', ''),
            'low_stock_threshold' => number_format($capacity * 0.15, 3, '.', ''),
            'status' => Tank::STATUS_ACTIVE,
        ];
    }

    public function withStock(string $quantity): static
    {
        return $this->state(fn () => ['current_stock' => $quantity]);
    }

    public function forFuel(FuelProduct $fuel): static
    {
        return $this->state(fn () => ['fuel_product_id' => $fuel->id]);
    }
}
