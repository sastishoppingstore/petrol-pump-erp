<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Dispenser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispenser>
 */
class DispenserFactory extends Factory
{
    protected $model = Dispenser::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'dispenser_number' => 'DP-'.Str::upper(Str::random(4)),
            'name' => fake()->word().' Dispenser',
            'model' => fake()->word().'-'.fake()->numberBetween(100, 999),
            'status' => Dispenser::STATUS_ACTIVE,
        ];
    }
}
