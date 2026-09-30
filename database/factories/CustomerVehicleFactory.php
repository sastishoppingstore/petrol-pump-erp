<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerVehicle>
 */
class CustomerVehicleFactory extends Factory
{
    protected $model = CustomerVehicle::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'registration_number' => 'LEA-' . fake()->numberBetween(1000, 9999),
            'driver_name' => fake()->name(),
            'make' => 'Toyota',
            'model' => 'Corolla',
            'colour' => 'White',
            'type' => 'Car',
            'tank_capacity' => '50.000',
            'status' => 'ACTIVE',
        ];
    }
}
