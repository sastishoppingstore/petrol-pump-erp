<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\CustomerVehicle;
use App\Models\FuelProduct;
use App\Models\Product;
use App\Models\ProductStockMovement;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\Tank;
use App\Models\TankDipChart;
use App\Models\TankReading;
use App\Models\User;
use App\Services\Fuel\TankCalibrationService;
use App\Support\Money;
use App\Support\Quantity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommercialAndInventoryTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->branch = Branch::factory()->create([
            'name' => 'Mehar Filling Station',
            'code' => 'MFS-01',
            'city' => 'Sheikhupura',
        ]);

        $this->admin = User::factory()->withRole(Role::ADMIN)->create();
        $this->admin->branches()->attach($this->branch->id, ['is_default' => true]);

        $this->actingAs($this->admin);
        session(['active_branch_id' => $this->branch->id]);
    }

    public function test_customer_creation_validation_and_listing(): void
    {
        $response = $this->get(route('customers.index'));
        $response->assertOk();
        $response->assertViewIs('customers.index');

        $payload = [
            'code' => 'CUST-TEST-001',
            'name' => 'Al-Rehman Goods Transport',
            'phone' => '03001234567',
            'cnic' => '35201-1234567-1',
            'email' => 'alrehman@example.com',
            'address' => 'Sheikhupura Bypass',
            'ntn_number' => '4210987-1',
            'credit_limit' => '250000.00',
            'opening_balance' => '50000.00',
            'status' => Customer::STATUS_ACTIVE,
            'notes' => 'Fleet of 8 10-wheeler trucks',
        ];

        $storeResponse = $this->post(route('customers.store'), $payload);
        $customer = Customer::where('code', 'CUST-TEST-001')->first();
        $this->assertNotNull($customer);

        $storeResponse->assertRedirect(route('customers.show', $customer));
        $this->assertEquals('50000.00', $customer->opening_balance);
        $this->assertEquals('50000.00', $customer->current_balance);
    }

    public function test_customer_vehicle_fleet_management(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'credit_limit' => '100000.00',
            'opening_balance' => '0.00',
            'current_balance' => '0.00',
        ]);

        $vehiclePayload = [
            'registration_number' => 'LES-26-9081',
            'driver_name' => 'Tariq Mehmood',
            'make' => 'Hino',
            'model' => 'FM8J Prime Mover',
            'type' => 'TRUCK',
            'tank_capacity' => '400.000',
            'status' => 'ACTIVE',
        ];

        $addResponse = $this->post(route('customers.vehicles.store', $customer), $vehiclePayload);
        $addResponse->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_vehicles', [
            'customer_id' => $customer->id,
            'registration_number' => 'LES-26-9081',
            'driver_name' => 'Tariq Mehmood',
        ]);

        $vehicle = $customer->vehicles()->first();
        $this->assertNotNull($vehicle);

        $deleteResponse = $this->delete(route('customers.vehicles.destroy', [$customer, $vehicle]));
        $deleteResponse->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseMissing('customer_vehicles', ['id' => $vehicle->id]);
    }

    public function test_customer_payment_cash_desk_and_running_ledger(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'opening_balance' => '75000.00',
            'current_balance' => '75000.00',
        ]);

        $paymentPayload = [
            'amount' => '25000.00',
            'payment_date' => today()->toDateString(),
            'payment_method' => CustomerPayment::METHOD_CASH,
            'notes' => 'Received cash at counter for vehicle fleet fuel',
        ];

        $response = $this->post(route('customers.payments.store', $customer), $paymentPayload);
        $response->assertRedirect(route('customers.show', $customer));

        $customer->refresh();
        $this->assertEquals('50000.00', $customer->current_balance);

        $this->assertDatabaseHas('customer_payments', [
            'customer_id' => $customer->id,
            'payment_method' => 'CASH',
            'amount' => '25000.00',
        ]);

        $this->assertDatabaseHas('customer_ledger', [
            'customer_id' => $customer->id,
            'credit' => '25000.00',
            'running_balance' => '50000.00',
        ]);

        $this->assertDatabaseHas('cash_entries', [
            'branch_id' => $this->branch->id,
            'type' => CashEntry::TYPE_CASH_IN,
            'category' => 'CUSTOMER_PAYMENT',
            'amount' => '25000.00',
        ]);
    }

    public function test_customer_ageing_report_fifo_buckets_and_whatsapp_link(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'phone' => '03007654321',
            'opening_balance' => '0.00',
            'current_balance' => '100000.00',
        ]);

        // Seed debits in different ageing buckets
        // 10 days ago (0-30 days) -> 30,000
        CustomerLedger::create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'date' => Carbon::today()->subDays(10)->toDateString(),
            'description' => 'Credit Fuel Dispense',
            'debit' => '30000.00',
            'credit' => '0.00',
            'running_balance' => '30000.00',
        ]);

        // 45 days ago (31-60 days) -> 40,000
        CustomerLedger::create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'date' => Carbon::today()->subDays(45)->toDateString(),
            'description' => 'Credit Fuel Dispense',
            'debit' => '40000.00',
            'credit' => '0.00',
            'running_balance' => '70000.00',
        ]);

        // 100 days ago (90+ days) -> 30,000
        CustomerLedger::create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'date' => Carbon::today()->subDays(100)->toDateString(),
            'description' => 'Credit Fuel Dispense',
            'debit' => '30000.00',
            'credit' => '0.00',
            'running_balance' => '100000.00',
        ]);

        $response = $this->get(route('customers.ageing'));
        $response->assertOk();
        $response->assertViewIs('customers.ageing');
        $response->assertSee('wa.me');
        $response->assertSee('923007654321');
    }

    public function test_customer_statement_and_pdf_download(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'opening_balance' => '15000.00',
            'current_balance' => '15000.00',
        ]);

        $statementResponse = $this->get(route('customers.statement', $customer));
        $statementResponse->assertOk();
        $statementResponse->assertViewIs('customers.statement');
        $statementResponse->assertSee($customer->name);

        $pdfResponse = $this->get(route('customers.statement.pdf', $customer));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_supplier_crud_and_ledger(): void
    {
        $payload = [
            'code' => 'SUP-VITAL-01',
            'name' => 'Vital Petroleum Lahore Depot',
            'contact_person' => 'Muhammad Bilal',
            'phone' => '042-35890123',
            'email' => 'orders@vitalpetroleum.com.pk',
            'address' => 'Machike Terminal, Sheikhupura',
            'ntn_number' => '4109876-5',
            'strn_number' => '03-05-2710-001-82',
            'opening_balance' => '500000.00',
            'status' => Supplier::STATUS_ACTIVE,
            'notes' => 'Primary OMC terminal supplier',
        ];

        $response = $this->post(route('suppliers.store'), $payload);
        $supplier = Supplier::where('code', 'SUP-VITAL-01')->first();
        $this->assertNotNull($supplier);

        $response->assertRedirect(route('suppliers.show', $supplier));
        $this->assertEquals('500000.00', $supplier->opening_balance);
        $this->assertEquals('500000.00', $supplier->current_balance);

        $bank = Bank::firstOrCreate(['name' => 'Habib Bank Limited'], ['short_name' => 'HBL', 'status' => 'ACTIVE']);
        $bankAccount = BankAccount::create([
            'branch_id' => $this->branch->id,
            'bank_id' => $bank->id,
            'bank_name' => 'Habib Bank Limited',
            'account_title' => 'Mehar Filling Station',
            'account_number' => '01234567890123',
            'iban' => 'PK36HABB0000123456789012',
            'opening_balance' => '1000000.00',
            'current_balance' => '1000000.00',
            'status' => 'ACTIVE',
        ]);

        $paymentPayload = [
            'amount' => '200000.00',
            'payment_date' => today()->toDateString(),
            'payment_method' => 'BANK_TRANSFER',
            'bank_account_id' => $bankAccount->id,
            'notes' => 'Online RTGS transfer for Bowsers',
        ];

        $paymentResponse = $this->post(route('suppliers.payments.store', $supplier), $paymentPayload);
        $paymentResponse->assertRedirect(route('suppliers.show', $supplier));

        $supplier->refresh();
        $this->assertEquals('300000.00', $supplier->current_balance);
        $this->assertDatabaseHas('supplier_ledger', [
            'supplier_id' => $supplier->id,
            'debit' => '200000.00',
            'running_balance' => '300000.00',
        ]);
    }

    public function test_fuel_purchase_with_tanker_challan_shortage_and_approval(): void
    {
        $supplier = Supplier::create([
            'branch_id' => $this->branch->id,
            'code' => 'SUP-PSO-01',
            'name' => 'Pakistan State Oil / Vital Terminal',
            'opening_balance' => '0.00',
            'current_balance' => '0.00',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        $fuelProduct = FuelProduct::factory()->create([
            'name' => 'Super Petrol 92 RON',
            'code' => 'PMG',
            'average_cost' => '250.00',
        ]);

        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuelProduct->id,
            'tank_number' => 'TK-01',
            'capacity' => '25000.000',
            'current_stock' => '5000.000',
        ]);

        // 1. Record Tanker Arrival with Shortage (Ordered 10,000 L, Decanted 9,920 L = 80 L Shortage)
        $purchasePayload = [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'tank_id' => $tank->id,
            'fuel_product_id' => $fuelProduct->id,
            'challan_number' => 'CH-890123',
            'invoice_number' => 'INV-OMC-991',
            'tanker_number' => 'TL-5511',
            'driver_name' => 'Ghulam Abbas',
            'volume_ordered' => '10000.000',
            'volume_received' => '9920.000',
            'dip_before' => '45.00',
            'dip_after' => '190.50',
            'purchase_rate' => '260.00',
            'ifem' => '1500.00',
            'petroleum_levy' => '60000.00',
            'freight_charges' => '15000.00',
            'tax_amount' => '0.00',
            'other_charges' => '500.00',
            'density' => '0.7450',
            'temperature' => '29.0',
            'notes' => 'Challan verified with dip measurement',
        ];

        $storeResponse = $this->post(route('purchases.store'), $purchasePayload);
        $purchase = Purchase::where('challan_number', 'CH-890123')->first();
        $this->assertNotNull($purchase);

        $storeResponse->assertRedirect(route('purchases.show', $purchase));

        // Verify Shortage
        $this->assertEquals('80.000', $purchase->shortage_litres);
        $this->assertEquals('20800.00', $purchase->shortage_amount); // 80 * 260
        $this->assertTrue($purchase->shortage_claimed);
        $this->assertEquals('PENDING', $purchase->shortage_claim_status);
        $this->assertEquals(Purchase::STATUS_RECEIVED, $purchase->status);

        // Verify Stock not yet modified before approval
        $tank->refresh();
        $this->assertEquals('5000.000', $tank->current_stock);

        // 2. Approve Decantation
        $approveResponse = $this->post(route('purchases.approve', $purchase));
        $approveResponse->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        $tank->refresh();
        $supplier->refresh();
        $fuelProduct->refresh();

        $this->assertEquals(Purchase::STATUS_APPROVED, $purchase->status);

        // Tank Stock: 5,000 + 9,920 = 14,920 L
        $this->assertEquals('14920.000', $tank->current_stock);

        // Weighted Average Cost:
        // StockBefore: 5000 L @ 250 = 1,250,000
        // Received: 9920 L @ 260 = 2,579,200
        // Total Value = 3,829,200 / 14920 L = 256.64879 -> 256.65
        $this->assertEquals('256.65', $fuelProduct->average_cost);

        // Supplier Ledger Credited
        $this->assertEquals($purchase->total_amount, $supplier->current_balance);
        $this->assertDatabaseHas('supplier_ledger', [
            'supplier_id' => $supplier->id,
            'credit' => $purchase->total_amount,
            'running_balance' => $purchase->total_amount,
        ]);

        // 3. Resolve Shortage Claim with Supplier Debit Note
        $claimPayload = [
            'status' => 'APPROVED',
            'notes' => 'Shortage accepted by transporter, debiting supplier account',
            'debit_supplier' => 1,
        ];

        $claimResponse = $this->post(route('purchases.shortage-claim', $purchase), $claimPayload);
        $claimResponse->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        $supplier->refresh();

        $this->assertEquals('APPROVED', $purchase->shortage_claim_status);
        // Supplier balance reduced by shortage amount (20,800)
        $expectedBalance = Money::subtract($purchase->total_amount, '20800.00');
        $this->assertEquals($expectedBalance, $supplier->current_balance);
    }

    public function test_purchase_exceeding_tank_capacity_fails_approval(): void
    {
        $supplier = Supplier::create([
            'branch_id' => $this->branch->id,
            'code' => 'SUP-TEST-02',
            'name' => 'Test Supplier',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        $fuel = FuelProduct::factory()->create();
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
            'capacity' => '10000.000',
            'current_stock' => '8000.000', // only 2000 L space left
        ]);

        // Try decanting 5,000 L into a tank with 2,000 L available
        $purchase = Purchase::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PUR-OVERFLOW-01',
            'challan_number' => 'CH-OVER-01',
            'purchase_date' => today()->toDateString(),
            'tank_id' => $tank->id,
            'fuel_product_id' => $fuel->id,
            'volume_ordered' => '5000.000',
            'volume_received' => '5000.000',
            'purchase_rate' => '250.00',
            'subtotal' => '1250000.00',
            'total_amount' => '1250000.00',
            'status' => Purchase::STATUS_RECEIVED,
        ]);

        $response = $this->post(route('purchases.approve', $purchase));
        $response->assertSessionHasErrors('tank_id');

        $tank->refresh();
        $this->assertEquals('8000.000', $tank->current_stock);
        $purchase->refresh();
        $this->assertEquals(Purchase::STATUS_RECEIVED, $purchase->status);
    }

    public function test_non_fuel_retail_products_eneos_filters_and_stock_movements(): void
    {
        // 1. Create ENEOS Lubricant
        $eneosPayload = [
            'code' => 'ENEOS-5W30-4L',
            'name' => 'ENEOS Sustina 5W-30 Fully Synthetic (4L)',
            'category' => Product::CATEGORY_LUBRICANT,
            'unit' => 'CAN',
            'cost_price' => '8200.00',
            'selling_price' => '9800.00',
            'initial_stock' => '20.000',
            'min_stock_level' => '5.000',
            'barcode' => '4984245100012',
            'status' => Product::STATUS_ACTIVE,
            'notes' => 'Premium engine oil',
        ];

        $createResponse = $this->post(route('products.store'), $eneosPayload);
        $product = Product::where('code', 'ENEOS-5W30-4L')->first();
        $this->assertNotNull($product);

        $createResponse->assertRedirect(route('products.show', $product));
        $this->assertEquals('20.000', $product->current_stock);
        $this->assertEquals('1600.00', $product->profitAmount());

        // 2. Stock In (Purchase additional 10 Cans)
        $stockInPayload = [
            'quantity' => '10.000',
            'unit_cost' => '8200.00',
            'notes' => 'Received shipment of 10 cans from distributor',
        ];

        $stockInResponse = $this->post(route('products.stock-in', $product), $stockInPayload);
        $stockInResponse->assertRedirect(route('products.show', $product));

        $product->refresh();
        $this->assertEquals('30.000', $product->current_stock);
        $this->assertDatabaseHas('product_stock_movements', [
            'product_id' => $product->id,
            'type' => ProductStockMovement::TYPE_PURCHASE,
            'quantity' => '10.000',
            'after_stock' => '30.000',
        ]);

        // 3. Manual Adjustment (Damage/Leakage - 2 Cans)
        $adjustmentPayload = [
            'type' => ProductStockMovement::TYPE_ADJUSTMENT_OUT,
            'quantity' => '2.000',
            'notes' => 'Damaged seal / can leaking in storage',
        ];

        $adjResponse = $this->post(route('products.adjust-stock', $product), $adjustmentPayload);
        $adjResponse->assertRedirect(route('products.show', $product));

        $product->refresh();
        $this->assertEquals('28.000', $product->current_stock);

        // 4. Products Index and Low Stock Query
        $indexResponse = $this->get(route('products.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('ENEOS Sustina');
    }

    public function test_tank_calibration_points_linear_interpolation_and_dip_variance(): void
    {
        $fuel = FuelProduct::factory()->create();
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
            'tank_number' => 'TK-CAL-01',
            'capacity' => '25000.000',
            'current_stock' => '10000.000',
        ]);

        // 1. Single Point Calibration Store
        $this->post(route('tanks.calibration.store', $tank), [
            'dip_cm' => '50.00',
            'litres' => '4000.000',
        ])->assertRedirect(route('tanks.calibration', $tank));

        // 2. Bulk Calibration Import
        $bulkData = "100.00, 10000.000\n150.00, 16000.000\n200.00, 22000.000";
        $this->post(route('tanks.calibration.bulk', $tank), [
            'data' => $bulkData,
        ])->assertRedirect(route('tanks.calibration', $tank));

        $this->assertEquals(4, $tank->dipCharts()->count());

        // 3. Linear Interpolation Test:
        // Point (100 cm, 10,000 L) and (150 cm, 16,000 L)
        // Midpoint at 125 cm should interpolate to 13,000 L
        $service = app(TankCalibrationService::class);
        $interpolated = $service->calculateLitresFromDip($tank, '125.00');
        $this->assertEquals('13000.000', $interpolated);

        // 4. Physical Dip Reading with Variance Analysis:
        // Expected Stock = 10,000 L.
        // Permissible loss (0.5%) = 50 L.
        // Case A: Dip = 99.8 cm -> approx 9980 L (Loss of 20 L) -> Within tolerance
        $evalA = $this->post(route('tanks.calibration.evaluate', $tank), [
            'physical_quantity' => '9970.000', // Loss of 30 L <= 50 L
            'notes' => 'Daily dip reading within 0.5% tolerance',
        ]);

        $evalA->assertRedirect(route('tank-readings.index'));
        $readingA = TankReading::where('tank_id', $tank->id)->latest('id')->first();
        $this->assertTrue($readingA->is_within_tolerance);
        $this->assertEquals('NORMAL_EVAPORATION', $readingA->variance_type);

        // Case B: Loss of 150 L (> 50 L allowable loss) -> Flagged as SHORTAGE
        $evalB = $this->post(route('tanks.calibration.evaluate', $tank), [
            'physical_quantity' => '9800.000', // Loss of 200 L > 50 L
            'notes' => 'Suspected tank leakage or dispenser calibration issue',
        ]);

        $readingB = TankReading::where('tank_id', $tank->id)->latest('id')->first();
        $this->assertFalse($readingB->is_within_tolerance);
        $this->assertEquals(TankReading::VARIANCE_SHORTAGE, $readingB->variance_type);
    }
}
