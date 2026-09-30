<?php

namespace Database\Factories;

use App\Models\Tank;
use App\Models\Branch;
use App\Models\FuelProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class TankFactory extends Factory
{
    protected $model = Tank::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'fuel_product_id' => FuelProduct::factory(),
            'tank_number' => 'TANK-' . $this->faker->unique()->numberBetween(1, 99),
            'name' => 'Tank ' . $this->faker->numberBetween(1, 10),
            'capacity' => $this->faker->randomFloat(3, 10000, 50000),
            'current_stock' => $this->faker->randomFloat(3, 1000, 10000),
            'min_level' => $this->faker->randomFloat(3, 500, 5000),
            'max_level' => $this->faker->randomFloat(3, 40000, 50000),
            'status' => 'ACTIVE',
        ];
    }
}
