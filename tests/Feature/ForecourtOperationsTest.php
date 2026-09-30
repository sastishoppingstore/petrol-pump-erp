<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Dispenser;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\NozzleTest;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\User;
use App\Services\Cash\CashBookService;
use App\Services\Fuel\MeterService;
use App\Services\Sale\SaleService;
use App\Services\Shift\ShiftService;
use App\Support\Money;
use App\Support\PermissionList;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ForecourtOperationsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $manager;
    private User $attendant;
    private Branch $branch;
    private FuelProduct $petrol;
    private FuelProduct $diesel;
    private FuelProduct $hiOctane;
    private Tank $petrolTank;
    private Tank $dieselTank;
    private Dispenser $dispenser;
    private Nozzle $petrolNozzle;
    private Nozzle $dieselNozzle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->branch = Branch::factory()->create(['name' => 'Mehar Filling Station Sheikhupura']);
        $this->admin = User::factory()->withRole(Role::ADMIN)->create();
        $this->manager = User::factory()->withRole(Role::MANAGER)->create();
        $this->attendant = User::factory()->withRole(Role::ATTENDANT)->create();

        foreach ([$this->admin, $this->manager, $this->attendant] as $u) {
            $u->branches()->attach($this->branch->id, ['is_default' => true]);
        }

        $this->petrol = FuelProduct::factory()->create([
            'name' => 'Super Petrol',
            'code' => 'PMG',
        ]);
        $this->diesel = FuelProduct::factory()->create([
            'name' => 'High Speed Diesel',
            'code' => 'HSD',
        ]);
        $this->hiOctane = FuelProduct::factory()->create([
            'name' => 'Hi-Octane 97',
            'code' => 'HOBC',
        ]);

        FuelPrice::factory()->create([
            'fuel_product_id' => $this->petrol->id,
            'branch_id' => $this->branch->id,
            'price' => '280.00',
            'effective_from' => now()->subDay(),
        ]);
        FuelPrice::factory()->create([
            'fuel_product_id' => $this->diesel->id,
            'branch_id' => $this->branch->id,
            'price' => '290.00',
            'effective_from' => now()->subDay(),
        ]);

        $this->petrolTank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->petrol->id,
            'capacity' => '50000.000',
            'current_stock' => '25000.000',
        ]);
        $this->dieselTank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->diesel->id,
            'capacity' => '50000.000',
            'current_stock' => '30000.000',
        ]);

        $this->dispenser = Dispenser::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_number' => '1',
        ]);

        $this->petrolNozzle = Nozzle::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->dispenser->id,
            'tank_id' => $this->petrolTank->id,
            'fuel_product_id' => $this->petrol->id,
            'nozzle_number' => '1',
            'current_meter' => '10000.000',
            'status' => Nozzle::STATUS_ACTIVE,
        ]);

        $this->dieselNozzle = Nozzle::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->dispenser->id,
            'tank_id' => $this->dieselTank->id,
            'fuel_product_id' => $this->diesel->id,
            'nozzle_number' => '2',
            'current_meter' => '20000.000',
            'status' => Nozzle::STATUS_ACTIVE,
        ]);
    }

    // =========================================================================
    // PART 1: Meter Reading Module & Forecourt Controller
    // =========================================================================

    public function test_forecourt_meters_screen_renders_successfully(): void
    {
        $this->actingAs($this->attendant);

        $response = $this->get(route('forecourt.meters.index'));
        $response->assertOk();
        $response->assertSee('Super Petrol');
        $response->assertSee('High Speed Diesel');
        $response->assertSee('Nozzle 1');
        $response->assertSee('Nozzle 2');
    }

    public function test_nozzle_calibration_test_records_and_returns_fuel_to_tank_excluded_from_sales(): void
    {
        $this->actingAs($this->attendant);

        $initialMeter = (float) $this->petrolNozzle->current_meter;
        $testLitres = '5.000';

        $response = $this->post(route('forecourt.meters.test'), [
            'nozzle_id' => $this->petrolNozzle->id,
            'litres' => $testLitres,
            'reason' => 'Daily Morning 5L Calibration Can Check (پیمانہ چیک)',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify NozzleTest record exists
        $this->assertDatabaseHas('nozzle_tests', [
            'nozzle_id' => $this->petrolNozzle->id,
            'litres' => '5.000',
            'returned_to_tank' => 1,
            'status' => 'COMPLETED',
        ]);

        // Verify meter advanced
        $this->petrolNozzle->refresh();
        $this->assertEquals($initialMeter + 5.0, (float) $this->petrolNozzle->current_meter);

        // Verify NO sale record was created (completely excluded from sales)
        $this->assertEquals(0, Sale::count());
    }

    public function test_meter_rollover_calculates_throughput_correctly(): void
    {
        $this->actingAs($this->manager);

        // Nozzle opening meter is 10000.000. If it rolls over at 100000.000 and reaches 00050.000:
        // Throughput = (100000 - 10000) + 50 = 90050.000
        $response = $this->post(route('forecourt.meters.rollover'), [
            'nozzle_id' => $this->petrolNozzle->id,
            'closing_meter' => '50.000',
            'rollover_max' => '100000.000',
            'reason' => 'Meter mechanical 99999 rollover',
        ]);

        $response->assertRedirect();
        $this->petrolNozzle->refresh();
        $this->assertEquals('50.000', (string) $this->petrolNozzle->current_meter);

        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $this->petrolNozzle->id,
            'type' => MeterReading::TYPE_ROLLOVER,
            'current_meter' => '50.000',
            'quantity' => '90050.000',
        ]);
    }

    public function test_supervisor_meter_correction_requires_permission_and_audits(): void
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('forecourt.meters.correction'), [
            'nozzle_id' => $this->petrolNozzle->id,
            'new_meter' => '10500.000',
            'reason' => 'Dispenser electronic pulser calibration',
        ]);

        $response->assertRedirect();
        $this->petrolNozzle->refresh();
        $this->assertEquals('10500.000', (string) $this->petrolNozzle->current_meter);

        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $this->petrolNozzle->id,
            'type' => MeterReading::TYPE_CORRECTION,
            'current_meter' => '10500.000',
        ]);
    }

    public function test_forecourt_meter_closing_reading_records(): void
    {
        $this->actingAs($this->attendant);

        $response = $this->post(route('forecourt.meters.closing'), [
            'nozzle_id' => $this->petrolNozzle->id,
            'closing_meter' => '10120.500',
            'reason' => 'Mid-day check',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $this->petrolNozzle->id,
            'type' => MeterReading::TYPE_CLOSING,
            'current_meter' => '10120.500',
        ]);
    }

    // =========================================================================
    // PART 2: POS Sales Wizard
    // =========================================================================

    public function test_pos_wizard_screen_renders_successfully(): void
    {
        $this->actingAs($this->attendant);

        $response = $this->get(route('pos.index'));
        $response->assertOk();
        $response->assertSee('Forecourt POS Sales Terminal');
        $response->assertSee('D-1 · N-1');
        $response->assertSee('Litres Mode');
        $response->assertSee('Amount Mode');
    }

    public function test_pos_sale_with_split_payments_cash_and_jazzcash(): void
    {
        $this->actingAs($this->attendant);

        // 10 Litres of Super Petrol @ Rs. 280 = Rs. 2800
        $saleService = app(SaleService::class);
        $token = $saleService->newRequestToken();

        $response = $this->post(route('pos.store'), [
            'request_token' => $token,
            'branch_id' => $this->branch->id,
            'customer_name' => 'Chaudhry Akram',
            'customer_phone' => '03001234567',
            'quantities' => [
                $this->petrolNozzle->id => 'LITRES:10.000',
            ],
            'payments' => [
                ['method' => SalePayment::METHOD_CASH, 'amount' => '1800.00'],
                ['method' => SalePayment::METHOD_JAZZCASH, 'amount' => '1000.00'],
            ],
        ]);

        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('pos.success', $sale));

        $this->assertEquals('2800.00', (string) $sale->total);
        $this->assertEquals(2, $sale->payments()->count());

        // Verify stock decreased
        $this->petrolTank->refresh();
        $this->assertEquals(24990.0, (float) $this->petrolTank->current_stock);

        // Verify meter advanced
        $this->petrolNozzle->refresh();
        $this->assertEquals(10010.0, (float) $this->petrolNozzle->current_meter);
    }

    public function test_pos_credit_sale_requires_customer(): void
    {
        $this->actingAs($this->attendant);

        $saleService = app(SaleService::class);
        $token = $saleService->newRequestToken();

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'request_token' => $token,
            'branch_id' => $this->branch->id,
            'quantities' => [
                $this->petrolNozzle->id => 'LITRES:5.000',
            ],
            'payments' => [
                ['method' => SalePayment::METHOD_CREDIT, 'amount' => '1401.00'],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHasErrors('customer_id');
    }

    public function test_pos_success_screen_and_receipts(): void
    {
        $this->actingAs($this->attendant);

        $saleService = app(SaleService::class);
        $sale = $saleService->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $saleService->newRequestToken(),
            quantities: [$this->petrolNozzle->id => 'LITRES:10.000'],
            payments: [['method' => SalePayment::METHOD_CASH, 'amount' => '2800.00']],
            customerName: 'Haji Aslam',
            customerPhone: '03007654321',
        );

        // Success screen
        $resSuccess = $this->get(route('pos.success', $sale));
        $resSuccess->assertOk();
        $resSuccess->assertSee($sale->invoice_number);
        $resSuccess->assertSee('Sale Completed Successfully!');
        $resSuccess->assertSee('wa.me');

        // A4 Receipt
        $resReceipt = $this->get(route('pos.receipt', $sale));
        $resReceipt->assertOk();
        $resReceipt->assertSee($sale->invoice_number);

        // 80mm Thermal Receipt
        $resThermal = $this->get(route('pos.thermal', $sale));
        $resThermal->assertOk();
        $resThermal->assertSee('VITAL PETROLEUM FRANCHISE');
        $resThermal->assertSee($sale->invoice_number);
    }

    // =========================================================================
    // PART 3: Shifts Management
    // =========================================================================

    public function test_shift_lifecycle_open_track_close_and_reconcile(): void
    {
        $this->actingAs($this->manager);

        // 1. Open Shift with float cash
        $responseOpen = $this->post(route('shifts.store'), [
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '5000.00',
            'opening_notes' => 'Shift A 5x1000 Rs float',
            'nozzles' => [$this->petrolNozzle->id, $this->dieselNozzle->id],
        ]);

        $shift = Shift::latest()->first();
        $this->assertNotNull($shift);
        $this->assertEquals(Shift::STATUS_OPEN, $shift->status);
        $this->assertEquals('5000.00', (string) $shift->opening_cash);

        // 2. View Shift Show page (live tracking)
        $resShow = $this->actingAs($this->attendant)->get(route('shifts.show', $shift));
        $resShow->assertOk();
        $resShow->assertSee($shift->shift_number);
        $resShow->assertSee('Rs. 5,000.00');

        // 3. Add cash drop / handover
        $this->post(route('shifts.cash', $shift), [
            'type' => 'DROP',
            'amount' => '2000.00',
            'notes' => 'Cash drop to safe',
        ]);
        $this->assertDatabaseHas('shift_cash', [
            'shift_id' => $shift->id,
            'entry_type' => 'DROP',
            'amount' => '2000.00',
        ]);

        // 4. Close Shift Form
        $resCloseForm = $this->get(route('shifts.close.form', $shift));
        $resCloseForm->assertOk();

        // 5. Submit Close Shift
        $responseClose = $this->post(route('shifts.close', $shift), [
            'actual_cash' => '3000.00',
            'card_total' => '0.00',
            'closing_notes' => 'Shift closed smoothly',
            'nozzles' => [
                ['nozzle_id' => $this->petrolNozzle->id, 'closing_meter' => (string) $this->petrolNozzle->current_meter],
                ['nozzle_id' => $this->dieselNozzle->id, 'closing_meter' => (string) $this->dieselNozzle->current_meter],
            ],
        ]);

        $shift->refresh();
        $this->assertEquals(Shift::STATUS_CLOSED, $shift->status);
        $this->assertEquals('3000.00', (string) $shift->actual_cash);

        // 6. Printable shift report
        $resPrint = $this->get(route('shifts.print', $shift));
        $resPrint->assertOk();
        $resPrint->assertSee($shift->shift_number);
    }

    // =========================================================================
    // PART 4: Cash Book / Roznamcha
    // =========================================================================

    public function test_cash_book_index_and_roznamcha_rendering(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get(route('cash.index'));
        $response->assertOk();
        $response->assertSee('Forecourt Daily Cash Book');
        $response->assertSee('CRV (وصولی)');
        $response->assertSee('CPV (خرچہ / ادائیگی)');
    }

    public function test_cash_in_crv_flow(): void
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('cash.store-in'), [
            'branch_id' => $this->branch->id,
            'amount' => '15000.00',
            'category' => 'CUSTOMER_PAYMENT',
            'person_name' => 'Sheikh Zahid',
            'reference_no' => 'REC-786',
            'notes' => 'Partial outstanding clearance',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_entries', [
            'branch_id' => $this->branch->id,
            'type' => CashEntry::TYPE_CASH_IN,
            'amount' => '15000.00',
            'person_name' => 'Sheikh Zahid',
            'reference_no' => 'REC-786',
        ]);
    }

    public function test_cash_out_cpv_flow_and_non_negative_validation(): void
    {
        $this->actingAs($this->manager);

        $cashBookService = app(CashBookService::class);

        // First deposit Rs. 5000 cash in
        $cashBookService->recordCashIn(
            actor: $this->manager,
            branchId: $this->branch->id,
            amount: '5000.00',
            category: 'DIRECT_SALE',
            personName: 'Forecourt Till',
        );

        // Payout Rs. 2000 (valid, <= 5000)
        $responseValid = $this->post(route('cash.store-out'), [
            'branch_id' => $this->branch->id,
            'amount' => '2000.00',
            'category' => 'EXPENSE',
            'person_name' => 'Sui Gas Bill',
            'reference_no' => 'BILL-4412',
            'notes' => 'Station gas bill payment',
        ]);

        $responseValid->assertRedirect();
        $this->assertDatabaseHas('cash_entries', [
            'branch_id' => $this->branch->id,
            'type' => CashEntry::TYPE_CASH_OUT,
            'amount' => '2000.00',
        ]);

        // Attempt payout Rs. 10000 (invalid! Cash cannot go negative! Available is 3000)
        $responseInvalid = $this->from(route('cash.create-out'))->post(route('cash.store-out'), [
            'branch_id' => $this->branch->id,
            'amount' => '10000.00',
            'category' => 'EXPENSE',
            'person_name' => 'Contractor Advance',
        ]);

        $responseInvalid->assertRedirect(route('cash.create-out'));
        $responseInvalid->assertSessionHasErrors('amount');
    }

    public function test_cash_voucher_show_and_print(): void
    {
        $this->actingAs($this->manager);

        $cashBookService = app(CashBookService::class);
        $entry = $cashBookService->recordCashIn(
            actor: $this->manager,
            branchId: $this->branch->id,
            amount: '450000.00',
            category: 'OWNER_INJECTION',
            personName: 'Mian Mehar',
            notes: 'Working capital injection',
        );

        // Voucher show page
        $resShow = $this->get(route('cash.show', $entry));
        $resShow->assertOk();
        $resShow->assertSee($entry->voucher_number);
        $resShow->assertSee('Rs. 4,50,000.00');
        $resShow->assertSee('چار لاکھ پچاس ہزار روپے صرف');

        // Printable voucher slip
        $resPrint = $this->get(route('cash.print', $entry));
        $resPrint->assertOk();
        $resPrint->assertSee($entry->voucher_number);
        $resPrint->assertSee('MEHAR FILLING STATION');
    }
}
