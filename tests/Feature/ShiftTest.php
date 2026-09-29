<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\FuelProduct;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Shift;
use App\Models\ShiftCash;
use App\Models\ShiftNozzle;
use App\Models\Tank;
use App\Models\User;
use App\Services\Fuel\MeterService;
use App\Services\Shift\ShiftService;
use App\Support\PermissionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 4: Shifts.
 *
 * Spec "Done when": tests for one-open-shift rule, expected cash,
 * variance threshold, and unauthorized close pass.
 */
class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $attendant;
    private Branch $branch;
    private Tank $tank;
    private Dispenser $dispenser;
    private Nozzle $nozzle1;
    private Nozzle $nozzle2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->withRole(Role::ADMIN)->create();
        $this->manager = User::factory()->withRole(Role::MANAGER)->create();
        $this->attendant = User::factory()->withRole(Role::ATTENDANT)->create();

        $this->branch = Branch::factory()->create();
        $this->attendant->branches()->attach($this->branch->id);
        $this->manager->branches()->attach($this->branch->id);

        $fuel = FuelProduct::factory()->create([
            'selling_price' => '250.00',
        ]);

        $this->tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
            'capacity' => '20000.000',
            'current_stock' => '10000.000',
        ]);

        $this->dispenser = Dispenser::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $this->nozzle1 = Nozzle::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->dispenser->id,
            'tank_id' => $this->tank->id,
            'fuel_product_id' => $fuel->id,
            'nozzle_number' => 'NZ-01',
            'opening_meter' => '1000.000',
            'current_meter' => '1000.000',
        ]);

        $this->nozzle2 = Nozzle::factory()->create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->dispenser->id,
            'tank_id' => $this->tank->id,
            'fuel_product_id' => $fuel->id,
            'nozzle_number' => 'NZ-02',
            'opening_meter' => '2000.000',
            'current_meter' => '2000.000',
        ]);
    }

    public function test_user_with_permission_can_open_shift_with_nozzles(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/shifts', [
                'branch_id' => $this->branch->id,
                'user_id' => $this->attendant->id,
                'opening_cash' => '1500.00',
                'opening_notes' => 'Morning shift',
                'nozzles' => [
                    ['nozzle_id' => $this->nozzle1->id, 'opening_meter' => '1000.000'],
                    ['nozzle_id' => $this->nozzle2->id, 'opening_meter' => '2000.000'],
                ],
            ]);

        $shift = Shift::first();
        $this->assertNotNull($shift);
        $response->assertRedirect(route('shifts.show', $shift));

        $this->assertSame(Shift::STATUS_OPEN, $shift->status);
        $this->assertSame('1500.00', (string) $shift->opening_cash);
        $this->assertSame('1500.00', (string) $shift->expected_cash);
        $this->assertStringStartsWith('SHIFT-', $shift->shift_number);

        // Shift nozzles attached
        $this->assertCount(2, $shift->shiftNozzles);

        // Meter readings of type OPENING recorded
        $this->assertDatabaseHas('meter_readings', [
            'shift_id' => $shift->id,
            'nozzle_id' => $this->nozzle1->id,
            'type' => MeterReading::TYPE_OPENING,
            'current_meter' => '1000.000',
        ]);
    }

    public function test_one_open_shift_per_employee_rule(): void
    {
        $service = app(ShiftService::class);

        $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '500.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id],
            ],
        ], $this->admin);

        // Second attempt to open a shift for the same employee must throw ValidationException
        $this->expectException(ValidationException::class);
        $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle2->id],
            ],
        ], $this->admin);
    }

    public function test_one_open_shift_per_nozzle_rule(): void
    {
        $service = app(ShiftService::class);

        $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '500.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id],
            ],
        ], $this->admin);

        // Another employee tries to open a shift using the same nozzle
        $otherAttendant = User::factory()->withRole(Role::ATTENDANT)->create();

        $this->expectException(ValidationException::class);
        $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $otherAttendant->id,
            'opening_cash' => '500.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id],
            ],
        ], $this->admin);
    }

    public function test_opening_cash_cannot_be_negative(): void
    {
        $service = app(ShiftService::class);

        $this->expectException(ValidationException::class);
        $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '-100.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id],
            ],
        ], $this->admin);
    }

    public function test_opening_meter_variance_over_tolerance_requires_note(): void
    {
        $service = app(ShiftService::class);

        // Nozzle 1 current meter is 1000.000. Tolerance is 0.5.
        // Trying to enter 1005.000 with no note must fail.
        $this->expectException(ValidationException::class);
        $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '500.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'opening_meter' => '1005.000'],
            ],
        ], $this->admin);
    }

    public function test_opening_meter_variance_with_note_is_accepted(): void
    {
        $service = app(ShiftService::class);

        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '500.00',
            'opening_notes' => 'Meter calibration difference verified',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'opening_meter' => '1005.000', 'notes' => 'Physical dial was at 1005'],
            ],
        ], $this->admin);

        $this->assertNotNull($shift);
        $this->assertSame('1005.000', (string) $this->nozzle1->fresh()->current_meter);
    }

    public function test_unauthorized_user_cannot_close_shift(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        $anotherAttendant = User::factory()->withRole(Role::ATTENDANT)->create();

        $this->actingAs($anotherAttendant)
            ->post("/shifts/{$shift->id}/close", [
                'actual_cash' => '1000.00',
                'nozzles' => [
                    ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '1050.000'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_shift_owner_can_close_shift(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        $this->actingAs($this->attendant)
            ->post("/shifts/{$shift->id}/close", [
                'actual_cash' => '1000.00',
                'nozzles' => [
                    ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '1050.000'],
                ],
            ])
            ->assertRedirect(route('shifts.show', $shift));

        $shift->refresh();
        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertSame('1050.000', (string) $this->nozzle1->fresh()->current_meter);
    }

    public function test_closing_meter_cannot_be_lower_than_opening_meter(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        // Attempting to close with 999.000 (lower than opening 1000.000)
        $this->expectException(ValidationException::class);
        $service->close($shift, [
            'actual_cash' => '1000.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '999.000'],
            ],
        ], $this->attendant);
    }

    public function test_expected_cash_calculation_with_float_and_cash_drops(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '2000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        // Expected cash starts at 2000.00
        $this->assertSame('2000.00', $service->calculateExpectedCash($shift));

        // Add float top-up of 500
        $service->addCashMovement($shift, '500.00', ShiftCash::TYPE_FLOAT_ADDITION, $this->manager, 'Extra change float');
        $this->assertSame('2500.00', $service->calculateExpectedCash($shift));

        // Mid-shift vault drop of 1000
        $service->addCashMovement($shift, '1000.00', ShiftCash::TYPE_DROP, $this->attendant, 'Safe drop');
        $this->assertSame('1500.00', $service->calculateExpectedCash($shift));
    }

    public function test_shift_within_variance_threshold_closes_immediately(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        // Actual cash is 1050 (variance is +50, within default 100 threshold)
        $service->close($shift, [
            'actual_cash' => '1050.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '1050.000'],
            ],
        ], $this->attendant);

        $shift->refresh();
        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertSame('50.00', (string) $shift->cash_difference);
    }

    public function test_shift_exceeding_variance_requires_closing_note(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        // Actual cash is 500 (difference is -500, exceeds 100 threshold). Without note, must fail.
        $this->expectException(ValidationException::class);
        $service->close($shift, [
            'actual_cash' => '500.00',
            'closing_notes' => '',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '1000.000'],
            ],
        ], $this->attendant);
    }

    public function test_attendant_shift_exceeding_variance_sets_pending_approval_and_notifies_manager(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        // Closed by attendant with -300 difference and a note
        $service->close($shift, [
            'actual_cash' => '700.00',
            'closing_notes' => 'Customer drove off without paying Rs. 300',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '1000.000'],
            ],
        ], $this->attendant);

        $shift->refresh();
        $this->assertSame(Shift::STATUS_PENDING_APPROVAL, $shift->status);
        $this->assertNull($shift->approved_by);

        // Manager notification was dispatched
        $this->assertDatabaseHas('notifications', [
            'type' => 'SHIFT_VARIANCE',
            'reference_type' => Shift::class,
            'reference_id' => $shift->id,
        ]);
    }

    public function test_manager_can_approve_pending_shift(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        $service->close($shift, [
            'actual_cash' => '700.00',
            'closing_notes' => 'Fuel spill / difference',
            'nozzles' => [
                ['nozzle_id' => $this->nozzle1->id, 'closing_meter' => '1000.000'],
            ],
        ], $this->attendant);

        $shift->refresh();
        $this->assertSame(Shift::STATUS_PENDING_APPROVAL, $shift->status);

        // Manager approves
        $service->approve($shift, $this->manager, 'Approved after review');

        $shift->refresh();
        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertSame($this->manager->id, $shift->approved_by);
        $this->assertNotNull($shift->approved_at);
    }

    public function test_printable_shift_report_renders(): void
    {
        $service = app(ShiftService::class);
        $shift = $service->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->attendant->id,
            'opening_cash' => '1000.00',
            'nozzles' => [['nozzle_id' => $this->nozzle1->id]],
        ], $this->admin);

        $this->actingAs($this->attendant)
            ->get("/shifts/{$shift->id}/print")
            ->assertOk()
            ->assertSee($shift->shift_number)
            ->assertSee('SHIFT SUMMARY REPORT');
    }
}
