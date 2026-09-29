<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\FuelProduct;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\User;
use App\Services\Shift\ShiftClosingService;
use App\Services\Shift\ShiftService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 4: shifts.
 *
 * Spec "Done when": tests for the one-open-shift rule, expected cash,
 * variance threshold, and unauthorized close.
 */
class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $attendant;
    private User $manager;
    private Branch $branch;
    private Nozzle $nozzleA;
    private Nozzle $nozzleB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $this->manager = User::factory()->withRole(Role::MANAGER)->create();
        $this->attendant = User::factory()->withRole(Role::ATTENDANT)->create();

        foreach ([$this->manager, $this->attendant] as $user) {
            $user->branches()->attach($this->branch->id, ['is_default' => true]);
        }

        $fuel = FuelProduct::factory()->create();
        $this->nozzleA = $this->makeNozzle('1', $fuel, '1000.000');
        $this->nozzleB = $this->makeNozzle('2', $fuel, '2000.000');
    }

    private function makeNozzle(string $number, FuelProduct $fuel, string $meter): Nozzle
    {
        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
        ]);
        $dispenser = Dispenser::factory()->create(['branch_id' => $this->branch->id]);

        $nozzle = new Nozzle([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $fuel->id,
            'nozzle_number' => $number,
            'opening_meter' => $meter,
            'status' => Nozzle::STATUS_ACTIVE,
        ]);
        $nozzle->save();
        $nozzle->forceFill(['current_meter' => $meter])->save();

        return $nozzle;
    }

    private function openShift(User $user, string $cash = '5000.00', array $nozzles = []): Shift
    {
        return app(ShiftService::class)->open(
            employee: $user,
            branch: $this->branch,
            openingCash: $cash,
            nozzleIds: $nozzles,
        );
    }

    // ---------------------------------------------------------------
    // Opening a shift
    // ---------------------------------------------------------------

    public function test_shift_can_be_opened(): void
    {
        $this->actingAs($this->attendant);

        $shift = $this->openShift($this->attendant, '5000.00', [$this->nozzleA->id]);

        $this->assertSame(Shift::STATUS_OPEN, $shift->status);
        $this->assertStringStartsWith('SHIFT-'.now()->format('Y').'-', $shift->shift_number);
        $this->assertSame('5000.00', $shift->opening_cash);
        $this->assertNotNull($shift->opened_at);
        $this->assertCount(1, $shift->nozzles);
    }

    public function test_opening_meter_is_copied_from_the_nozzle(): void
    {
        $this->actingAs($this->attendant);

        $shift = $this->openShift($this->attendant, '0', [$this->nozzleA->id]);

        $assignment = $shift->nozzles->first();

        $this->assertSame('1000.000', $assignment->opening_meter);
        $this->assertDatabaseHas('meter_readings', [
            'nozzle_id' => $this->nozzleA->id,
            'shift_id' => $shift->id,
            'type' => MeterReading::TYPE_OPENING,
        ]);
    }

    public function test_opening_meter_below_the_system_meter_is_rejected(): void
    {
        $this->actingAs($this->attendant);

        $this->expectException(ValidationException::class);

        app(ShiftService::class)->open(
            employee: $this->attendant,
            branch: $this->branch,
            openingCash: '0',
            nozzleIds: [$this->nozzleA->id],
            openingMeters: [$this->nozzleA->id => '500.000'],  // below 1000
        );
    }

    public function test_opening_meter_above_the_system_meter_is_accepted(): void
    {
        $this->actingAs($this->attendant);

        $shift = app(ShiftService::class)->open(
            employee: $this->attendant,
            branch: $this->branch,
            openingCash: '0',
            nozzleIds: [$this->nozzleA->id],
            openingMeters: [$this->nozzleA->id => '1005.000'],
        );

        $this->assertSame('1005.000', $shift->nozzles->first()->opening_meter);
    }

    // ---------------------------------------------------------------
    // The one-open-shift rule
    // ---------------------------------------------------------------

    public function test_employee_cannot_hold_two_open_shifts(): void
    {
        $this->actingAs($this->attendant);
        $this->openShift($this->attendant);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/already have an open shift/');

        $this->openShift($this->attendant);
    }

    public function test_employee_may_open_a_shift_after_closing_the_previous_one(): void
    {
        $this->actingAs($this->attendant);
        $first = $this->openShift($this->attendant);

        app(ShiftClosingService::class)->close(
            shift: $first,
            closingMeters: [],
            actualCash: '5000.00',
            actor: $this->attendant,
        );

        $second = $this->openShift($this->attendant);

        $this->assertNotSame($first->shift_number, $second->shift_number);
    }

    public function test_different_employees_may_hold_shifts_at_the_same_time(): void
    {
        $this->actingAs($this->manager);

        $a = $this->openShift($this->attendant);
        $b = $this->openShift($this->manager);

        $this->assertNotSame($a->id, $b->id);
    }

    public function test_a_nozzle_cannot_be_on_two_open_shifts(): void
    {
        $this->actingAs($this->manager);
        $this->openShift($this->attendant, '0', [$this->nozzleA->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/already on an open shift/');

        $this->openShift($this->manager, '0', [$this->nozzleA->id]);
    }

    public function test_a_nozzle_frees_up_after_its_shift_closes(): void
    {
        $this->actingAs($this->manager);
        $first = $this->openShift($this->attendant, '0', [$this->nozzleA->id]);

        app(ShiftClosingService::class)->close(
            shift: $first,
            closingMeters: [$first->nozzles->first()->id => '1000.000'],
            actualCash: '0.00',
            actor: $this->attendant,
        );

        $second = $this->openShift($this->manager, '0', [$this->nozzleA->id]);

        $this->assertSame(Shift::STATUS_OPEN, $second->status);
    }

    public function test_cannot_open_a_shift_at_an_unassigned_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $this->actingAs($this->attendant);

        $this->expectException(ValidationException::class);

        app(ShiftService::class)->open(
            employee: $this->attendant,
            branch: $otherBranch,
            openingCash: '0',
        );
    }

    // ---------------------------------------------------------------
    // Expected cash
    // ---------------------------------------------------------------

    public function test_expected_cash_with_no_transactions_is_the_opening_float(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '7500.00');

        $summary = app(ShiftService::class)->summary($shift);

        $this->assertSame('7500.00', $summary['expected_cash']);
    }

    public function test_expected_cash_accounts_for_expenses_and_drops(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');

        // A cash expense and a cash drop both reduce the till.
        DB::table('shift_cash')->insert([
            ['shift_id' => $shift->id, 'entry_type' => 'DROP', 'amount' => '2000.00',
                'user_id' => $this->attendant->id, 'created_at' => now(), 'updated_at' => now()],
            ['shift_id' => $shift->id, 'entry_type' => 'HANDOVER', 'amount' => '500.00',
                'user_id' => $this->attendant->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $summary = app(ShiftService::class)->summary($shift);

        // 10000 - 2000 - 500 = 7500
        $this->assertSame('7500.00', $summary['expected_cash']);
        $this->assertSame('2500.00', $summary['cash_drops']);
    }

    // ---------------------------------------------------------------
    // Closing and cash variance
    // ---------------------------------------------------------------

    public function test_shift_closes_with_a_balanced_till(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00', [$this->nozzleA->id]);
        $assignmentId = $shift->nozzles->first()->id;

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [$assignmentId => '1025.125'],
            actualCash: '10000.00',
            actor: $this->attendant,
        );

        $this->assertSame(Shift::STATUS_CLOSED, $closed->status);
        $this->assertSame('10000.00', $closed->expected_cash);
        $this->assertSame('10000.00', $closed->actual_cash);
        $this->assertSame('0.00', $closed->cash_difference);
        $this->assertNotNull($closed->closed_at);
    }

    public function test_cash_difference_is_actual_minus_expected(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [],
            actualCash: '9500.00',   // 500 short
            actor: $this->attendant,
        );

        $this->assertSame('10000.00', $closed->expected_cash);
        $this->assertSame('9500.00', $closed->actual_cash);
        $this->assertSame('-500.00', $closed->cash_difference);
    }

    public function test_small_variance_within_threshold_closes_directly(): void
    {
        config(['erp.shift_variance_threshold' => 100]);

        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [],
            actualCash: '9990.00',   // 10 short, within tolerance
            actor: $this->attendant,
        );

        $this->assertSame(Shift::STATUS_CLOSED, $closed->status);
    }

    public function test_variance_over_threshold_requires_manager_approval(): void
    {
        config(['erp.shift_variance_threshold' => 100]);

        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [],
            actualCash: '8000.00',   // 2000 short, over threshold
            actor: $this->attendant,
        );

        $this->assertSame(Shift::STATUS_PENDING_APPROVAL, $closed->status);
        $this->assertNull($closed->approved_by);
    }

    public function test_exactly_at_the_threshold_does_not_need_approval(): void
    {
        config(['erp.shift_variance_threshold' => 100]);

        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [],
            actualCash: '9900.00',   // exactly 100 short
            actor: $this->attendant,
        );

        $this->assertSame(Shift::STATUS_CLOSED, $closed->status);
    }

    public function test_manager_can_approve_a_pending_variance(): void
    {
        config(['erp.shift_variance_threshold' => 100]);

        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');
        $pending = app(ShiftClosingService::class)->close(
            shift: $shift, closingMeters: [], actualCash: '8000.00', actor: $this->attendant,
        );

        $this->actingAs($this->manager);
        $approved = app(ShiftClosingService::class)->approve($pending, $this->manager, 'Counted again, confirmed');

        $this->assertSame(Shift::STATUS_CLOSED, $approved->status);
        $this->assertSame($this->manager->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'shift_approve']);
    }

    public function test_attendant_cannot_approve_a_variance(): void
    {
        config(['erp.shift_variance_threshold' => 100]);

        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '10000.00');
        $pending = app(ShiftClosingService::class)->close(
            shift: $shift, closingMeters: [], actualCash: '8000.00', actor: $this->attendant,
        );

        $this->expectException(ValidationException::class);

        app(ShiftClosingService::class)->approve($pending, $this->attendant);
    }

    // ---------------------------------------------------------------
    // Closing meters
    // ---------------------------------------------------------------

    public function test_closing_meters_record_throughput_and_variance(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '0', [$this->nozzleA->id]);
        $assignmentId = $shift->nozzles->first()->id;

        // The nozzle meter advanced by 25.125 L during the shift.
        $this->nozzleA->forceFill(['current_meter' => '1025.125'])->save();

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [$assignmentId => '1025.125'],
            actualCash: '0.00',
            actor: $this->attendant,
        );

        $assignment = $closed->nozzles->first();

        $this->assertSame('1025.125', $assignment->closing_meter);
        $this->assertSame('25.125', $assignment->system_litres, '1025.125 - 1000.000');
        $this->assertSame('0.000', $assignment->meter_variance);
        $this->assertFalse($assignment->variance_flagged);

        $this->assertDatabaseHas('meter_readings', [
            'shift_id' => $shift->id,
            'nozzle_id' => $this->nozzleA->id,
            'type' => MeterReading::TYPE_CLOSING,
        ]);
    }

    public function test_meter_variance_is_physical_minus_system(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '0', [$this->nozzleA->id]);
        $assignmentId = $shift->nozzles->first()->id;

        // System says 1025.125 but the attendant physically reads 1030.000.
        $this->nozzleA->forceFill(['current_meter' => '1025.125'])->save();

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [$assignmentId => '1030.000'],
            actualCash: '0.00',
            actor: $this->attendant,
        );

        $assignment = $closed->nozzles->first();

        $this->assertSame('4.875', $assignment->meter_variance, '1030.000 - 1025.125');
        $this->assertTrue($assignment->variance_flagged, 'Beyond the 0.5 L tolerance.');
    }

    public function test_closing_meter_below_opening_is_rejected_with_the_spec_message(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '0', [$this->nozzleA->id]);
        $assignmentId = $shift->nozzles->first()->id;

        try {
            app(ShiftClosingService::class)->close(
                shift: $shift,
                closingMeters: [$assignmentId => '500.000'],   // below opening 1000
                actualCash: '0.00',
                actor: $this->attendant,
            );
            $this->fail('A closing meter below the opening meter must be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                'Closing meter reading cannot be lower than opening meter reading.',
                $e->getMessage()
            );
        }

        $this->assertSame(Shift::STATUS_OPEN, $shift->fresh()->status, 'A rejected close must not change the shift.');
    }

    public function test_closing_meter_is_required_for_every_nozzle(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '0', [$this->nozzleA->id, $this->nozzleB->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/closing meter reading is required/');

        // Supplying only one of the two nozzles must fail.
        app(ShiftClosingService::class)->close(
            shift: $shift,
            closingMeters: [$shift->nozzles->first()->id => '1000.000'],
            actualCash: '0.00',
            actor: $this->attendant,
        );
    }

    // ---------------------------------------------------------------
    // Authorisation
    // ---------------------------------------------------------------

    public function test_a_different_user_without_shift_close_cannot_close_someone_elses_shift(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '1000.00');

        $otherAttendant = User::factory()->withRole(Role::ATTENDANT)->create();
        $otherAttendant->branches()->attach($this->branch->id, ['is_default' => true]);
        $this->actingAs($otherAttendant);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Only the shift owner or a manager/');

        app(ShiftClosingService::class)->close(
            shift: $shift, closingMeters: [], actualCash: '1000.00', actor: $otherAttendant,
        );
    }

    public function test_a_manager_can_close_someone_elses_shift(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '1000.00');

        $this->actingAs($this->manager);

        $closed = app(ShiftClosingService::class)->close(
            shift: $shift, closingMeters: [], actualCash: '1000.00', actor: $this->manager,
        );

        $this->assertSame(Shift::STATUS_CLOSED, $closed->status);
    }

    public function test_a_closed_shift_cannot_be_closed_again(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant, '1000.00');
        app(ShiftClosingService::class)->close(
            shift: $shift, closingMeters: [], actualCash: '1000.00', actor: $this->attendant,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/already CLOSED/');

        app(ShiftClosingService::class)->close(
            shift: $shift->fresh(), closingMeters: [], actualCash: '1000.00', actor: $this->attendant,
        );
    }

    public function test_active_shift_lookup_returns_the_open_shift(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant);

        $found = app(ShiftService::class)->activeShiftFor($this->attendant, $this->branch->id);

        $this->assertNotNull($found);
        $this->assertSame($shift->id, $found->id);
    }

    public function test_active_shift_lookup_is_null_after_closing(): void
    {
        $this->actingAs($this->attendant);
        $shift = $this->openShift($this->attendant);
        app(ShiftClosingService::class)->close(
            shift: $shift, closingMeters: [], actualCash: '0.00', actor: $this->attendant,
        );

        $this->assertNull(app(ShiftService::class)->activeShiftFor($this->attendant, $this->branch->id));
    }

    public function test_shift_number_is_unique(): void
    {
        $this->actingAs($this->manager);
        $a = $this->openShift($this->attendant);
        $b = $this->openShift($this->manager);

        $this->assertNotSame($a->shift_number, $b->shift_number);
    }
}
