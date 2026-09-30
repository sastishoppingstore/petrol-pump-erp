<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        $banks = [
            ['HBL', 'Habib Bank Limited'],
            ['NBP', 'National Bank of Pakistan'],
            ['MCB', 'MCB Bank Limited'],
            ['UBL', 'United Bank Limited'],
            ['ABL', 'Allied Bank Limited'],
            ['ASKARI', 'Bank Askari Limited'],
            ['ALFALAH', 'Al Baraka Islamic Bank'],
        ];

        $bank = $this->faker->randomElement($banks);

        return [
            'bank_code' => $bank[0],
            'bank_name' => $bank[1],
            'bank_name_ur' => 'بینک',
        ];
    }
}
