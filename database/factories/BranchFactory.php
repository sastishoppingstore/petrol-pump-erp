<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(5)),
            'name' => fake()->unique()->city().' Station',
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->word(),
            'country' => 'Pakistan',
            'phone' => fake()->numerify('03#########'),
            'status' => Branch::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Branch::STATUS_INACTIVE]);
    }
}
