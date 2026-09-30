<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'EXP-ELEC', 'name' => 'Electricity', 'urdu_name' => 'بجلی کا بل'],
            ['code' => 'EXP-GEN', 'name' => 'Generator Fuel', 'urdu_name' => 'جنریٹر کا ڈیزل'],
            ['code' => 'EXP-MAINT', 'name' => 'Maintenance', 'urdu_name' => 'مرمت و دیکھ بھال'],
            ['code' => 'EXP-SAL', 'name' => 'Salaries', 'urdu_name' => 'تنخواہیں'],
            ['code' => 'EXP-REF', 'name' => 'Tea/Refreshment', 'urdu_name' => 'چائے / ریفریشمنٹ'],
            ['code' => 'EXP-TRANS', 'name' => 'Transport', 'urdu_name' => 'ٹرانسپورٹ'],
            ['code' => 'EXP-SEC', 'name' => 'Security', 'urdu_name' => 'سیکیورٹی'],
            ['code' => 'EXP-RENT', 'name' => 'Rent', 'urdu_name' => 'کرایہ'],
            ['code' => 'EXP-MISC', 'name' => 'Miscellaneous', 'urdu_name' => 'متفرق اخراجات'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'urdu_name' => $cat['urdu_name'],
                    'status' => ExpenseCategory::STATUS_ACTIVE,
                ]
            );
        }
    }
}
