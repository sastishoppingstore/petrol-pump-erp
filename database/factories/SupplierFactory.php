<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => strtoupper(\Illuminate\Support\Str::random(6)),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'status' => 'ACTIVE',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'INACTIVE']);
    }
}
