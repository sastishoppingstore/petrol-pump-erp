<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Customer;
use App\Models\InvoiceTemplate;
use App\Models\InvoiceSnapshot;
use App\Models\DigitalSignature;
use App\Services\Invoicing\InvoiceDesignerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDesignerServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceDesignerService $service;
    private Sale $sale;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoiceDesignerService::class);

        // Create test branch
        $this->branch = Branch::factory()->create([
            'name' => 'Main Branch',
            'code' => 'BRN-001',
        ]);

        // Create test fuel
        $fuel = FuelProduct::factory()->create([
            'name' => 'Petrol',
            'code' => 'PTL',
        ]);

        // Create test customer
        $customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'branch_id' => $this->branch->id,
        ]);

        // Create test sale
        $this->sale = Sale::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2024-000001',
            'subtotal' => 5000,
            'discount' => 100,
            'tax' => 500,
            'total' => 5400,
            'status' => 'COMPLETED',
        ]);
    }

    /**
     * Test default templates initialization
     */
    public function test_initialize_default_templates()
    {
        // Clear existing templates
        InvoiceTemplate::truncate();

        $this->service->initializeDefaultTemplates();

        $this->assertDatabaseCount('invoice_templates', 3);
        $this->assertDatabaseHas('invoice_templates', [
            'layout_type' => 'MODERN_RED_BAND',
        ]);
        $this->assertDatabaseHas('invoice_templates', [
            'layout_type' => 'CLASSIC',
        ]);
        $this->assertDatabaseHas('invoice_templates', [
            'layout_type' => 'MINIMAL',
        ]);
    }

    /**
     * Test snapshot generation
     */
    public function test_generate_snapshot()
    {
        $this->service->initializeDefaultTemplates();

        $snapshot = $this->service->generateSnapshot(
            $this->sale,
            InvoiceDesignerService::LAYOUT_MODERN_RED_BAND
        );

        $this->assertNotNull($snapshot->id);
        $this->assertEquals($this->sale->id, $snapshot->invoice_id);
        $this->assertNotNull($snapshot->invoice_data);
        $this->assertNotNull($snapshot->design_snapshot);

        $invoiceData = json_decode($snapshot->invoice_data, true);
        $this->assertEquals('INV-2024-000001', $invoiceData['invoice_number']);
        $this->assertArrayHasKey('seller', $invoiceData);
        $this->assertArrayHasKey('buyer', $invoiceData);
    }

    /**
     * Test snapshot contains all required sections
     */
    public function test_snapshot_contains_all_sections()
    {
        $this->service->initializeDefaultTemplates();

        $snapshot = $this->service->generateSnapshot(
            $this->sale,
            InvoiceDesignerService::LAYOUT_MODERN_RED_BAND
        );

        $invoiceData = json_decode($snapshot->invoice_data, true);

        $this->assertArrayHasKey('invoice_number', $invoiceData);
        $this->assertArrayHasKey('invoice_date', $invoiceData);
        $this->assertArrayHasKey('seller', $invoiceData);
        $this->assertArrayHasKey('buyer', $invoiceData);
        $this->assertArrayHasKey('items', $invoiceData);
        $this->assertArrayHasKey('totals', $invoiceData);
        $this->assertArrayHasKey('payment', $invoiceData);
    }

    /**
     * Test all three layout types
     */
    public function test_all_layout_types()
    {
        $this->service->initializeDefaultTemplates();

        $layouts = [
            InvoiceDesignerService::LAYOUT_MODERN_RED_BAND,
            InvoiceDesignerService::LAYOUT_CLASSIC,
            InvoiceDesignerService::LAYOUT_MINIMAL,
        ];

        foreach ($layouts as $layout) {
            $snapshot = $this->service->generateSnapshot($this->sale, $layout);

            $this->assertNotNull($snapshot->id);
            $designSnapshot = json_decode($snapshot->design_snapshot, true);
            $this->assertEquals($layout, $designSnapshot['layout']);
        }
    }

    /**
     * Test digital signature addition
     */
    public function test_add_digital_signature()
    {
        $signatureSvg = '<svg></svg>';
        $signerName = 'John Doe';
        $signerPhone = '03001234567';

        $signature = $this->service->addSignature(
            $this->sale,
            $signatureSvg,
            $signerName,
            $signerPhone
        );

        $this->assertNotNull($signature->id);
        $this->assertEquals($this->sale->id, $signature->sale_id);
        $this->assertEquals($signerName, $signature->signer_name);
        $this->assertEquals($signerPhone, $signature->signer_phone);
        $this->assertDatabaseHas('digital_signatures', [
            'sale_id' => $this->sale->id,
            'signer_name' => $signerName,
        ]);
    }

    /**
     * Test get available layouts
     */
    public function test_get_available_layouts()
    {
        $layouts = $this->service->getAvailableLayouts();

        $this->assertArrayHasKey('MODERN_RED_BAND', $layouts);
        $this->assertArrayHasKey('CLASSIC', $layouts);
        $this->assertArrayHasKey('MINIMAL', $layouts);
        $this->assertCount(3, $layouts);
    }

    /**
     * Test get available paper sizes
     */
    public function test_get_available_paper_sizes()
    {
        $sizes = $this->service->getAvailablePaperSizes();

        $this->assertArrayHasKey('A4', $sizes);
        $this->assertArrayHasKey('A5', $sizes);
        $this->assertArrayHasKey('THERMAL_80MM', $sizes);
        $this->assertArrayHasKey('THERMAL_58MM', $sizes);
        $this->assertCount(4, $sizes);
    }

    /**
     * Test get snapshot
     */
    public function test_get_snapshot()
    {
        $this->service->initializeDefaultTemplates();
        $generated = $this->service->generateSnapshot($this->sale);

        $retrieved = $this->service->getSnapshot($this->sale);

        $this->assertNotNull($retrieved);
        $this->assertEquals($generated->id, $retrieved->id);
    }

    /**
     * Test regenerate snapshot
     */
    public function test_regenerate_snapshot()
    {
        $this->service->initializeDefaultTemplates();

        $first = $this->service->generateSnapshot(
            $this->sale,
            InvoiceDesignerService::LAYOUT_MODERN_RED_BAND
        );

        $second = $this->service->regenerateSnapshot(
            $this->sale,
            InvoiceDesignerService::LAYOUT_CLASSIC
        );

        $this->assertNotEquals($first->id, $second->id);
        $designSnapshot = json_decode($second->design_snapshot, true);
        $this->assertEquals('CLASSIC', $designSnapshot['layout']);
    }

    /**
     * Test totals are calculated correctly
     */
    public function test_totals_calculation()
    {
        $this->service->initializeDefaultTemplates();

        $snapshot = $this->service->generateSnapshot($this->sale);
        $invoiceData = json_decode($snapshot->invoice_data, true);

        $this->assertEquals(5000, $invoiceData['totals']['subtotal']);
        $this->assertEquals(100, $invoiceData['totals']['discount']);
        $this->assertEquals(4900, $invoiceData['totals']['subtotal_after_discount']);
        $this->assertEquals(5400, $invoiceData['totals']['total']);
    }

    /**
     * Test FBR data is included when enabled
     */
    public function test_fbr_data_included()
    {
        $this->service->initializeDefaultTemplates();

        // Set FBR enabled
        \App\Models\Setting::query()->updateOrCreate(
            ['key' => 'fbr_invoicing_enabled'],
            ['value' => true]
        );

        $snapshot = $this->service->generateSnapshot($this->sale);
        $invoiceData = json_decode($snapshot->invoice_data, true);

        $this->assertNotNull($invoiceData['fbr']);
        $this->assertArrayHasKey('qr_code_base64', $invoiceData['fbr']);
        $this->assertArrayHasKey('qr_payload', $invoiceData['fbr']);
    }

    /**
     * Test export PDF
     */
    public function test_export_pdf()
    {
        $filename = $this->service->exportPdf(
            $this->sale,
            InvoiceDesignerService::LAYOUT_MODERN_RED_BAND
        );

        $this->assertStringContainsString('INV-2024-000001', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    /**
     * Test signature validation
     */
    public function test_signature_is_valid()
    {
        $signature = $this->service->addSignature(
            $this->sale,
            '<svg></svg>',
            'John Doe'
        );

        $this->assertTrue($signature->isValid());
    }
}
