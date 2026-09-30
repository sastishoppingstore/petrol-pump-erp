<?php

namespace Database\Factories;

use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Tank;
use App\Models\Dispenser;
use App\Models\Nozzle;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'branch_id' => Branch::factory(),
            'fuel_product_id' => FuelProduct::factory(),
            'tank_id' => Tank::factory(),
            'dispenser_id' => Dispenser::factory()->create(),
            'nozzle_id' => Nozzle::factory()->create(),
            'litres' => $this->faker->randomFloat(3, 10, 1000),
            'rate' => $this->faker->randomFloat(2, 100, 300),
            'cost_rate' => $this->faker->randomFloat(2, 80, 250),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'meter_start' => $this->faker->randomFloat(3, 0, 100000),
            'meter_end' => $this->faker->randomFloat(3, 0, 100000),
        ];
    }
}
