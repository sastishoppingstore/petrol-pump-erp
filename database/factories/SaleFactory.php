<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'employee_id' => User::factory(),
            'customer_id' => null,
            'vehicle_id' => null,
            'invoice_number' => 'INV-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'sale_date' => now(),
            'subtotal' => $this->faker->randomFloat(2, 1000, 10000),
            'discount' => 0,
            'tax' => $this->faker->randomFloat(2, 100, 1000),
            'total' => $this->faker->randomFloat(2, 1100, 11000),
            'status' => Sale::STATUS_COMPLETED,
        ];
    }

    public function voided(): static
    {
        return $this->state(fn () => ['status' => Sale::STATUS_VOIDED]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => ['status' => Sale::STATUS_REFUNDED]);
    }
}
