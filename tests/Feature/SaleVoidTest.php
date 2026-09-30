<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\FuelProduct;
use App\Models\MeterReading;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tank;
use App\Models\User;
use App\Services\Fuel\MeterService;
use App\Services\Sale\SaleService;
use App\Services\Sale\SaleVoidService;
use App\Services\Shift\ShiftService;
use App\Services\Stock\StockService;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 5 close-out: void and refund.
 *
 * The rule under test: a sale is never deleted. Voiding reverses stock,
 * meter and cash so the ledger still reconciles afterwards.
 */
class SaleVoidTest extends TestCase
{
    use RefreshDatabase;

    private User $attendant;
    private User $manager;
    private Branch $branch;
    private Tank $tank;
    private \App\Models\Nozzle $nozzle;
    private \App\Models\Shift $shift;
    private SaleService $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $this->attendant = User::factory()->withRole(Role::ATTENDANT)->create();
        $this->manager = User::factory()->withRole(Role::MANAGER)->create();

        foreach ([$this->attendant, $this->manager] as $u) {
            $u->branches()->attach($this->branch->id, ['is_default' => true]);
        }

        $this->actingAs($this->attendant);

        $fuel = FuelProduct::factory()->create(['selling_price' => '250.00', 'average_cost' => '200.00']);
        $this->tank = Tank::factory()->create([
            'branch_id' => $this->branch->id, 'fuel_product_id' => $fuel->id,
            'capacity' => '50000.000', 'opening_stock' => '10000.000',
        ]);
        $this->tank->forceFill(['current_stock' => '10000.000'])->save();

        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);
        $this->nozzle = new \App\Models\Nozzle([
            'branch_id' => $this->branch->id, 'dispenser_id' => $dispenser->id,
            'tank_id' => $this->tank->id, 'fuel_product_id' => $fuel->id,
            'nozzle_number' => '1', 'opening_meter' => '1000.000',
            'status' => \App\Models\Nozzle::STATUS_ACTIVE,
        ]);
        $this->nozzle->save();
        $this->nozzle->forceFill(['current_meter' => '1000.000'])->save();

        $this->shift = app(ShiftService::class)->open(
            employee: $this->attendant, branch: $this->branch,
            openingCash: '5000.00', nozzleIds: [$this->nozzle->id],
        );

        $this->sales = app(SaleService::class);
    }

    private function makeSale(string $litres = '25.125'): Sale
    {
        $amount = \App\Support\Money::amountForLitres($litres, '250.00');

        return $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => "LITRES:{$litres}"],
            payments: [['method' => 'CASH', 'amount' => $amount]],
            shiftId: $this->shift->id,
        );
    }

    public function test_void_returns_the_litres_to_the_tank(): void
    {
        $sale = $this->makeSale();

        $this->assertSame('9974.875', $this->tank->fresh()->current_stock);

        app(SaleVoidService::class)->void($sale, 'Wrong nozzle selected', $this->manager->id);

        $this->assertSame(
            '10000.000',
            $this->tank->fresh()->current_stock,
            'The tank must be back to its pre-sale quantity.'
        );

        // A reversing CORRECTION movement, not a deleted SALE row.
        $this->assertDatabaseHas('tank_movements', [
            'tank_id' => $this->tank->id,
            'type' => StockService::TYPE_CORRECTION,
            'quantity' => '25.125',
            'after_quantity' => '10000.000',
        ]);

        // The original SALE movement is still there, untouched.
        $this->assertDatabaseHas('tank_movements', [
            'tank_id' => $this->tank->id,
            'type' => StockService::TYPE_SALE,
            'quantity' => '-25.125',
        ]);
    }

    public function test_void_rolls_the_meter_back(): void
    {
        $sale = $this->makeSale();

        $this->assertSame('1025.125', $this->nozzle->fresh()->current_meter);

        app(SaleVoidService::class)->void($sale, 'Customer cancelled', $this->manager->id);

        $this->assertSame(
            '1000.000',
            $this->nozzle->fresh()->current_meter,
            'The meter must return to its value at the start of the sale.'
        );

        // The correction is a recorded CORRECTION row, not a silent overwrite.
        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $this->nozzle->id,
            'type' => MeterReading::TYPE_CORRECTION,
            'previous_meter' => '1025.125',
            'current_meter' => '1000.000',
        ]);
    }

    public function test_void_marks_the_sale_but_never_deletes_it(): void
    {
        $sale = $this->makeSale();
        $invoice = $sale->invoice_number;

        app(SaleVoidService::class)->void($sale, 'Wrong nozzle', $this->manager->id);

        $fresh = $sale->fresh();

        $this->assertSame(Sale::STATUS_VOIDED, $fresh->status);
        $this->assertSame('Wrong nozzle', $fresh->void_reason);
        $this->assertSame($this->manager->id, $fresh->voided_by);
        $this->assertNotNull($fresh->voided_at);

        // Still present, with its items and payments intact.
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'invoice_number' => $invoice]);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('sale_payments', 1);
    }

    public function test_void_writes_an_audit_row(): void
    {
        $sale = $this->makeSale();

        app(SaleVoidService::class)->void($sale, 'Fraudulent duplicate', $this->manager->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sale_void',
            'module' => 'sales',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_void_reverses_the_cash_in_the_shift(): void
    {
        $sale = $this->makeSale();

        app(SaleVoidService::class)->void($sale, 'Customer cancelled', $this->manager->id);

        $this->assertDatabaseHas('shift_cash', [
            'shift_id' => $this->shift->id,
            'entry_type' => 'SALE_VOID',
            'amount' => '6281.25',
        ]);

        // Expected cash must fall back to the opening float.
        $expected = app(ShiftService::class)->summary($this->shift)['expected_cash'];
        $this->assertSame('5000.00', $expected, 'A void must not leave phantom cash in the till.');
    }

    public function test_refund_marks_the_sale_as_refunded(): void
    {
        $sale = $this->makeSale();

        $result = app(SaleVoidService::class)->void(
            $sale, 'Customer returned goods', $this->manager->id, asRefund: true
        );

        $this->assertSame(Sale::STATUS_REFUNDED, $result->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sale_refund']);
        $this->assertSame('10000.000', $this->tank->fresh()->current_stock);
    }

    public function test_void_requires_a_reason(): void
    {
        $sale = $this->makeSale();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/reason is required/i');

        app(SaleVoidService::class)->void($sale, '   ', $this->manager->id);
    }

    public function test_void_without_permission_is_refused(): void
    {
        $sale = $this->makeSale();

        // A manager has void permission; a plain attendant does not, so use a
        // user holding no financial permissions at all.
        $stranger = User::factory()->withRole(Role::VIEWER)->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/permission/i');

        app(SaleVoidService::class)->void($sale, 'Trying it on', $stranger->id);
    }

    public function test_a_sale_cannot_be_voided_twice(): void
    {
        $sale = $this->makeSale();
        $service = app(SaleVoidService::class);

        $service->void($sale, 'First attempt', $this->manager->id);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/already VOIDED/');

        $service->void($sale->fresh(), 'Second attempt', $this->manager->id);
    }

    public function test_failed_void_changes_nothing(): void
    {
        $sale = $this->makeSale();
        $stockBefore = $this->tank->fresh()->current_stock;
        $meterBefore = $this->nozzle->fresh()->current_meter;

        try {
            // An empty reason is rejected before anything is written.
            app(SaleVoidService::class)->void($sale, '', $this->manager->id);
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame($stockBefore, $this->tank->fresh()->current_stock);
        $this->assertSame($meterBefore, $this->nozzle->fresh()->current_meter);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
    }

    public function test_ledger_stays_balanced_after_a_void(): void
    {
        $this->makeSale();

        app(SaleVoidService::class)->void(
            $this->makeSale(), 'Second sale also voided', $this->manager->id
        );

        // Tank quantity and the movement ledger must still agree.
        $this->assertSame(
            '0.000',
            app(StockService::class)->ledgerDrift($this->tank->fresh()),
            'The stock ledger must reconcile after a void.'
        );
    }

    public function test_void_is_reachable_only_with_permission(): void
    {
        $sale = $this->makeSale();

        $viewer = User::factory()->withRole(Role::VIEWER)->create();
        $viewer->branches()->attach($this->branch->id, ['is_default' => true]);

        $this->actingAs($viewer)
            ->get("/sales/{$sale->id}/void")
            ->assertForbidden();
    }
}
