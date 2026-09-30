<?php

namespace Tests\Feature;

use App\Livewire\Settings\BillDesigner;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Dispenser;
use App\Models\FuelProduct;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSnapshot;
use App\Models\InvoiceTemplate;
use App\Models\Nozzle;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Tank;
use App\Models\User;
use App\Services\Sale\InvoiceService;
use App\Support\AmountInWords;
use App\Support\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class InvoicingTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Branch $branch;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\InvoiceTemplateSeeder::class);

        $this->branch = Branch::factory()->create([
            'name' => 'Main Forecourt',
            'code' => 'MFS001',
        ]);

        $this->admin = User::factory()->withRole(Role::ADMIN)->create([
            'name' => 'Admin Manager',
            'email' => 'admin@meharfilling.pk',
        ]);
        $this->admin->branches()->attach($this->branch->id, ['is_default' => true]);

        $this->invoiceService = app(InvoiceService::class);
    }

    /**
     * Test sequential gapless invoice numbering format: MFS-{YYYY}-{000000}.
     */
    public function test_sequential_gapless_invoice_numbering_format_mfs_yyyy_000000(): void
    {
        $year = now()->format('Y');

        $invNum1 = $this->invoiceService->generateInvoiceNumber();
        $this->assertMatchesRegularExpression("/^MFS-{$year}-\\d{6}$/", $invNum1);

        $invNum2 = $this->invoiceService->generateInvoiceNumber();
        $this->assertMatchesRegularExpression("/^MFS-{$year}-\\d{6}$/", $invNum2);

        // Sequence must increment
        $seq1 = (int) substr($invNum1, -6);
        $seq2 = (int) substr($invNum2, -6);
        $this->assertEquals($seq1 + 1, $seq2);
    }

    /**
     * Test invoice creation with items, tax calculations, Urdu words, and hash.
     */
    public function test_create_invoice_with_items_and_financial_calculations(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Malik Tariq Mehmood',
            'phone' => '0300-1234567',
        ]);

        $vehicle = CustomerVehicle::factory()->create([
            'customer_id' => $customer->id,
            'registration_number' => 'LES-2024-5555',
        ]);

        $fuel = FuelProduct::factory()->create([
            'name' => 'Super Petrol',
        ]);

        $items = [
            [
                'fuel_product_id' => $fuel->id,
                'item_description' => 'Super Petrol',
                'quantity' => '50.000',
                'unit_price' => '280.00',
                'total_amount' => '14000.00',
            ],
            [
                'item_description' => 'Engine Oil 20W50',
                'quantity' => '1.000',
                'unit_price' => '2500.00',
                'total_amount' => '2500.00',
            ],
        ];

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $this->admin->id,
            'payment_method' => 'cash',
            'paid_amount' => '16501.00',
            'previous_balance' => '0.00',
        ], $items);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'subtotal' => '16500.00',
            'pos_fee' => '1.00',
            'total_amount' => '16501.00',
            'paid_amount' => '16501.00',
            'balance_due' => '0.00',
            'status' => Invoice::STATUS_PAID,
        ]);

        $this->assertCount(2, $invoice->items);
        $this->assertNotEmpty($invoice->hash);
        $this->assertNotEmpty($invoice->amount_in_words_ur);
        $this->assertNotEmpty($invoice->amount_in_words_en);
        $this->assertStringContainsString('روپے صرف', $invoice->amount_in_words_ur);
    }

    /**
     * Test immutable snapshot creation and integrity verification.
     */
    public function test_immutable_snapshot_creation_and_integrity_verification(): void
    {
        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'payment_method' => 'cash',
        ], [
            [
                'item_description' => 'Super Petrol',
                'quantity' => '20.000',
                'unit_price' => '280.00',
                'total_amount' => '5600.00',
            ],
        ]);

        $snapshot = $invoice->snapshot;
        $this->assertNotNull($snapshot);
        $this->assertNotEmpty($snapshot->snapshot_hash);
        $this->assertTrue($snapshot->verifyIntegrity());

        // Test immutability: snapshot cannot be modified
        $this->expectException(RuntimeException::class);
        $snapshot->update(['station_snapshot' => ['tampered' => true]]);
    }

    /**
     * Test creating an invoice from a completed POS Sale.
     */
    public function test_create_invoice_from_pos_sale(): void
    {
        $customer = Customer::factory()->create(['name' => 'Chaudhry Bashir']);
        $fuel = FuelProduct::factory()->create(['name' => 'High Speed Diesel']);
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
        ]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);
        $nozzle = Nozzle::factory()->create([
            'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $fuel->id,
        ]);

        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'employee_id' => $this->admin->id,
            'invoice_number' => 'INV-2026-000099',
            'sale_date' => now(),
            'sale_mode' => Sale::MODE_LITRES,
            'subtotal' => '8700.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '8700.00',
            'total_litres' => '30.000',
            'total_cost' => '7500.00',
            'status' => Sale::STATUS_COMPLETED,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'tank_id' => $tank->id,
            'dispenser_id' => $dispenser->id,
            'nozzle_id' => $nozzle->id,
            'fuel_product_id' => $fuel->id,
            'litres' => '30.000',
            'rate' => '290.00',
            'amount' => '8700.00',
            'cost_rate' => '250.00',
            'cost_amount' => '7500.00',
            'meter_start' => '1000.000',
            'meter_end' => '1030.000',
        ]);

        SalePayment::create([
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'method' => 'cash',
            'amount' => '8700.00',
        ]);

        $invoice = $this->invoiceService->createFromSale($sale);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($sale->id, $invoice->sale_id);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertCount(1, $invoice->items);
        $this->assertNotNull($invoice->snapshot);
    }

    /**
     * Test invoices index screen accessible with permission.
     */
    public function test_invoices_index_screen_accessible_with_permission(): void
    {
        $this->actingAs($this->admin);

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
        ], [
            ['item_description' => 'Test Item', 'quantity' => '10.000', 'unit_price' => '100.00'],
        ]);

        $response = $this->get(route('invoices.index'));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('Customer Invoices');
    }

    /**
     * Test invoice show screen displays details, snapshot integrity, and actions.
     */
    public function test_invoices_show_screen_displays_details_and_verification_badge(): void
    {
        $this->actingAs($this->admin);

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
        ], [
            ['item_description' => 'Super Petrol', 'quantity' => '25.000', 'unit_price' => '280.00'],
        ]);

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('Print A4 Invoice');
        $response->assertSee('Thermal 80mm');
        $response->assertSee('Snapshot Integrity Verified');
    }

    /**
     * Test A4 Modern Red Band invoice view renders correctly with all elements.
     */
    public function test_a4_modern_red_band_invoice_view_renders_correctly(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::factory()->create(['name' => 'Sardar Muhammad']);
        $vehicle = CustomerVehicle::factory()->create([
            'customer_id' => $customer->id,
            'registration_number' => 'FDN-2023-1122',
        ]);

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $this->admin->id,
            'previous_balance' => '5000.00',
            'paid_amount' => '0.00', // Udhaar sale
        ], [
            ['item_description' => 'Super Petrol', 'quantity' => '40.000', 'unit_price' => '280.00'],
        ]);

        $response = $this->get(route('invoices.a4', $invoice));

        $response->assertOk();
        $response->assertSee('MEHAR FILLING STATION');
        $response->assertSee('مہر فلنگ اسٹیشن');
        $response->assertSee('VITAL PETROLEUM');
        $response->assertSee('Sardar Muhammad');
        $response->assertSee('FDN-2023-1122');
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('Customer Udhaar Balance', false);
        $response->assertSee('Customer Signature / دستخط کسٹمر');
    }

    /**
     * Test thermal receipt renders 80mm and 58mm formats.
     */
    public function test_thermal_receipt_renders_80mm_and_58mm_formats(): void
    {
        $this->actingAs($this->admin);

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
        ], [
            ['item_description' => 'Super Petrol', 'quantity' => '15.000', 'unit_price' => '280.00'],
        ]);

        // 80mm
        $res80 = $this->get(route('invoices.thermal', ['invoice' => $invoice, 'size' => '80mm']));
        $res80->assertOk();
        $res80->assertSee('MEHAR FILLING STATION');
        $res80->assertSee($invoice->invoice_number);

        // 58mm
        $res58 = $this->get(route('invoices.thermal', ['invoice' => $invoice, 'size' => '58mm']));
        $res58->assertOk();
        $res58->assertSee('MEHAR FILLING STATION');
    }

    /**
     * Test public QR verification route without login returns genuine invoice details.
     */
    public function test_public_qr_verification_endpoint_without_login(): void
    {
        // Unauthenticated
        $this->assertGuest();

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
        ], [
            ['item_description' => 'Super Petrol', 'quantity' => '30.000', 'unit_price' => '280.00'],
        ]);

        $response = $this->get(route('invoice.verify', $invoice->hash));

        $response->assertOk();
        $response->assertSee('OFFICIAL GENUINE TAX INVOICE VERIFIED');
        $response->assertSee('مہر فلنگ اسٹیشن کے ڈیجیٹل سسٹم سے تصدیق شدہ');
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('Mehar Filling Station');
    }

    /**
     * Test public QR verification with invalid hash returns 404 and unverified notice.
     */
    public function test_public_qr_verification_with_invalid_hash_returns_404(): void
    {
        $this->assertGuest();

        $response = $this->get(route('invoice.verify', 'invalid-fake-hash-99999'));

        $response->assertStatus(404);
        $response->assertSee('Verification Failed / غیر تصدیق شدہ');
    }

    /**
     * Test Bill Designer Livewire component loads, updates colors, and saves.
     */
    public function test_bill_designer_livewire_component_renders_and_updates_settings(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BillDesigner::class)
            ->assertSee('Bill Designer & Brand Identity', false)
            ->assertSee('Modern Red Band')
            ->set('primary_color', '#D71920')
            ->set('dark_red_color', '#A30F15')
            ->set('show_logo', true)
            ->set('show_urdu_name', true)
            ->set('show_signatures', true)
            ->call('save')
            ->assertSet('savedSuccess', true);

        $this->assertDatabaseHas('invoice_templates', [
            'slug' => 'modern_red_band',
            'primary_color' => '#D71920',
            'dark_red_color' => '#A30F15',
            'show_logo' => true,
        ]);
    }

    /**
     * Test Bill Designer palette switching and reset to defaults.
     */
    public function test_bill_designer_palette_switching_and_reset_to_defaults(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BillDesigner::class)
            ->call('applyPalette', 'corporate_navy')
            ->assertSet('primary_color', '#1E293B')
            ->assertSet('dark_red_color', '#0F172A')
            ->call('resetToDefaults')
            ->assertSet('primary_color', '#D71920')
            ->assertSet('dark_red_color', '#A30F15')
            ->assertSet('name', 'Modern Red Band');
    }

    /**
     * Test PDF generation endpoint returns application/pdf stream.
     */
    public function test_pdf_generation_endpoint(): void
    {
        $this->actingAs($this->admin);

        $invoice = $this->invoiceService->createInvoice([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
        ], [
            ['item_description' => 'Super Petrol', 'quantity' => '20.000', 'unit_price' => '280.00'],
        ]);

        $response = $this->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
