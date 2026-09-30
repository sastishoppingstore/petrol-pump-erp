<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Dispenser;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Models\Nozzle;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\TankMovement;
use App\Models\User;
use App\Services\Fuel\FuelPriceService;
use App\Services\Sale\SaleService;
use App\Services\Shift\ShiftService;
use App\Services\Stock\StockService;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 5: the sale transaction.
 *
 * Spec "Done when": tests for litres<->amount maths, rounding, meter increase,
 * stock decrease, invoice uniqueness, double-submit, insufficient stock,
 * credit limit, and void reversal.
 */
class SaleTest extends TestCase
{
    use RefreshDatabase;

    private User $attendant;
    private Branch $branch;
    private Shift $shift;
    private Nozzle $nozzle;
    private FuelProduct $fuel;
    private SaleService $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $this->attendant = User::factory()->withRole(Role::ATTENDANT)->create();
        $this->attendant->branches()->attach($this->branch->id, ['is_default' => true]);
        $this->actingAs($this->attendant);

        // Fuel at 250.00 with a known 200.00 cost, so margin is testable.
        $this->fuel = FuelProduct::factory()->create([
            'selling_price' => '250.00',
            'average_cost' => '200.00',
        ]);

        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->fuel->id,
            'capacity' => '50000.000',
            'opening_stock' => '10000.000',
        ]);
        $tank->forceFill(['current_stock' => '10000.000'])->save();

        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        $this->nozzle = new Nozzle([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $this->fuel->id,
            'nozzle_number' => '1',
            'opening_meter' => '1000.000',
            'status' => Nozzle::STATUS_ACTIVE,
        ]);
        $this->nozzle->save();
        $this->nozzle->forceFill(['current_meter' => '1000.000'])->save();

        $this->shift = app(ShiftService::class)->open(
            employee: $this->attendant,
            branch: $this->branch,
            openingCash: '5000.00',
            nozzleIds: [$this->nozzle->id],
        );

        $this->sales = app(SaleService::class);
    }

    private function sell(array $quantities, array $payments = [], ?User $actor = null): Sale
    {
        $actor ??= $this->attendant;

        if ($payments === []) {
            // Default: pay the computed total in cash.
            $total = '0.00';
            foreach ($quantities as $spec) {
                [$mode, $value] = str_contains($spec, ':')
                    ? explode(':', $spec, 2)
                    : ['LITRES', $spec];
                [$l, $a] = $this->sales->compute($mode, $value, '250.00');
                $total = Money::add($total, $a);
            }
            $payments = [['method' => 'CASH', 'amount' => $total]];
        }

        return $this->sales->create(
            actor: $actor,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: $quantities,
            payments: $payments,
            shiftId: $this->shift->id,
        );
    }

    // ---------------------------------------------------------------
    // Litres / amount maths
    // ---------------------------------------------------------------

    public function test_litres_mode_computes_amount_from_server_side_rate(): void
    {
        $sale = $this->sell([(string) $this->nozzle->id => 'LITRES:25.125']);

        // 25.125 x 250.00 = 6281.25
        $this->assertSame('6281.25', $sale->total);
        $this->assertSame('25.125', $sale->total_litres);
        $this->assertSame('250.00', $sale->items->first()->rate);
    }

    public function test_amount_mode_back_calculates_litres_and_keeps_the_entered_amount(): void
    {
        $sale = $this->sell([(string) $this->nozzle->id => 'AMOUNT:5000.00']);

        // 5000 / 250 = 20.000 litres, and the customer paid exactly 5000.00
        $this->assertSame('5000.00', $sale->total);
        $this->assertSame('20.000', $sale->total_litres);
    }

    public function test_amount_mode_with_a_rate_that_does_not_divide_evenly(): void
    {
        // 100.00 / 3.00 = 33.333 litres, but the amount stays 100.00
        app(FuelPriceService::class)->changePrice($this->fuel, '3.00', null, $this->attendant->id);

        $sale = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'AMOUNT:100.00'],
            payments: [['method' => 'CASH', 'amount' => '100.00']],
            shiftId: $this->shift->id,
        );

        $this->assertSame('33.333', $sale->total_litres);
        $this->assertSame('100.00', $sale->total, 'The entered amount is what the customer pays.');
    }

    public function test_rate_is_taken_from_the_server_not_the_browser(): void
    {
        // A crafted POST tries to supply its own rate; it must be ignored.
        $sale = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CASH', 'amount' => '2500.00']],
            shiftId: $this->shift->id,
        );

        $this->assertSame('250.00', $sale->items->first()->rate, 'Rate must come from the price record.');
        $this->assertSame('2500.00', $sale->total);
    }

    public function test_cost_rate_and_gross_margin_are_recorded(): void
    {
        $sale = $this->sell([(string) $this->nozzle->id => 'LITRES:10.000']);

        $item = $sale->items->first();

        $this->assertSame('200.00', $item->cost_rate, 'Historical cost, never recalculated.');
        // COGS = 10 x 200 = 2000; Margin = 2500 - 2000 = 500
        $this->assertSame('2000.00', $sale->total_cost);
        $this->assertSame('500.00', $sale->grossMargin());
    }

    // ---------------------------------------------------------------
    // Meters and stock
    // ---------------------------------------------------------------

    public function test_sale_increases_the_meter_by_the_litres_dispensed(): void
    {
        $sale = $this->sell([(string) $this->nozzle->id => 'LITRES:25.125']);

        $item = $sale->items->first();

        $this->assertSame('1000.000', $item->meter_start);
        $this->assertSame('1025.125', $item->meter_end);
        $this->assertSame('1025.125', $this->nozzle->fresh()->current_meter);
    }

    public function test_sale_decreases_stock_and_writes_a_movement(): void
    {
        $this->sell([(string) $this->nozzle->id => 'LITRES:25.125']);

        $tank = Tank::first();

        $this->assertSame('9974.875', $tank->current_stock, '10000 - 25.125');

        $this->assertDatabaseHas('tank_movements', [
            'tank_id' => $tank->id,
            'type' => StockService::TYPE_SALE,
            'quantity' => '-25.125',
            'before_quantity' => '10000.000',
            'after_quantity' => '9974.875',
        ]);
    }

    public function test_sale_writes_a_meter_reading_row(): void
    {
        $this->sell([(string) $this->nozzle->id => 'LITRES:25.125']);

        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $this->nozzle->id,
            'shift_id' => $this->shift->id,
            'type' => 'SALE',
            'previous_meter' => '1000.000',
            'current_meter' => '1025.125',
        ]);
    }

    public function test_sale_with_insufficient_stock_is_rejected_and_rolls_back(): void
    {
        $tank = Tank::first();
        $tank->forceFill(['current_stock' => '10.000'])->save();

        try {
            $this->sell([(string) $this->nozzle->id => 'LITRES:25.125']);
            $this->fail('A sale larger than the tank stock must be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        // Nothing may be left behind.
        $this->assertSame('10.000', $tank->fresh()->current_stock, 'Stock unchanged.');
        $this->assertSame('1000.000', $this->nozzle->fresh()->current_meter, 'Meter unchanged.');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('sale_payments', 0);
        $this->assertDatabaseCount('tank_movements', 0);
    }

    // ---------------------------------------------------------------
    // Payments
    // ---------------------------------------------------------------

    public function test_split_payments_are_allowed(): void
    {
        $sale = $this->sell(
            [(string) $this->nozzle->id => 'LITRES:20.000'],   // 5000.00
            [
                ['method' => 'CASH', 'amount' => '3000.00'],
                ['method' => 'CARD', 'amount' => '1500.00'],
                ['method' => 'WALLET', 'amount' => '500.00'],
            ]
        );

        $this->assertSame('5000.00', $sale->total);
        $this->assertCount(3, $sale->payments);
    }

    public function test_payments_that_do_not_match_the_total_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/must match exactly/');

        $this->sell(
            [(string) $this->nozzle->id => 'LITRES:20.000'],   // 5000.00
            [['method' => 'CASH', 'amount' => '4000.00']]
        );
    }

    public function test_discount_reduces_the_total_and_the_payment(): void
    {
        $sale = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'LITRES:20.000'],
            payments: [['method' => 'CASH', 'amount' => '4500.00']],
            discount: '500.00',
            shiftId: $this->shift->id,
        );

        $this->assertSame('5000.00', $sale->subtotal);
        $this->assertSame('500.00', $sale->discount);
        $this->assertSame('4500.00', $sale->total);
    }

    // ---------------------------------------------------------------
    // Idempotency
    // ---------------------------------------------------------------

    public function test_a_replayed_request_token_does_not_create_a_second_sale(): void
    {
        $token = $this->sales->newRequestToken();

        $first = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $token,
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CASH', 'amount' => '2500.00']],
            shiftId: $this->shift->id,
        );

        // The double click replays the same token.
        $second = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $token,
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CASH', 'amount' => '2500.00']],
            shiftId: $this->shift->id,
        );

        $this->assertSame($first->id, $second->id, 'The replay must return the original sale.');
        $this->assertSame($first->invoice_number, $second->invoice_number);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertSame('1010.000', $this->nozzle->fresh()->current_meter, 'The meter advanced once, not twice.');
        $this->assertSame('9990.000', Tank::first()->current_stock, 'Stock fell once, not twice.');
    }

    public function test_a_failed_request_token_can_be_retried(): void
    {
        $tank = Tank::first();
        $tank->forceFill(['current_stock' => '1.000'])->save();

        $token = $this->sales->newRequestToken();

        try {
            $this->sales->create(
                actor: $this->attendant,
                branchId: $this->branch->id,
                requestToken: $token,
                quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
                payments: [['method' => 'CASH', 'amount' => '2500.00']],
                shiftId: $this->shift->id,
            );
        } catch (ValidationException) {
            // expected
        }

        // Restock and retry with the same token: the previous attempt failed,
        // so this must be allowed through.
        $tank->forceFill(['current_stock' => '10000.000'])->save();

        $sale = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $token,
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CASH', 'amount' => '2500.00']],
            shiftId: $this->shift->id,
        );

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertDatabaseCount('sales', 1);
    }

    // ---------------------------------------------------------------
    // Invoice numbers
    // ---------------------------------------------------------------

    public function test_invoice_numbers_are_unique_and_formatted(): void
    {
        $a = $this->sell([(string) $this->nozzle->id => 'LITRES:1.000']);
        $b = $this->sell([(string) $this->nozzle->id => 'LITRES:1.000']);
        $c = $this->sell([(string) $this->nozzle->id => 'LITRES:1.000']);

        $this->assertMatchesRegularExpression(
            '/^INV-' . now()->format('Y') . '-\d{6}$/',
            $a->invoice_number
        );

        $this->assertSame(
            3,
            Sale::distinct()->count('invoice_number'),
            'Every invoice number must be unique.'
        );
    }

    // ---------------------------------------------------------------
    // Shift and permission rules
    // ---------------------------------------------------------------

    public function test_sale_requires_an_open_shift(): void
    {
        $this->shift->update(['status' => Shift::STATUS_CLOSED]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/not open/');

        $this->sell([(string) $this->nozzle->id => 'LITRES:10.000']);
    }

    public function test_sale_requires_the_nozzle_to_be_on_the_shift(): void
    {
        $otherNozzle = Nozzle::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->nozzle->dispenser_id,
            'tank_id' => $this->nozzle->tank_id,
            'fuel_product_id' => $this->fuel->id,
            'nozzle_number' => '9',
        ]);
        $otherNozzle->forceFill(['current_meter' => '0.000'])->save();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/not assigned to your shift/');

        $this->sell([(string) $otherNozzle->id => 'LITRES:5.000']);
    }

    public function test_user_without_sales_create_permission_is_refused(): void
    {
        $viewer = User::factory()->withRole(Role::VIEWER)->create();
        $viewer->branches()->attach($this->branch->id, ['is_default' => true]);
        $this->actingAs($viewer);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->sales->create(
            actor: $viewer,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CASH', 'amount' => '2500.00']],
            shiftId: $this->shift->id,
        );
    }

    public function test_sale_at_an_unassigned_branch_is_refused(): void
    {
        $otherBranch = Branch::factory()->create();

        $this->expectException(ValidationException::class);

        $this->sales->create(
            actor: $this->attendant,
            branchId: $otherBranch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CASH', 'amount' => '2500.00']],
            shiftId: $this->shift->id,
        );
    }

    // ---------------------------------------------------------------
    // Credit limit
    // ---------------------------------------------------------------

    public function test_credit_sale_debits_the_customer_and_respects_the_limit(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'credit_limit' => '3000.00',
            'opening_balance' => '0.00',
        ]);

        $sale = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CREDIT', 'amount' => '2500.00']],
            customerId: $customer->id,
            shiftId: $this->shift->id,
        );

        $this->assertSame('2500.00', $sale->total);
        $this->assertSame('2500.00', $customer->fresh()->outstandingBalance());
    }

    public function test_credit_sale_over_the_limit_is_rejected(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'credit_limit' => '1000.00',
        ]);

        try {
            $this->sales->create(
                actor: $this->attendant,
                branchId: $this->branch->id,
                requestToken: $this->sales->newRequestToken(),
                quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
                payments: [['method' => 'CREDIT', 'amount' => '2500.00']],
                customerId: $customer->id,
                shiftId: $this->shift->id,
            );
            $this->fail('A sale beyond the credit limit must be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('credit limit', $e->getMessage());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('10000.000', Tank::first()->current_stock, 'Rollback must restore stock.');
    }

    public function test_customer_with_unlimited_credit_is_allowed_beyond_a_zero_limit(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'credit_limit' => '0.00',   // unlimited
        ]);

        $sale = $this->sales->create(
            actor: $this->attendant,
            branchId: $this->branch->id,
            requestToken: $this->sales->newRequestToken(),
            quantities: [(string) $this->nozzle->id => 'LITRES:10.000'],
            payments: [['method' => 'CREDIT', 'amount' => '2500.00']],
            customerId: $customer->id,
            shiftId: $this->shift->id,
        );

        $this->assertSame('2500.00', $sale->total);
    }

    // ---------------------------------------------------------------
    // Multiple nozzles
    // ---------------------------------------------------------------

    public function test_a_single_sale_can_span_several_nozzles(): void
    {
        $second = Nozzle::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->nozzle->dispenser_id,
            'tank_id' => $this->nozzle->tank_id,
            'fuel_product_id' => $this->fuel->id,
            'nozzle_number' => '2',
        ]);
        $second->forceFill(['current_meter' => '5000.000'])->save();

        $shift = $this->shift;
        $shift->nozzles()->create([
            'nozzle_id' => $second->id,
            'opening_meter' => '5000.000',
        ]);

        $sale = $this->sell([
            (string) $this->nozzle->id => 'LITRES:10.000',   // 2500.00
            (string) $second->id => 'LITRES:5.000',          // 1250.00
        ]);

        $this->assertSame('3750.00', $sale->total);
        $this->assertSame('15.000', $sale->total_litres);
        $this->assertCount(2, $sale->items);
    }
}
