<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'shift_number' => 'SHIFT-2026-'.fake()->unique()->numberBetween(100000, 999999),
            'opened_at' => now(),
            'opening_cash' => '1000.00',
            'expected_cash' => '1000.00',
            'status' => Shift::STATUS_OPEN,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => Shift::STATUS_CLOSED,
            'closed_at' => now(),
            'actual_cash' => '1000.00',
            'cash_difference' => '0.00',
        ]);
    }
}
