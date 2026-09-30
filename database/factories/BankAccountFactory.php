<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_id' => Bank::factory(),
            'account_number' => 'ACC-' . $this->faker->unique()->numerify('################'),
            'account_title' => $this->faker->company() . ' Fuel Station',
            'account_type' => $this->faker->randomElement(['CURRENT', 'SAVINGS', 'DEPOSIT']),
            'opening_balance' => (string) $this->faker->randomFloat(2, 10000, 1000000),
            'current_balance' => (string) $this->faker->randomFloat(2, 10000, 1000000),
            'status' => 'ACTIVE',
        ];
    }
}
