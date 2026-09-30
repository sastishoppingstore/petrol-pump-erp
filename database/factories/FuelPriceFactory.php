<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuelPrice>
 */
class FuelPriceFactory extends Factory
{
    protected $model = FuelPrice::class;

    public function definition(): array
    {
        return [
            'fuel_product_id' => FuelProduct::factory(),
            'branch_id' => Branch::factory(),
            'price' => fake()->randomFloat(2, 200, 350),
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'reason' => 'Factory price setting',
        ];
    }
}
