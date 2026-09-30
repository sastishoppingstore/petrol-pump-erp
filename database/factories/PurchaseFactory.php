<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\FuelProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'supplier_id' => Supplier::factory(),
            'fuel_product_id' => FuelProduct::factory(),
            'invoice_number' => 'PUR-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'purchase_date' => now(),
            'quantity' => $this->faker->randomFloat(3, 100, 10000),
            'rate' => $this->faker->randomFloat(2, 100, 250),
            'tax_rate' => '0.17',
            'total_amount' => $this->faker->randomFloat(2, 11700, 2500000),
            'paid_amount' => 0,
            'status' => 'APPROVED',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'DRAFT']);
    }
}
