<?php

namespace Database\Factories;

use App\Models\FuelProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuelProduct>
 */
class FuelProductFactory extends Factory
{
    protected $model = FuelProduct::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(3)),
            'name' => fake()->unique()->word().' '.fake()->word(),
            'unit' => FuelProduct::UNIT_LITRE,
            'selling_price' => fake()->randomFloat(2, 100, 300),
            'average_cost' => fake()->randomFloat(2, 90, 280),
            'tax_rate' => '0.00',
            'minimum_stock' => '0.000',
            'status' => FuelProduct::STATUS_ACTIVE,
        ];
    }

    public function priced(string $price): static
    {
        return $this->state(fn () => ['selling_price' => $price]);
    }
}
