<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-VP-01',
                'name' => 'Vital Petroleum OMC (Pvt) Ltd',
                'contact_person' => 'Muhammad Bilal (Supply Manager)',
                'phone' => '042-35789012',
                'email' => 'supplies@vitalpetroleum.com.pk',
                'address' => 'Vital House, Main Boulevard, Gulberg III, Lahore',
                'ntn_number' => '4210987-6',
                'strn_number' => '03-05-2710-001-82',
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'status' => Supplier::STATUS_ACTIVE,
                'notes' => 'Primary OMC franchise supply depot - Sheikhupura Supply Terminal',
            ],
            [
                'code' => 'SUP-PSO-02',
                'name' => 'Pakistan State Oil Company Limited (PSO)',
                'contact_person' => 'Tariq Mehmood (Terminal Officer)',
                'phone' => '042-36301234',
                'email' => 'sales.north@psopk.com',
                'address' => 'PSO Bulk Oil Installation, Machike, Sheikhupura',
                'ntn_number' => '0786123-1',
                'strn_number' => '03-01-2710-002-19',
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'status' => Supplier::STATUS_ACTIVE,
                'notes' => 'Machike Oil Depot terminal supplies',
            ],
            [
                'code' => 'SUP-PARCO-03',
                'name' => 'Pak-Arab Refinery Limited (PARCO)',
                'contact_person' => 'Khurram Shehzad',
                'phone' => '021-35090100',
                'email' => 'commercial@parco.com.pk',
                'address' => 'Corporate Headquarters, Korangi Creek Road, Karachi',
                'ntn_number' => '0891234-5',
                'strn_number' => '03-02-2710-003-45',
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'status' => Supplier::STATUS_ACTIVE,
                'notes' => 'Direct pipeline/tanker bulk allocations',
            ],
            [
                'code' => 'SUP-ENEOS-04',
                'name' => 'ENEOS Pakistan Official Lubricants Distributor',
                'contact_person' => 'Sheikh Rizwan',
                'phone' => '0300-8456789',
                'email' => 'distributor@eneos-pak.com',
                'address' => 'Badami Bagh Auto Market, Lahore',
                'ntn_number' => '3129845-2',
                'strn_number' => '03-08-3403-001-11',
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'status' => Supplier::STATUS_ACTIVE,
                'notes' => 'Authorized distributor of ENEOS Japanese engine oils and transmission fluids',
            ],
            [
                'code' => 'SUP-LOCAL-05',
                'name' => 'Sheikhupura Tuck Shop & Beverages Traders',
                'contact_person' => 'Chaudhry Naeem',
                'phone' => '0321-4567890',
                'email' => 'naeemtraders.skp@gmail.com',
                'address' => 'Railway Road, Sheikhupura',
                'ntn_number' => '5432198-7',
                'strn_number' => null,
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'status' => Supplier::STATUS_ACTIVE,
                'notes' => 'Local beverages, confectionery and mineral water supplier',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
