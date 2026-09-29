<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Role;
use App\Models\Tank;
use App\Models\TankReading;
use App\Models\User;
use App\Services\Fuel\FuelPriceService;
use App\Services\Fuel\MeterService;
use App\Services\Fuel\NozzleService;
use App\Services\Fuel\TankService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 2: fuel master data.
 *
 * Spec "Done when": tests for fuel/tank/dispenser/nozzle creation,
 * nozzle-tank fuel mismatch rejection, and meter validation.
 */
class FuelMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = User::factory()->withRole(Role::ADMIN)->create();
        $this->branch = Branch::factory()->create();
    }

    // ---------------------------------------------------------------
    // Fuel products
    // ---------------------------------------------------------------

    public function test_fuel_product_can_be_created(): void
    {
        $this->actingAs($this->admin)
            ->post('/fuels', [
                'code' => 'PET',
                'name' => 'Petrol',
                'unit' => 'LITRE',
                'selling_price' => '249.99',
                'tax_rate' => '0',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('fuels.index'));

        $this->assertDatabaseHas('fuel_products', ['code' => 'PET', 'name' => 'Petrol']);
    }

    public function test_fuel_product_code_is_unique(): void
    {
        FuelProduct::factory()->create(['code' => 'PET']);

        $this->actingAs($this->admin)
            ->post('/fuels', ['code' => 'PET', 'name' => 'Copy', 'unit' => 'LITRE', 'status' => 'ACTIVE'])
            ->assertSessionHasErrors('code');
    }

    public function test_fuel_price_must_not_be_negative(): void
    {
        $this->actingAs($this->admin)
            ->post('/fuels', ['code' => 'BAD', 'name' => 'Bad', 'unit' => 'LITRE', 'selling_price' => '-5', 'status' => 'ACTIVE'])
            ->assertSessionHasErrors('selling_price');
    }

    public function test_any_fuel_can_be_added_by_an_admin_nothing_is_hardcoded(): void
    {
        // The spec is explicit: nothing about fuel is hardcoded.
        $this->actingAs($this->admin)->post('/fuels', [
            'code' => 'KERO', 'name' => 'Kerosene', 'unit' => 'LITRE', 'selling_price' => '180.00', 'status' => 'ACTIVE',
        ])->assertRedirect();

        $this->actingAs($this->admin)->post('/fuels', [
            'code' => 'HOG', 'name' => 'HOG Oil', 'unit' => 'LITRE', 'selling_price' => '210.00', 'status' => 'ACTIVE',
        ])->assertRedirect();

        $this->assertDatabaseHas('fuel_products', ['name' => 'Kerosene']);
        $this->assertDatabaseHas('fuel_products', ['name' => 'HOG Oil']);
    }

    // ---------------------------------------------------------------
    // Tanks
    // ---------------------------------------------------------------

    public function test_tank_can_be_created(): void
    {
        $fuel = FuelProduct::factory()->create();

        $this->actingAs($this->admin)
            ->post('/tanks', [
                'branch_id' => $this->branch->id,
                'fuel_product_id' => $fuel->id,
                'tank_number' => 'TK-01',
                'capacity' => '20000.000',
                'min_level' => '2000.000',
                'max_level' => '19000.000',
                'opening_stock' => '10000.000',
                'low_stock_threshold' => '3000.000',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('tanks.index'));

        $this->assertDatabaseHas('tanks', [
            'branch_id' => $this->branch->id,
            'tank_number' => 'TK-01',
            'capacity' => 20000.000,
        ]);
    }

    public function test_tank_number_is_unique_per_branch_but_may_repeat_across_branches(): void
    {
        $fuel = FuelProduct::factory()->create();
        $otherBranch = Branch::factory()->create();

        Tank::factory()->create(['branch_id' => $this->branch->id, 'tank_number' => 'TK-01']);

        $payload = fn ($branchId) => [
            'branch_id' => $branchId, 'fuel_product_id' => $fuel->id, 'tank_number' => 'TK-01',
            'capacity' => '20000.000', 'status' => 'ACTIVE',
        ];

        // Same branch -> rejected.
        $this->actingAs($this->admin)
            ->post('/tanks', $payload($this->branch->id))
            ->assertSessionHasErrors('tank_number');

        // Different branch -> allowed.
        $this->actingAs($this->admin)
            ->post('/tanks', $payload($otherBranch->id))
            ->assertRedirect(route('tanks.index'));
    }

    public function test_tank_level_validation(): void
    {
        $fuel = FuelProduct::factory()->create();
        $service = app(TankService::class);

        $base = [
            'capacity' => '10000.000',
            'min_level' => '1000.000',
            'max_level' => '9000.000',
            'opening_stock' => '5000.000',
        ];

        // min > max
        $this->expectException(ValidationException::class);
        $service->validateLevels(new Tank(array_merge($base, ['min_level' => '9500.000'])));
    }

    public function test_opening_stock_cannot_exceed_capacity(): void
    {
        $fuel = FuelProduct::factory()->create();
        $service = app(TankService::class);

        $this->expectException(ValidationException::class);

        $service->validateLevels(new Tank([
            'capacity' => '10000.000',
            'min_level' => '1000.000',
            'max_level' => '9000.000',
            'opening_stock' => '12000.000',
        ]));
    }

    // ---------------------------------------------------------------
    // Dispensers & nozzles
    // ---------------------------------------------------------------

    public function test_dispenser_can_be_created(): void
    {
        $this->actingAs($this->admin)
            ->post('/dispensers', [
                'branch_id' => $this->branch->id,
                'dispenser_number' => 'DP-01',
                'name' => 'Front Pump 1',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('dispensers.index'));

        $this->assertDatabaseHas('dispensers', ['dispenser_number' => 'DP-01']);
    }

    public function test_nozzle_can_be_created(): void
    {
        $fuel = FuelProduct::factory()->create();
        $tank = Tank::factory()->create(['branch_id' => $this->branch->id, 'fuel_product_id' => $fuel->id]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->post('/nozzles', [
                'branch_id' => $this->branch->id,
                'dispenser_id' => $dispenser->id,
                'tank_id' => $tank->id,
                'fuel_product_id' => $fuel->id,
                'nozzle_number' => '1',
                'opening_meter' => '1000.000',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('nozzles.index'));

        $nozzle = Nozzle::where('nozzle_number', '1')->first();

        $this->assertNotNull($nozzle);
        // A nozzle starts at its opening meter.
        $this->assertSame('1000.000', $nozzle->current_meter);
    }

    /**
     * The rule the spec calls out explicitly: a nozzle's fuel must equal its
     * tank's fuel.
     */
    public function test_nozzle_fuel_must_match_tank_fuel(): void
    {
        $petrol = FuelProduct::factory()->create(['name' => 'Petrol']);
        $diesel = FuelProduct::factory()->create(['name' => 'Diesel']);
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $petrol->id,
        ]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->post('/nozzles', [
                'branch_id' => $this->branch->id,
                'dispenser_id' => $dispenser->id,
                'tank_id' => $tank->id,
                'fuel_product_id' => $diesel->id, // <- mismatch
                'nozzle_number' => '9',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('fuel_product_id');

        $this->assertDatabaseMissing('nozzles', ['nozzle_number' => '9']);
    }

    public function test_nozzle_service_rejects_a_fuel_mismatch_directly(): void
    {
        $petrol = FuelProduct::factory()->create(['name' => 'Petrol']);
        $diesel = FuelProduct::factory()->create(['name' => 'Diesel']);
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id, 'fuel_product_id' => $petrol->id,
        ]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        $this->expectException(ValidationException::class);

        app(NozzleService::class)->create(
            branch: $this->branch,
            dispenser: $dispenser,
            tank: $tank,
            fuelProductId: $diesel->id,
            nozzleNumber: '1',
        );
    }

    public function test_nozzle_cannot_use_a_tank_from_another_branch(): void
    {
        $fuel = FuelProduct::factory()->create();
        $otherBranch = Branch::factory()->create();
        $tank = Tank::factory()->create(['branch_id' => $otherBranch->id, 'fuel_product_id' => $fuel->id]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->post('/nozzles', [
                'branch_id' => $this->branch->id,
                'dispenser_id' => $dispenser->id,
                'tank_id' => $tank->id,
                'fuel_product_id' => $fuel->id,
                'nozzle_number' => '3',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('tank_id');
    }

    public function test_nozzle_number_is_unique_per_dispenser(): void
    {
        $fuel = FuelProduct::factory()->create();
        $tank = Tank::factory()->create(['branch_id' => $this->branch->id, 'fuel_product_id' => $fuel->id]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        Nozzle::factory()->create([
            'dispenser_id' => $dispenser->id, 'tank_id' => $tank->id,
            'fuel_product_id' => $fuel->id, 'branch_id' => $this->branch->id, 'nozzle_number' => '1',
        ]);

        $this->actingAs($this->admin)
            ->post('/nozzles', [
                'branch_id' => $this->branch->id,
                'dispenser_id' => $dispenser->id,
                'tank_id' => $tank->id,
                'fuel_product_id' => $fuel->id,
                'nozzle_number' => '1',
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors('nozzle_number');
    }

    // ---------------------------------------------------------------
    // Meter logic
    // ---------------------------------------------------------------

    public function test_meter_advances_by_the_litres_dispensed(): void
    {
        $nozzle = Nozzle::factory()->withMeter('1000.000')->create();
        $service = app(MeterService::class);

        $result = $service->advance($nozzle, '25.125');

        $this->assertSame('1000.000', $result['previous']);
        $this->assertSame('1025.125', $result['current']);
        $this->assertSame('1025.125', $nozzle->fresh()->current_meter);

        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $nozzle->id,
            'type' => MeterReading::TYPE_SALE,
            'previous_meter' => '1000.000',
            'current_meter' => '1025.125',
            'quantity' => '25.125',
        ]);
    }

    /**
     * The spec's exact wording, used verbatim.
     */
    public function test_meter_cannot_move_backwards(): void
    {
        $nozzle = Nozzle::factory()->withMeter('1000.000')->create();
        $service = app(MeterService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(MeterService::ERROR_LOWER_METER);

        $service->assertNotLower('900.000', '1000.000');
    }

    public function test_lower_meter_message_matches_the_spec_wording(): void
    {
        $this->assertSame(
            'Closing meter reading cannot be lower than opening meter reading.',
            MeterService::ERROR_LOWER_METER
        );
    }

    public function test_assert_not_lower_rejects_a_backwards_closing_reading(): void
    {
        $service = app(MeterService::class);

        $this->expectException(ValidationException::class);

        $service->assertNotLower('500.000', '1000.000');
    }

    public function test_assert_not_lower_accepts_an_equal_or_higher_reading(): void
    {
        $service = app(MeterService::class);

        $service->assertNotLower('1000.000', '1000.000');
        $service->assertNotLower('1000.001', '1000.000');

        $this->assertTrue(true, 'No exception thrown for valid readings.');
    }

    public function test_meter_correction_requires_a_reason(): void
    {
        $nozzle = Nozzle::factory()->withMeter('1000.000')->create();

        $this->expectException(ValidationException::class);

        app(MeterService::class)->correct($nozzle, '0.000', '   ');
    }

    public function test_meter_correction_moves_the_meter_and_leaves_an_audit_trail(): void
    {
        $nozzle = Nozzle::factory()->withMeter('1000.000')->create();

        $this->actingAs($this->admin);

        app(MeterService::class)->correct($nozzle, '0.000', 'Meter rollover at 999999');

        $this->assertSame('0.000', $nozzle->fresh()->current_meter);

        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $nozzle->id,
            'type' => MeterReading::TYPE_CORRECTION,
            'previous_meter' => '1000.000',
            'current_meter' => '0.000',
            'reason' => 'Meter rollover at 999999',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meter_correction',
            'reference_type' => Nozzle::class,
            'reference_id' => $nozzle->id,
        ]);
    }

    public function test_meter_correction_is_refused_without_permission(): void
    {
        $nozzle = Nozzle::factory()->withMeter('1000.000')->create();
        $viewer = User::factory()->withRole(Role::VIEWER)->create();

        $this->actingAs($viewer);

        $this->expectException(ValidationException::class);

        app(MeterService::class)->correct($nozzle, '0.000', 'Trying to reset', $viewer->id);
    }

    public function test_meter_correction_is_allowed_for_a_manager(): void
    {
        $nozzle = Nozzle::factory()->withMeter('1000.000')->create();
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $this->actingAs($manager);

        app(MeterService::class)->correct($nozzle, '500.000', 'Faulty meter reset', $manager->id);

        $this->assertSame('500.000', $nozzle->fresh()->current_meter);
    }

    // ---------------------------------------------------------------
    // Fuel prices
    // ---------------------------------------------------------------

    public function test_changing_price_creates_history_and_closes_the_old_window(): void
    {
        $fuel = FuelProduct::factory()->priced('250.00')->create();
        $service = app(FuelPriceService::class);

        $service->changePrice($fuel, '260.00', null, $this->admin->id, 'Government increase');

        $this->assertDatabaseCount('fuel_prices', 1);
        $this->assertDatabaseHas('fuel_prices', [
            'fuel_product_id' => $fuel->id,
            'price' => '260.00',
            'reason' => 'Government increase',
        ]);

        $this->assertSame('260.00', $fuel->fresh()->selling_price);
        $this->assertDatabaseHas('audit_logs', ['action' => 'price_change']);
    }

    public function test_second_price_change_closes_the_first_window(): void
    {
        $fuel = FuelProduct::factory()->priced('250.00')->create();
        $service = app(FuelPriceService::class);

        $service->changePrice($fuel, '260.00', null, $this->admin->id, 'first');
        $service->changePrice($fuel, '270.00', null, $this->admin->id, 'second');

        $prices = FuelPrice::where('fuel_product_id', $fuel->id)->orderBy('effective_from')->get();

        $this->assertCount(2, $prices);
        $this->assertNotNull($prices[0]->effective_to, 'The first window must be closed, not left open.');
        $this->assertNull($prices[1]->effective_to, 'The current price window stays open.');
    }

    public function test_price_change_rejects_a_negative_price(): void
    {
        $fuel = FuelProduct::factory()->priced('250.00')->create();

        $this->expectException(ValidationException::class);

        app(FuelPriceService::class)->changePrice($fuel, '-1.00', null, $this->admin->id);
    }

    public function test_price_change_is_refused_without_the_price_change_permission(): void
    {
        $fuel = FuelProduct::factory()->priced('250.00')->create();
        $cashier = User::factory()->withRole(Role::CASHIER)->create();

        $this->actingAs($cashier)
            ->post('/fuel-prices', [
                'fuel_product_id' => $fuel->id,
                'selling_price' => '999.00',
                'reason' => 'greed',
            ])
            ->assertForbidden();
    }

    public function test_sale_rate_is_resolved_server_side_not_from_the_browser(): void
    {
        $fuel = FuelProduct::factory()->priced('250.00')->create();
        $service = app(FuelPriceService::class);

        $this->assertSame('250.00', $service->rateFor($fuel, $this->branch->id));
    }

    public function test_a_fuel_with_no_price_cannot_be_sold(): void
    {
        $fuel = FuelProduct::factory()->priced('0.00')->create();

        $this->expectException(ValidationException::class);

        app(FuelPriceService::class)->rateFor($fuel, $this->branch->id);
    }

    // ---------------------------------------------------------------
    // Tank readings
    // ---------------------------------------------------------------

    public function test_tank_reading_records_variance(): void
    {
        $tank = Tank::factory()->withStock('1000.000')->create();
        $service = app(TankService::class);

        $reading = $service->recordReading($tank, '950.000', 'Dip measured');

        $this->assertSame('1000.000', $reading->expected_quantity);
        $this->assertSame('950.000', $reading->physical_quantity);
        $this->assertSame('-50.000', $reading->variance_quantity);
        $this->assertSame(TankReading::VARIANCE_SHORTAGE, $reading->variance_type);
    }

    public function test_tank_reading_detects_a_surplus(): void
    {
        $tank = Tank::factory()->withStock('1000.000')->create();

        $reading = app(TankService::class)->recordReading($tank, '1020.000');

        $this->assertSame('20.000', $reading->variance_quantity);
        $this->assertSame(TankReading::VARIANCE_SURPLUS, $reading->variance_type);
    }

    public function test_tank_reading_detects_an_exact_match(): void
    {
        $tank = Tank::factory()->withStock('1000.000')->create();

        $reading = app(TankService::class)->recordReading($tank, '1000.000');

        $this->assertSame('0.000', $reading->variance_quantity);
        $this->assertSame(TankReading::VARIANCE_MATCH, $reading->variance_type);
    }

    public function test_tank_reading_cannot_exceed_capacity(): void
    {
        $tank = Tank::factory()->withStock('1000.000')->create(['capacity' => '2000.000']);

        $this->expectException(ValidationException::class);

        app(TankService::class)->recordReading($tank, '3000.000');
    }

    // ---------------------------------------------------------------
    // Data type guarantees
    // ---------------------------------------------------------------

    public function test_decimal_columns_use_the_specified_precision(): void
    {
        $schema = \Illuminate\Support\Facades\Schema::getColumnListing('tanks');

        $this->assertContains('current_stock', $schema);

        // Money DECIMAL(14,2), litres DECIMAL(12,3), meters DECIMAL(14,3).
        $fuel = FuelProduct::factory()->priced('249.99')->create();
        $tank = Tank::factory()->withStock('12345.678')->create(['capacity' => '20000.000']);
        $nozzle = Nozzle::factory()->withMeter('1234567.891')->create();

        $this->assertSame('249.99', $fuel->fresh()->selling_price);
        $this->assertSame('12345.678', $tank->fresh()->current_stock);
        $this->assertSame('1234567.891', $nozzle->fresh()->current_meter);
    }
}
