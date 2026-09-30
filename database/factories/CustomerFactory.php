<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => 'C'.Str::upper(Str::random(6)),
            'name' => fake()->name().' Transport',
            'phone' => fake()->numerify('03#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'credit_limit' => '50000.00',
            'opening_balance' => '0.00',
            'status' => Customer::STATUS_ACTIVE,
        ];
    }

    public function withLimit(string $limit): static
    {
        return $this->state(fn () => ['credit_limit' => $limit]);
    }
}
