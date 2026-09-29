<?php

namespace Database\Factories;

use App\Models\Nozzle;
use App\Models\Shift;
use App\Models\ShiftNozzle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftNozzleFactory extends Factory
{
    protected $model = ShiftNozzle::class;

    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'nozzle_id' => Nozzle::factory(),
            'opening_meter' => '1000.000',
            'closing_meter' => null,
            'meter_sales_litres' => '0.000',
            'system_litres' => '0.000',
            'meter_variance' => '0.000',
        ];
    }
}
