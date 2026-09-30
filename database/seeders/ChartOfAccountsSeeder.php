<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // ASSETS (1000 - 1999)
            [
                'code' => Account::CODE_CASH_IN_HAND,
                'name' => 'Cash in Hand',
                'urdu_name' => 'کیش ان ہینڈ (نقد رقم)',
                'type' => Account::TYPE_ASSET,
                'subcategory' => 'Current Assets',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Main forecourt till and safe cash',
            ],
            [
                'code' => Account::CODE_BANK_ACCOUNTS,
                'name' => 'Bank Accounts',
                'urdu_name' => 'بینک اکاؤنٹس',
                'type' => Account::TYPE_ASSET,
                'subcategory' => 'Current Assets',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Commercial bank deposits and balances',
            ],
            [
                'code' => Account::CODE_ACCOUNTS_RECEIVABLE,
                'name' => 'Accounts Receivable',
                'urdu_name' => 'ادھار وصولیاں (کھاتہ داران)',
                'type' => Account::TYPE_ASSET,
                'subcategory' => 'Current Assets',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Customer credit / Udhaar balance',
            ],
            [
                'code' => Account::CODE_OMC_CARD_RECEIVABLE,
                'name' => 'OMC Card Receivable',
                'urdu_name' => 'او ایم سی فلیٹ کارڈ وصولیاں',
                'type' => Account::TYPE_ASSET,
                'subcategory' => 'Current Assets',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Vital / PSO / Shell fleet and POS card receivables',
            ],
            [
                'code' => Account::CODE_FUEL_INVENTORY,
                'name' => 'Fuel Inventory',
                'urdu_name' => 'ایندھن کا ذخیرہ (Fuel Stock)',
                'type' => Account::TYPE_ASSET,
                'subcategory' => 'Inventory',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Underground storage tanks petrol & diesel stock',
            ],
            [
                'code' => Account::CODE_LUBRICANT_INVENTORY,
                'name' => 'Lubricant Inventory',
                'urdu_name' => 'لیوب آئل ذخیرہ',
                'type' => Account::TYPE_ASSET,
                'subcategory' => 'Inventory',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Engine oil and lubricant packaged stock',
            ],

            // LIABILITIES (2000 - 2999)
            [
                'code' => Account::CODE_ACCOUNTS_PAYABLE,
                'name' => 'Accounts Payable',
                'urdu_name' => 'واجب الادا رقوم (سپلائرز)',
                'type' => Account::TYPE_LIABILITY,
                'subcategory' => 'Current Liabilities',
                'normal_balance' => Account::BALANCE_CREDIT,
                'is_system' => true,
                'notes' => 'OMC and fuel supplier liabilities',
            ],
            [
                'code' => Account::CODE_SALES_TAX_PAYABLE,
                'name' => 'Sales Tax Payable',
                'urdu_name' => 'سیلز ٹیکس واجب الادا (PRA / FBR)',
                'type' => Account::TYPE_LIABILITY,
                'subcategory' => 'Current Liabilities',
                'normal_balance' => Account::BALANCE_CREDIT,
                'is_system' => true,
                'notes' => 'Provincial Sales Tax / GST collected payable to government',
            ],

            // EQUITY (3000 - 3999)
            [
                'code' => Account::CODE_OWNER_CAPITAL,
                'name' => 'Owner Capital',
                'urdu_name' => 'مالک کا سرمایہ',
                'type' => Account::TYPE_EQUITY,
                'subcategory' => 'Equity',
                'normal_balance' => Account::BALANCE_CREDIT,
                'is_system' => true,
                'notes' => 'Proprietor initial and accumulated capital',
            ],
            [
                'code' => Account::CODE_OWNER_DRAWINGS,
                'name' => 'Owner Drawings',
                'urdu_name' => 'مالک کے ذاتی اخراجات',
                'type' => Account::TYPE_EQUITY,
                'subcategory' => 'Equity',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Owner drawings from station funds',
            ],

            // REVENUE (4000 - 4999)
            [
                'code' => Account::CODE_FUEL_SALES_REVENUE,
                'name' => 'Fuel Sales Revenue',
                'urdu_name' => 'ایندھن فروخت آمدن',
                'type' => Account::TYPE_REVENUE,
                'subcategory' => 'Operating Revenue',
                'normal_balance' => Account::BALANCE_CREDIT,
                'is_system' => true,
                'notes' => 'Revenue from dispensing PMG, HSD, HOBC',
            ],
            [
                'code' => Account::CODE_LUBE_SALES_REVENUE,
                'name' => 'Lube Sales Revenue',
                'urdu_name' => 'لیوب فروخت آمدن',
                'type' => Account::TYPE_REVENUE,
                'subcategory' => 'Operating Revenue',
                'normal_balance' => Account::BALANCE_CREDIT,
                'is_system' => true,
                'notes' => 'Revenue from lubricants, filters and store items',
            ],

            // EXPENSES & COGS (5000 - 6999)
            [
                'code' => Account::CODE_COST_OF_FUEL_SOLD,
                'name' => 'Cost of Fuel Sold',
                'urdu_name' => 'فروخت شدہ ایندھن کی لاگت',
                'type' => Account::TYPE_EXPENSE,
                'subcategory' => 'Cost of Goods Sold',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Purchase cost of fuel dispensed',
            ],
            [
                'code' => Account::CODE_COST_OF_LUBE_SOLD,
                'name' => 'Cost of Lube Sold',
                'urdu_name' => 'فروخت شدہ لیوب کی لاگت',
                'type' => Account::TYPE_EXPENSE,
                'subcategory' => 'Cost of Goods Sold',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Cost of lubricants sold',
            ],
            [
                'code' => Account::CODE_INVENTORY_GAIN_LOSS,
                'name' => 'Inventory Gain/Loss',
                'urdu_name' => 'انوینٹری نفع / نقصان (Stock Variance)',
                'type' => Account::TYPE_EXPENSE,
                'subcategory' => 'Operating Expenses',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Tank evaporation, temperature variation, calibration gain/loss',
            ],
            [
                'code' => Account::CODE_CASH_SHORT_OVER,
                'name' => 'Cash Short/Over',
                'urdu_name' => 'کیش کمی بیشی (Cash Variance)',
                'type' => Account::TYPE_EXPENSE,
                'subcategory' => 'Operating Expenses',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Shift cash differences and cashier reconciliation variance',
            ],
            [
                'code' => Account::CODE_OPERATING_EXPENSES,
                'name' => 'Operating Expenses',
                'urdu_name' => 'کاروباری اخراجات',
                'type' => Account::TYPE_EXPENSE,
                'subcategory' => 'Operating Expenses',
                'normal_balance' => Account::BALANCE_DEBIT,
                'is_system' => true,
                'notes' => 'Electricity, salaries, maintenance, generator fuel, stationery',
            ],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        // Standard Expense Categories
        $opAccount = Account::where('code', Account::CODE_OPERATING_EXPENSES)->first();
        $categories = [
            ['code' => 'EXP-ELEC', 'name' => 'Electricity & WAPDA Bill', 'urdu_name' => 'بجلی کا بل (لیسکو / واپڈا)'],
            ['code' => 'EXP-GEN', 'name' => 'Generator Fuel & Maintenance', 'urdu_name' => 'جنریٹر ڈیزل اور مرمت'],
            ['code' => 'EXP-SAL', 'name' => 'Staff Salaries & Meals', 'urdu_name' => 'ملازمین تنخواہ اور کھانا'],
            ['code' => 'EXP-MAINT', 'name' => 'Forecourt & Machine Maintenance', 'urdu_name' => 'مشینوں اور پمپ کی دیکھ بھال'],
            ['code' => 'EXP-STAT', 'name' => 'Stationery & Printing', 'urdu_name' => 'اسٹیشنری اور پرنٹنگ'],
            ['code' => 'EXP-MISC', 'name' => 'Miscellaneous Expenses', 'urdu_name' => 'متفرق اخراجات'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(
                ['code' => $cat['code']],
                array_merge($cat, ['account_id' => $opAccount?->id, 'status' => 'ACTIVE'])
            );
        }
    }
}
