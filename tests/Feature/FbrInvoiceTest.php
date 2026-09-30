<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Dispenser;
use App\Models\FbrInvoice;
use App\Models\FuelProduct;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tank;
use App\Models\User;
use App\Services\Compliance\FbrInvoiceService;
use App\Services\Sale\SaleService;
use App\Services\Shift\ShiftService;
use App\Support\Fbr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FBR digital invoicing end to end: a completed sale becomes a fiscalised
 * invoice carrying the SRO 1006(I)/2021 fiscal number and QR payload.
 */
class FbrInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $attendant;
    private Branch $branch;
    private SaleService $sales;
    private int $nozzleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $this->branch = Branch::factory()->create(['code' => 'MFS-01']);
        $this->attendant = User::factory()->withRole(Role::ATTENDANT)->create();
        $this->attendant->branches()->attach($this->branch->id, ['is_default' => true]);

        $this->actingAs($this->attendant);
        $this->sales = app(SaleService::class);

        $fuel = FuelProduct::factory()->create(['selling_price' => '250.00', 'average_cost' => '200.00']);
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id, 'fuel_product_id' => $fuel->id,
            'capacity' => '50000.000', 'opening_stock' => '10000.000',
        ]);
        $tank->forceFill(['current_stock' => '10000.000'])->save();

        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);
        $nozzle = new \App\Models\Nozzle([
            'branch_id' => $this->branch->id, 'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id, 'fuel_product_id' => $fuel->id,
            'nozzle_number' => '1', 'opening_meter' => '1000.000',
            'status' => \App\Models\Nozzle::STATUS_ACTIVE,
        ]);
        $nozzle->save();
        $nozzle->forceFill(['current_meter' => '1000.000'])->save();
        $this->nozzleId = (int) $nozzle->id;

        app(ShiftService::class)->open(
            employee: $this->attendant, branch: $this->branch,
            openingCash: '5000.00', nozzleIds: [$nozzle->id],
        );
    }

    private function makeSale(string $litres = '25.125', ?int $customerId = null): Sale
    {
        $amount = \App\Support\Money::amountForLitres($litres, '250.00');

        return $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzleId => "LITRES:{$litres}"],
            payments: [['method' => 'CASH', 'amount' => $amount]],
            customerId: $customerId,
            shiftId: \App\Models\Shift::where('status', 'OPEN')->value('id'),
        );
    }

    public function test_a_sale_is_fiscalised_with_a_valid_fiscal_number(): void
    {
        $sale = $this->makeSale();

        $invoice = app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        $this->assertSame($sale->id, $invoice->sale_id);
        $this->assertTrue(
            Fbr::isValidFiscalNumber($invoice->fiscal_number),
            "[{$invoice->fiscal_number}] is not a valid SRO fiscal number."
        );

        // POS branch code is derived from the branch and is six characters.
        $this->assertSame(6, strlen($invoice->pos_branch_code));
        $this->assertStringStartsWith($invoice->pos_branch_code.'-', $invoice->fiscal_number);
    }

    public function test_the_qr_payload_carries_the_fiscal_number_and_total(): void
    {
        $sale = $this->makeSale();

        $invoice = app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        $this->assertSame($invoice->fiscal_number, $invoice->qr_payload['fbr_invoice_no']);
        $this->assertSame(
            \App\Support\Money::round($sale->total),
            $invoice->qr_payload['total_amount'],
        );
    }

    public function test_the_pos_service_fee_is_one_rupee(): void
    {
        $sale = $this->makeSale();

        $invoice = app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        $this->assertSame('1.00', $invoice->pos_service_fee, 'SRO 1006(I)/2021 mandates Rs.1 per invoice.');
    }

    public function test_buyer_details_are_not_required_for_a_small_cash_sale(): void
    {
        $sale = $this->makeSale('10.000');   // Rs.2,500

        $invoice = app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        $this->assertFalse($invoice->buyer_details_required);
        $this->assertNull($invoice->buyer_name);
    }

    public function test_buyer_details_are_required_above_one_hundred_thousand(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'ntn_number' => '1234567-8',
            'cnic' => '35202-1234567-8',
        ]);

        // 400 L x 250.00 = Rs.100,000.00 exactly; 401 L crosses the line.
        $crossing = $this->makeSale('401.000', $customer->id);

        $invoice = app(FbrInvoiceService::class)->fiscalise($crossing, $this->attendant->id);

        $this->assertSame('100250.00', $crossing->total);
        $this->assertTrue(
            $invoice->buyer_details_required,
            'Above Rs.100,000 the buyer CNIC/NTN is mandatory.'
        );
        $this->assertSame($customer->ntn_number, $invoice->buyer_ntn);
        $this->assertSame($customer->cnic, $invoice->buyer_cnic);
        $this->assertSame($customer->name, $invoice->buyer_name);
    }

    public function test_a_tax_liable_buyer_needs_details_even_on_a_small_invoice(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'ntn_number' => '7654321-0',
            'is_tax_liable' => true,
        ]);

        $sale = $this->makeSale('4.000', $customer->id);   // Rs.1,000

        $invoice = app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        $this->assertTrue(
            $invoice->buyer_details_required,
            'A tax-liable buyer always has to be identified.'
        );
        $this->assertSame('7654321-0', $invoice->buyer_ntn);
    }

    public function test_a_sale_is_never_fiscalised_twice(): void
    {
        $sale = $this->makeSale();
        $service = app(FbrInvoiceService::class);

        $first = $service->fiscalise($sale, $this->attendant->id);
        $second = $service->fiscalise($sale, $this->attendant->id);

        $this->assertSame($first->id, $second->id, 'A fiscal number is never re-issued.');
        $this->assertSame($first->fiscal_number, $second->fiscal_number);
        $this->assertDatabaseCount('fbr_invoices', 1);
    }

    public function test_two_sales_in_the_same_second_get_different_numbers(): void
    {
        $service = app(FbrInvoiceService::class);

        $a = $service->fiscalise($this->makeSale('10.000'), $this->attendant->id);
        $b = $service->fiscalise($this->makeSale('11.000'), $this->attendant->id);

        $this->assertNotSame($a->fiscal_number, $b->fiscal_number);
        $this->assertTrue(Fbr::isValidFiscalNumber($a->fiscal_number));
        $this->assertTrue(Fbr::isValidFiscalNumber($b->fiscal_number));
    }

    public function test_fiscal_numbers_are_unique_across_the_table(): void
    {
        $service = app(FbrInvoiceService::class);

        for ($i = 0; $i < 5; $i++) {
            $service->fiscalise($this->makeSale((string) (10 + $i)), $this->attendant->id);
        }

        $total = FbrInvoice::count();
        $distinct = FbrInvoice::distinct()->count('fiscal_number');

        $this->assertSame($total, $distinct, 'Every fiscal number must be unique.');
    }

    public function test_a_voided_sale_cannot_be_fiscalised(): void
    {
        $sale = $this->makeSale();
        $sale->update(['status' => Sale::STATUS_VOIDED]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);
    }

    public function test_fiscalisation_writes_an_audit_row(): void
    {
        $sale = $this->makeSale();

        app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'fbr_invoice_create',
            'module' => 'compliance',
        ]);
    }

    public function test_the_invoice_starts_as_a_draft_pending_a_licensed_integrator(): void
    {
        $sale = $this->makeSale();

        $invoice = app(FbrInvoiceService::class)->fiscalise($sale, $this->attendant->id);

        // Chapter XIV, Sales Tax Rules 2006: only an FBR-licensed integrator
        // may transmit. The app prepares the document, it does not submit it.
        $this->assertSame(FbrInvoice::STATUS_DRAFT, $invoice->status);
        $this->assertNull($invoice->submitted_at);
        $this->assertSame(0, $invoice->attempts);
    }
}
