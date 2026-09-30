<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ENEOS Lubricants
            [
                'code' => 'ENEOS-5W30-4L',
                'name' => 'ENEOS Sustina 5W-30 Fully Synthetic (4L)',
                'category' => Product::CATEGORY_LUBRICANT,
                'unit' => 'CAN',
                'cost_price' => '8200.00',
                'selling_price' => '9800.00',
                'current_stock' => '24.000',
                'min_stock_level' => '6.000',
                'barcode' => '4984245100012',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'Premium Japanese synthetic motor oil for modern petrol engines',
            ],
            [
                'code' => 'ENEOS-20W50-4L',
                'name' => 'ENEOS Super Gasoline 20W-50 (4L)',
                'category' => Product::CATEGORY_LUBRICANT,
                'unit' => 'CAN',
                'cost_price' => '4300.00',
                'selling_price' => '5200.00',
                'current_stock' => '36.000',
                'min_stock_level' => '8.000',
                'barcode' => '4984245100029',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'High viscosity mineral engine oil for high mileage cars & CNG',
            ],
            [
                'code' => 'ENEOS-15W40-4L',
                'name' => 'ENEOS Diesel Grand 15W-40 CI-4 (4L)',
                'category' => Product::CATEGORY_LUBRICANT,
                'unit' => 'CAN',
                'cost_price' => '4800.00',
                'selling_price' => '5800.00',
                'current_stock' => '20.000',
                'min_stock_level' => '6.000',
                'barcode' => '4984245100036',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'Heavy duty diesel engine oil for tractors, trucks & generators',
            ],
            [
                'code' => 'ENEOS-20W40-4T-1L',
                'name' => 'ENEOS 20W-40 4T Motorcycle (1L)',
                'category' => Product::CATEGORY_LUBRICANT,
                'unit' => 'BOTTLE',
                'cost_price' => '950.00',
                'selling_price' => '1200.00',
                'current_stock' => '60.000',
                'min_stock_level' => '15.000',
                'barcode' => '4984245100043',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'JASO MA2 high performance 4-stroke motorbike engine oil',
            ],
            [
                'code' => 'ENEOS-SAE50-4L',
                'name' => 'ENEOS Engine Oil SAE 50 Monograde (4L)',
                'category' => Product::CATEGORY_LUBRICANT,
                'unit' => 'CAN',
                'cost_price' => '3600.00',
                'selling_price' => '4400.00',
                'current_stock' => '18.000',
                'min_stock_level' => '5.000',
                'barcode' => '4984245100050',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'Mono-grade heavy engine oil for older diesel engines and agricultural machinery',
            ],

            // Filters
            [
                'code' => 'FILT-GUARD-TOY',
                'name' => 'Guard Oil Filter - Toyota Corolla / GLI',
                'category' => Product::CATEGORY_FILTER,
                'unit' => 'PIECE',
                'cost_price' => '650.00',
                'selling_price' => '950.00',
                'current_stock' => '25.000',
                'min_stock_level' => '5.000',
                'barcode' => '896400010101',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'OEM fit for Corolla 1.3/1.6/1.8',
            ],
            [
                'code' => 'FILT-GUARD-HON',
                'name' => 'Guard Oil Filter - Honda Civic / City',
                'category' => Product::CATEGORY_FILTER,
                'unit' => 'PIECE',
                'cost_price' => '700.00',
                'selling_price' => '1000.00',
                'current_stock' => '20.000',
                'min_stock_level' => '5.000',
                'barcode' => '896400010102',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'OEM fit for City/Civic all models',
            ],
            [
                'code' => 'FILT-GUARD-SUZ',
                'name' => 'Guard Oil Filter - Suzuki Cultus / Alto / Mehran',
                'category' => Product::CATEGORY_FILTER,
                'unit' => 'PIECE',
                'cost_price' => '450.00',
                'selling_price' => '700.00',
                'current_stock' => '30.000',
                'min_stock_level' => '8.000',
                'barcode' => '896400010103',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'Small block Suzuki engines',
            ],

            // Tuck Shop
            [
                'code' => 'SHOP-WATER-1.5L',
                'name' => 'Aquafina Mineral Water 1.5L',
                'category' => Product::CATEGORY_TUCK_SHOP,
                'unit' => 'BOTTLE',
                'cost_price' => '90.00',
                'selling_price' => '120.00',
                'current_stock' => '120.000',
                'min_stock_level' => '24.000',
                'barcode' => '896400020201',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'Chilled mineral water',
            ],
            [
                'code' => 'SHOP-STING-300ML',
                'name' => 'Sting Berry Blast 300ml Glass Bottle',
                'category' => Product::CATEGORY_TUCK_SHOP,
                'unit' => 'BOTTLE',
                'cost_price' => '65.00',
                'selling_price' => '80.00',
                'current_stock' => '96.000',
                'min_stock_level' => '24.000',
                'barcode' => '896400020202',
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'Energy beverage',
            ],

            // Services
            [
                'code' => 'SRV-WASH-CAR',
                'name' => 'Full Body Car Wash with Vacuum',
                'category' => Product::CATEGORY_CAR_WASH,
                'unit' => 'SERVICE',
                'cost_price' => '250.00',
                'selling_price' => '800.00',
                'current_stock' => '0.000',
                'min_stock_level' => '0.000',
                'barcode' => null,
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'High pressure shampoo wash, undercarriage wash & interior vacuum',
            ],
            [
                'code' => 'SRV-TYRE-NITROGEN',
                'name' => 'Nitrogen Tyre Inflation (4 Tyres)',
                'category' => Product::CATEGORY_TYRE,
                'unit' => 'SERVICE',
                'cost_price' => '50.00',
                'selling_price' => '300.00',
                'current_stock' => '0.000',
                'min_stock_level' => '0.000',
                'barcode' => null,
                'status' => Product::STATUS_ACTIVE,
                'notes' => 'High purity nitrogen tyre purge and fill',
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
