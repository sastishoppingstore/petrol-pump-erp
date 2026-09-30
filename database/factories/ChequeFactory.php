<?php

namespace Database\Factories;

use App\Models\Cheque;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChequeFactory extends Factory
{
    protected $model = Cheque::class;

    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'bank_account_id' => BankAccount::factory(),
            'cheque_number' => $this->faker->unique()->numerify('#########'),
            'issued_to' => $this->faker->company(),
            'amount' => (string) $this->faker->randomFloat(2, 5000, 500000),
            'issue_date' => $issueDate,
            'due_date' => $this->faker->dateTimeBetween($issueDate, '+30 days'),
            'status' => $this->faker->randomElement(['ISSUED', 'PRESENTED', 'CLEARED', 'BOUNCED', 'CANCELLED']),
        ];
    }

    public function cleared(): static
    {
        return $this->state(fn () => ['status' => 'CLEARED']);
    }

    public function bounced(): static
    {
        return $this->state(fn () => ['status' => 'BOUNCED']);
    }
}
