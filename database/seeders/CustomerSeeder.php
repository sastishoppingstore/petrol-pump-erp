<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'code' => 'CUST-001',
                'name' => 'M/s Sheikhupura Goods Transport Company',
                'phone' => '0300-4567890',
                'email' => 'sheikhupura.transport@gmail.com',
                'address' => 'Lahore-Sargodha Road, near Toll Plaza, Sheikhupura',
                'ntn_number' => '1234567-8',
                'cnic' => '35201-1234567-1',
                'is_tax_liable' => true,
                'credit_limit' => '1500000.00', // 15 Lakh
                'opening_balance' => '250000.00',
                'current_balance' => '250000.00',
                'status' => Customer::STATUS_ACTIVE,
                'notes' => 'Fleet client with 15 days credit term. Monthly volume approx 20,000 litres High Speed Diesel.',
                'vehicles' => [
                    [
                        'registration_number' => 'LEA-1234',
                        'driver_name' => 'Muhammad Tariq',
                        'make' => 'Bedford',
                        'model' => 'Rocket Truck 1998',
                        'colour' => 'Yellow / Red Truck Art',
                        'type' => 'TRUCK',
                        'tank_capacity' => '300.000',
                    ],
                    [
                        'registration_number' => 'LXZ-9872',
                        'driver_name' => 'Jamshed Khan',
                        'make' => 'Hino',
                        'model' => '500 Series 2016',
                        'colour' => 'White',
                        'type' => 'TRUCK',
                        'tank_capacity' => '400.000',
                    ],
                    [
                        'registration_number' => 'SKP-5520',
                        'driver_name' => 'Allah Ditta',
                        'make' => 'Master',
                        'model' => 'Foton 2019',
                        'colour' => 'Blue',
                        'type' => 'TRUCK',
                        'tank_capacity' => '180.000',
                    ],
                ],
            ],
            [
                'code' => 'CUST-002',
                'name' => 'Rana Cotton & Ginning Mills Sheikhupura',
                'phone' => '0321-7890123',
                'email' => 'ranacotton@mills.com.pk',
                'address' => 'Faisalabad Road, Sheikhupura Industrial Area',
                'ntn_number' => '2345678-9',
                'cnic' => '35202-7654321-3',
                'is_tax_liable' => true,
                'credit_limit' => '800000.00', // 8 Lakh
                'opening_balance' => '120000.00',
                'current_balance' => '120000.00',
                'status' => Customer::STATUS_ACTIVE,
                'notes' => 'Industrial diesel generator and fleet refilling',
                'vehicles' => [
                    [
                        'registration_number' => 'LES-4567',
                        'driver_name' => 'Asghar Ali',
                        'make' => 'Toyota',
                        'model' => 'Hilux Revo 2021',
                        'colour' => 'Super White',
                        'type' => 'PICKUP',
                        'tank_capacity' => '80.000',
                    ],
                    [
                        'registration_number' => 'LEB-7711',
                        'driver_name' => 'Rana Shahbaz',
                        'make' => 'Toyota',
                        'model' => 'Corolla Altis 2020',
                        'colour' => 'Attitude Black',
                        'type' => 'CAR',
                        'tank_capacity' => '55.000',
                    ],
                ],
            ],
            [
                'code' => 'CUST-003',
                'name' => 'Chaudhry Bashir Ahmed (Farmer / Zamindar)',
                'phone' => '0302-3344556',
                'email' => null,
                'address' => 'Mouza Bhikhi, District Sheikhupura',
                'ntn_number' => null,
                'cnic' => '35201-9988776-5',
                'is_tax_liable' => false,
                'credit_limit' => '300000.00', // 3 Lakh
                'opening_balance' => '50000.00',
                'current_balance' => '50000.00',
                'status' => Customer::STATUS_ACTIVE,
                'notes' => 'Agricultural diesel account for wheat harvest and tube wells. Settles after crop harvest.',
                'vehicles' => [
                    [
                        'registration_number' => 'TRC-3850',
                        'driver_name' => 'Bashir Ahmed',
                        'make' => 'Millat',
                        'model' => 'MF-385 Tractor',
                        'colour' => 'Red',
                        'type' => 'TRACTOR',
                        'tank_capacity' => '108.000',
                    ],
                ],
            ],
            [
                'code' => 'CUST-004',
                'name' => 'Government Graduate College Sheikhupura (Transport Wing)',
                'phone' => '0333-8899001',
                'email' => 'transport@ggcskp.edu.pk',
                'address' => 'Civil Lines, Sheikhupura',
                'ntn_number' => '9012345-0',
                'cnic' => '35201-4455667-9',
                'is_tax_liable' => false,
                'credit_limit' => '500000.00', // 5 Lakh
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'status' => Customer::STATUS_ACTIVE,
                'notes' => 'College student bus fleet credit account. Fortnightly billing.',
                'vehicles' => [
                    [
                        'registration_number' => 'SLG-2020',
                        'driver_name' => 'Ustad Munir',
                        'make' => 'Isuzu',
                        'model' => 'NQR 52 Seater Bus',
                        'colour' => 'Green & White',
                        'type' => 'BUS',
                        'tank_capacity' => '140.000',
                    ],
                ],
            ],
        ];

        foreach ($customers as $data) {
            $vehicles = $data['vehicles'] ?? [];
            unset($data['vehicles']);

            $customer = Customer::updateOrCreate(['code' => $data['code']], $data);

            foreach ($vehicles as $vData) {
                CustomerVehicle::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'registration_number' => $vData['registration_number'],
                    ],
                    array_merge($vData, ['customer_id' => $customer->id, 'status' => 'ACTIVE'])
                );
            }
        }
    }
}
