<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Tank;
use App\Services\Accounting\AccountingEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingEngineService $service;
    protected Branch $branch;
    protected User $user;
    protected FuelProduct $fuel;
    protected Supplier $supplier;
    protected Tank $tank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AccountingEngineService::class);
        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->fuel = FuelProduct::factory()->create();
        $this->tank = Tank::factory()->create(['branch_id' => $this->branch->id, 'fuel_product_id' => $this->fuel->id]);
        $this->supplier = Supplier::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->user);
    }

    public function test_post_sale_transaction_cash()
    {
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->user->id,
            'customer_id' => null,
            'invoice_number' => 'INV-' . now()->year . '-000001',
            'sale_date' => now(),
            'subtotal' => 1000.00,
            'discount' => 0,
            'tax' => 100.00,
            'total' => 1100.00,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->fuel->id,
            'tank_id' => $this->tank->id,
            'litres' => 100,
            'rate' => 10.00,
            'cost_rate' => 8.00,
            'amount' => 1000.00,
        ]);

        $entry = $this->service->postSaleTransaction($sale, $this->branch->id);

        $this->assertNotNull($entry);
        $this->assertEquals('SALE', $entry->entry_type);
        $this->assertTrue($this->service->isEntryBalanced($entry));

        $lines = $entry->lines;
        $this->assertGreaterThan(0, $lines->count());

        $totalDebit = $lines->sum('debit_amount');
        $totalCredit = $lines->sum('credit_amount');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }

    public function test_post_sale_transaction_credit()
    {
        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);

        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->user->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . now()->year . '-000002',
            'sale_date' => now(),
            'subtotal' => 2000.00,
            'discount' => 100.00,
            'tax' => 190.00,
            'total' => 2090.00,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->fuel->id,
            'tank_id' => $this->tank->id,
            'litres' => 200,
            'rate' => 10.00,
            'cost_rate' => 8.00,
            'amount' => 2000.00,
        ]);

        $entry = $this->service->postSaleTransaction($sale, $this->branch->id);

        $this->assertNotNull($entry);
        $this->assertEquals('SALE', $entry->entry_type);
        $this->assertTrue($this->service->isEntryBalanced($entry));

        $receivableLines = $entry->lines()->whereHas('account', function ($q) {
            $q->where('code', '1201');
        })->get();

        $this->assertTrue($receivableLines->count() > 0);
    }

    public function test_post_purchase_transaction()
    {
        $purchase = Purchase::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'fuel_product_id' => $this->fuel->id,
            'invoice_number' => 'PUR-' . now()->year . '-000001',
            'purchase_date' => now(),
            'quantity' => 1000,
            'rate' => 10.00,
            'tax_rate' => '0.17',
            'total_amount' => 11700.00,
            'paid_amount' => 0,
            'status' => 'APPROVED',
        ]);

        $entry = $this->service->postPurchaseTransaction($purchase, $this->branch->id);

        $this->assertNotNull($entry);
        $this->assertEquals('PURCHASE', $entry->entry_type);
        $this->assertTrue($this->service->isEntryBalanced($entry));

        $inventoryLines = $entry->lines()->whereHas('account', function ($q) {
            $q->where('code', '1301');
        })->get();

        $this->assertTrue($inventoryLines->count() > 0);
    }

    public function test_entry_balance_verification()
    {
        $entry = JournalEntry::create([
            'branch_id' => $this->branch->id,
            'entry_number' => 'JE-TEST-2026-000001',
            'entry_date' => now(),
            'entry_type' => 'MANUAL',
            'status' => 'DRAFT',
            'posted_by' => $this->user->id,
        ]);

        $cashAccount = Account::create([
            'branch_id' => $this->branch->id,
            'code' => '1101-test',
            'name' => 'Cash Test',
            'type' => 'ASSET',
            'classification' => 'BANK',
            'normal_balance' => 'DEBIT',
        ]);

        $revenueAccount = Account::create([
            'branch_id' => $this->branch->id,
            'code' => '4101-test',
            'name' => 'Sales Revenue Test',
            'type' => 'REVENUE',
            'classification' => 'REVENUE',
            'normal_balance' => 'CREDIT',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $cashAccount->id,
            'debit_amount' => 1000.00,
            'credit_amount' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $revenueAccount->id,
            'debit_amount' => 0,
            'credit_amount' => 1000.00,
        ]);

        $this->assertTrue($this->service->isEntryBalanced($entry));
    }

    public function test_trial_balance_calculation()
    {
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->user->id,
            'customer_id' => null,
            'invoice_number' => 'INV-' . now()->year . '-000003',
            'sale_date' => now(),
            'subtotal' => 5000.00,
            'discount' => 0,
            'tax' => 500.00,
            'total' => 5500.00,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->fuel->id,
            'tank_id' => $this->tank->id,
            'litres' => 500,
            'rate' => 10.00,
            'cost_rate' => 8.00,
            'amount' => 5000.00,
        ]);

        $this->service->postSaleTransaction($sale, $this->branch->id);

        $trialBalance = $this->service->getTrialBalance($this->branch->id);

        $this->assertArrayHasKey('total_debits', $trialBalance);
        $this->assertArrayHasKey('total_credits', $trialBalance);
        $this->assertArrayHasKey('accounts', $trialBalance);

        $this->assertEqualsWithDelta($trialBalance['total_debits'], $trialBalance['total_credits'], 0.01);
    }

    public function test_journal_entry_balance_on_save()
    {
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->user->id,
            'customer_id' => null,
            'invoice_number' => 'INV-' . now()->year . '-000004',
            'sale_date' => now(),
            'subtotal' => 1000.00,
            'discount' => 0,
            'tax' => 100.00,
            'total' => 1100.00,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->fuel->id,
            'tank_id' => $this->tank->id,
            'litres' => 100,
            'rate' => 10.00,
            'cost_rate' => 8.00,
            'amount' => 1000.00,
        ]);

        $entry = $this->service->postSaleTransaction($sale, $this->branch->id);

        // Verify the entry has correct totals
        $this->assertEquals($entry->total_debit, $entry->total_credit);
    }

    public function test_cogs_in_sale_entry()
    {
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->user->id,
            'customer_id' => null,
            'invoice_number' => 'INV-' . now()->year . '-000005',
            'sale_date' => now(),
            'subtotal' => 2000.00,
            'discount' => 0,
            'tax' => 200.00,
            'total' => 2200.00,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->fuel->id,
            'tank_id' => $this->tank->id,
            'litres' => 200,
            'rate' => 10.00,
            'cost_rate' => 8.00,
            'amount' => 2000.00,
        ]);

        $entry = $this->service->postSaleTransaction($sale, $this->branch->id);

        // Should have COGS entry: 200 liters * 8.00 = 1600
        $cogsLines = $entry->lines()->whereHas('account', function ($q) {
            $q->where('code', '5101');
        })->get();

        $this->assertTrue($cogsLines->count() > 0);
        $this->assertEquals(1600.00, $cogsLines->first()->debit_amount);
    }
}
