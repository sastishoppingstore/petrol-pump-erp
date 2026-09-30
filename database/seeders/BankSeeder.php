<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

/**
 * Every bank a Pakistani filling station can realistically deposit cash into.
 *
 * Compiled from the State Bank of Pakistan / Banking Mohtasib lists of
 * scheduled banks. These are reference rows, not a hardcoded whitelist: an
 * admin can add a newly licensed bank from the Banks screen without a release.
 */
class BankSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->banks() as $bank) {
            Bank::firstOrCreate(
                ['name' => $bank['name'], 'branch_name' => $bank['branch'] ?? null],
                [
                    'short_name' => $bank['short'],
                    'bank_type' => $bank['type'],
                    'city' => $bank['city'] ?? null,
                    'status' => 'ACTIVE',
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, short: string, type: string, city?: string}>
     */
    private function banks(): array
    {
        $c = Bank::TYPE_COMMERCIAL;
        $i = Bank::TYPE_ISLAMIC;
        $p = Bank::TYPE_PUBLIC;
        $d = Bank::TYPE_DIGITAL;

        return [
            // ---------------- Commercial (conventional) ----------------
            ['name' => 'Habib Bank Limited', 'short' => 'HBL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'United Bank Limited', 'short' => 'UBL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'MCB Bank Limited', 'short' => 'MCB', 'type' => $c, 'city' => 'Lahore'],
            ['name' => 'Allied Bank Limited', 'short' => 'ABL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Askari Bank Limited', 'short' => 'AKBL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Bank Alfalah Limited', 'short' => 'BAFL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Bank AL Habib Limited', 'short' => 'BAHL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'The Bank of Khyber', 'short' => 'BOK', 'type' => $c, 'city' => 'Peshawar'],
            ['name' => 'The Bank of Punjab', 'short' => 'BOP', 'type' => $c, 'city' => 'Lahore'],
            ['name' => 'Faysal Bank Limited', 'short' => 'FIBL', 'type' => $c, 'city' => 'Lahore'],
            ['name' => 'JS Bank Limited', 'short' => 'JSBL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Habib Metropolitan Bank Limited', 'short' => 'HabibMetro', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'NMB Bank Limited', 'short' => 'NMB', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Samba Bank Limited', 'short' => 'SAMB', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Summit Bank Limited', 'short' => 'SBL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Soneri Bank Limited', 'short' => 'SNBL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Standard Chartered Bank Pakistan', 'short' => 'SCBPL', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Citibank N.A.', 'short' => 'CITI', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Deutsche Bank AG', 'short' => 'DB', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'Industrial and Commercial Bank of China Limited', 'short' => 'ICBC', 'type' => $c, 'city' => 'Karachi'],
            ['name' => 'First Women Bank Limited', 'short' => 'FWBL', 'type' => $c, 'city' => 'Karachi'],

            // ---------------- Islamic ----------------
            ['name' => 'BankIslami Pakistan Limited', 'short' => 'BIPL', 'type' => $i, 'city' => 'Karachi'],
            ['name' => 'Meezan Bank Limited', 'short' => 'MZBL', 'type' => $i, 'city' => 'Karachi'],
            ['name' => 'Dubai Islamic Bank Pakistan Limited', 'short' => 'DIBP', 'type' => $i, 'city' => 'Karachi'],
            ['name' => 'Al Baraka Bank (Pakistan) Limited', 'short' => 'ABPL', 'type' => $i, 'city' => 'Karachi'],
            ['name' => 'Islamic Bank Pakistan Limited', 'short' => 'IBP', 'type' => $i, 'city' => 'Islamabad'],
            ['name' => 'MCB Islamic Bank Limited', 'short' => 'MCBIBL', 'type' => $i, 'city' => 'Lahore'],

            // ---------------- Public sector ----------------
            ['name' => 'National Bank of Pakistan', 'short' => 'NBP', 'type' => $p, 'city' => 'Karachi'],
            ['name' => 'Sindh Bank Limited', 'short' => 'SBLP', 'type' => $p, 'city' => 'Karachi'],
            ['name' => 'Industrial Development Bank of Pakistan', 'short' => 'IDBP', 'type' => $p, 'city' => 'Karachi'],
            ['name' => 'Bank of Azad Jammu and Kashmir', 'short' => 'BAJK', 'type' => $p, 'city' => 'Muzaffarabad'],
            ['name' => 'Bank of Balochistan', 'short' => 'BoB', 'type' => $p, 'city' => 'Quetta'],
            ['name' => 'Punjab Provincial Cooperative Bank Ltd.', 'short' => 'PPCB', 'type' => $p, 'city' => 'Lahore'],
            ['name' => 'Karakoram Cooperative Bank Limited', 'short' => 'KCB', 'type' => $p, 'city' => 'Gilgit'],

            // ---------------- Digital / specialised ----------------
            ['name' => 'Easypaisa Bank Limited', 'short' => 'EPB', 'type' => $d, 'city' => 'Karachi'],
            ['name' => 'SME Bank Limited', 'short' => 'SMEB', 'type' => $d, 'city' => 'Islamabad'],
        ];
    }
}
