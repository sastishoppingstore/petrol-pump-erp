<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

/**
 * Pakistan's four provinces and the sales-tax authority that levies in each.
 *
 * Fuel is taxed by the provincial body, not FBR, so every branch must resolve
 * to one of these before it can sell tax-correctly.
 */
class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->provinces() as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']],
                [
                    'name' => $province['name'],
                    'tax_authority' => $province['authority'],
                    'authority_name' => $province['authority_name'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{code: string, name: string, authority: string, authority_name: string}>
     */
    private function provinces(): array
    {
        return [
            [
                'code' => 'PB',
                'name' => 'Punjab',
                'authority' => Province::PRA,
                'authority_name' => 'Punjab Revenue Authority',
            ],
            [
                'code' => 'SD',
                'name' => 'Sindh',
                'authority' => Province::SRB,
                'authority_name' => 'Sindh Revenue Board',
            ],
            [
                'code' => 'KPK',
                'name' => 'Khyber Pakhtunkhwa',
                'authority' => Province::KPRA,
                'authority_name' => 'Khyber Pakhtunkhwa Revenue Authority',
            ],
            [
                'code' => 'BA',
                'name' => 'Balochistan',
                'authority' => Province::BRA,
                'authority_name' => 'Balochistan Revenue Authority',
            ],
        ];
    }
}
