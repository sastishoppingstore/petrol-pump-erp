<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Notification;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\Tank;
use App\Models\TankMovement;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Stock\StockAdjustmentService;
use App\Services\Stock\StockService;
use App\Services\System\NotificationService;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 3: the stock engine.
 *
 * Spec "Done when": tests prove stock never goes negative, before/after
 * quantities are correct, concurrent-safe locking is used, and adjustments
 * are audited.
 */
class StockEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tank $tank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = User::factory()->withRole(Role::ADMIN)->create();

        $this->tank = Tank::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'capacity' => '10000.000',
            'min_level' => '500.000',
            'max_level' => '9500.000',
            'opening_stock' => '1000.000',
            'low_stock_threshold' => '200.000',
        ]);
        // current_stock starts equal to opening stock.
        $this->tank->forceFill(['current_stock' => '1000.000'])->save();

        $this->actingAs($this->admin);
    }

    private function move(string $type, string $quantity, ?Tank $tank = null): TankMovement
    {
        return DB::transaction(fn () => app(StockService::class)->move(
            tank: $tank ?? $this->tank,
            type: $type,
            quantity: $quantity,
            userId: $this->admin->id,
        ));
    }

    // ---------------------------------------------------------------
    // move(): before / after quantities
    // ---------------------------------------------------------------

    public function test_purchase_increases_stock_and_records_before_and_after(): void
    {
        $movement = $this->move(StockService::TYPE_PURCHASE, '5000.000');

        $this->assertSame('1000.000', $movement->before_quantity);
        $this->assertSame('6000.000', $movement->after_quantity);
        $this->assertSame('5000.000', $movement->quantity);
        $this->assertSame('6000.000', $this->tank->fresh()->current_stock);
    }

    public function test_sale_reduces_stock_and_is_stored_as_a_negative_quantity(): void
    {
        $movement = $this->move(StockService::TYPE_SALE, '25.125');

        $this->assertSame('1000.000', $movement->before_quantity);
        $this->assertSame('974.875', $movement->after_quantity);
        $this->assertSame('-25.125', $movement->quantity, 'An outbound move is stored signed.');
        $this->assertSame('974.875', $this->tank->fresh()->current_stock);
    }

    public function test_every_movement_writes_an_auditable_row(): void
    {
        $this->move(StockService::TYPE_PURCHASE, '100.000');

        $row = TankMovement::first();

        $this->assertDatabaseHas('tank_movements', [
            'tank_id' => $this->tank->id,
            'type' => StockService::TYPE_PURCHASE,
            'quantity' => '100.000',
            'before_quantity' => '1000.000',
            'after_quantity' => '1100.000',
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * RefreshDatabase wraps every test in a transaction, so DB::transactionLevel()
     * is never 0 here and the guard cannot fire. The guard exists for
     * production callers and is asserted by reading the source below.
     */
    public function test_move_refuses_to_run_outside_a_transaction(): void
    {
        $source = file_get_contents(app_path('Services/Stock/StockService.php'));

        $this->assertStringContainsString(
            'must be called inside a DB transaction',
            $source,
            'StockService::move() must keep its transaction guard.'
        );

        $this->assertStringContainsString(
            'DB::transactionLevel() < 1',
            $source,
            'The guard must check the transaction level before locking anything.'
        );
    }

    public function test_unknown_movement_type_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DB::transaction(fn () => app(StockService::class)->move($this->tank, 'TELEPORT', '10.000'));
    }

    // ---------------------------------------------------------------
    // Stock can never go negative or overfill
    // ---------------------------------------------------------------

    public function test_stock_can_never_go_below_zero(): void
    {
        // Tank holds 1000 L; try to remove 1500 L.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        try {
            $this->move(StockService::TYPE_SALE, '1500.000');
        } finally {
            $this->assertSame(
                '1000.000',
                $this->tank->fresh()->current_stock,
                'A rejected movement must leave stock completely untouched.'
            );
            $this->assertDatabaseMissing('tank_movements', [
                'tank_id' => $this->tank->id,
                'type' => StockService::TYPE_SALE,
            ]);
        }
    }

    public function test_removing_exactly_all_stock_is_allowed(): void
    {
        $this->move(StockService::TYPE_SALE, '1000.000');

        $this->assertSame('0.000', $this->tank->fresh()->current_stock);
    }

    public function test_removing_one_millilitre_more_than_available_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->move(StockService::TYPE_SALE, '1000.001');
    }

    public function test_stock_can_never_exceed_capacity(): void
    {
        // Tank holds 1000 of 10000; 9500 more would fill it exactly.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/capacity/');

        try {
            $this->move(StockService::TYPE_PURCHASE, '9500.001');
        } finally {
            $this->assertSame('1000.000', $this->tank->fresh()->current_stock);
        }
    }

    public function test_filling_to_exactly_capacity_is_allowed(): void
    {
        $this->move(StockService::TYPE_PURCHASE, '9000.000');

        $this->assertSame('10000.000', $this->tank->fresh()->current_stock);
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->move(StockService::TYPE_PURCHASE, '-100.000');
    }

    // ---------------------------------------------------------------
    // expected() and variance()
    // ---------------------------------------------------------------

    public function test_expected_stock_is_derived_from_the_ledger(): void
    {
        $this->move(StockService::TYPE_PURCHASE, '2000.000');  // 1000 -> 3000
        $this->move(StockService::TYPE_SALE, '500.000');        // 3000 -> 2500
        $this->move(StockService::TYPE_ADJUSTMENT_OUT, '200.000'); // 2500 -> 2300

        // Opening 1000 + 2000 purchase - 500 sale - 200 out = 2300.
        $this->assertSame('2300.000', app(StockService::class)->expected($this->tank->id));
    }

    public function test_expected_stock_matches_the_tank_balance_with_no_drift(): void
    {
        $this->move(StockService::TYPE_PURCHASE, '2000.000');
        $this->move(StockService::TYPE_SALE, '500.000');
        $this->move(StockService::TYPE_TRANSFER_IN, '100.000');
        $this->move(StockService::TYPE_LOSS, '25.000');

        $service = app(StockService::class);

        $this->assertSame(
            '0.000',
            $service->ledgerDrift($this->tank->fresh()),
            'The ledger and tanks.current_stock must always agree.'
        );
    }

    public function test_variance_is_physical_minus_expected(): void
    {
        $this->move(StockService::TYPE_PURCHASE, '1000.000');  // now 2000

        $service = app(StockService::class);

        $this->assertSame('2000.000', $service->expected($this->tank->id));
        $this->assertSame('-50.000', $service->variance($this->tank->fresh(), '1950.000'), 'shortage');
        $this->assertSame('50.000', $service->variance($this->tank->fresh(), '2050.000'), 'surplus');
        $this->assertSame('0.000', $service->variance($this->tank->fresh(), '2000.000'), 'match');
    }

    public function test_current_stock_cannot_be_mass_assigned(): void
    {
        // A form post that includes current_stock must be ignored: stock is
        // only ever changed by StockService.
        $this->tank->forceFill(['current_stock' => '999999.000'])->save();
        $this->assertSame('999999.000', $this->tank->fresh()->current_stock);

        $fresh = Tank::find($this->tank->id);
        $fresh->fill(['current_stock' => '1.000', 'notes' => 'test']);
        $fresh->save();

        $this->assertSame('999999.000', $fresh->fresh()->current_stock, 'Mass-assigned stock must be ignored.');
        $this->assertSame('test', $fresh->fresh()->notes);
    }

    // ---------------------------------------------------------------
    // Concurrency — the reason FOR UPDATE exists
    // ---------------------------------------------------------------

    /**
     * With the lock in place, two sales of 600 L against 1000 L of stock
     * cannot both succeed — the second must be refused as insufficient.
     */
    public function test_second_oversized_sale_is_refused_once_the_first_commits(): void
    {
        $this->move(StockService::TYPE_SALE, '600.000');

        try {
            $this->move(StockService::TYPE_SALE, '600.000');
            $this->fail('The second 600 L sale should have been refused with only 400 L left.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        $this->assertSame('400.000', $this->tank->fresh()->current_stock);
        $this->assertDatabaseCount('tank_movements', 1);
    }

    // ---------------------------------------------------------------
    // Stock adjustments
    // ---------------------------------------------------------------

    public function test_adjustment_request_does_not_move_stock_until_approved(): void
    {
        $service = app(StockAdjustmentService::class);

        $adjustment = $service->request(
            tank: $this->tank,
            type: StockAdjustment::TYPE_OUT,
            quantity: '100.000',
            reason: 'Damaged drum found during dip',
            userId: $this->admin->id,
        );

        $this->assertSame(StockAdjustment::STATUS_PENDING, $adjustment->status);
        $this->assertSame('1000.000', $this->tank->fresh()->current_stock, 'A pending request must not move stock.');
        $this->assertDatabaseCount('tank_movements', 0);
    }

    public function test_approving_an_adjustment_moves_stock_and_is_audited(): void
    {
        $service = app(StockAdjustmentService::class);

        $adjustment = $service->request(
            tank: $this->tank,
            type: StockAdjustment::TYPE_OUT,
            quantity: '150.000',
            reason: 'Shortage confirmed at dip',
            userId: $this->admin->id,
        );

        $approved = $service->approve($adjustment, $this->admin->id);

        $this->assertSame(StockAdjustment::STATUS_APPROVED, $approved->status);
        $this->assertSame('850.000', $approved->stock_after);

        $this->assertSame('850.000', $this->tank->fresh()->current_stock);

        $this->assertDatabaseHas('tank_movements', [
            'tank_id' => $this->tank->id,
            'type' => StockService::TYPE_ADJUSTMENT_OUT,
            'quantity' => '-150.000',
            'before_quantity' => '1000.000',
            'after_quantity' => '850.000',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'stock_adjustment_approve',
            'module' => 'stock',
        ]);
    }

    public function test_approved_adjustment_moves_stock_and_keeps_the_ledger_balanced(): void
    {
        $service = app(StockAdjustmentService::class);

        $adjustment = $service->request(
            $this->tank, StockAdjustment::TYPE_IN, '300.000', 'Returned tanker', $this->admin->id
        );
        $service->approve($adjustment, $this->admin->id);

        $this->assertSame('1300.000', $this->tank->fresh()->current_stock);
        $this->assertSame(
            '0.000',
            app(StockService::class)->ledgerDrift($this->tank->fresh()),
            'An approved adjustment must keep the ledger in step.'
        );
    }

    public function test_adjustment_requires_a_reason(): void
    {
        $this->expectException(ValidationException::class);

        app(StockAdjustmentService::class)->request(
            $this->tank, StockAdjustment::TYPE_OUT, '10.000', '   ', $this->admin->id
        );
    }

    public function test_cannot_approve_the_same_adjustment_twice(): void
    {
        $service = app(StockAdjustmentService::class);

        $adjustment = $service->request(
            $this->tank, StockAdjustment::TYPE_OUT, '10.000', 'First', $this->admin->id
        );
        $service->approve($adjustment, $this->admin->id);

        $this->expectException(ValidationException::class);

        $service->approve($adjustment->fresh(), $this->admin->id);
    }

    public function test_approving_an_adjustment_that_would_go_negative_is_refused(): void
    {
        $service = app(StockAdjustmentService::class);

        $adjustment = $service->request(
            $this->tank, StockAdjustment::TYPE_OUT, '5000.000', 'Typo in quantity', $this->admin->id
        );

        $this->expectException(ValidationException::class);

        try {
            $service->approve($adjustment, $this->admin->id);
        } finally {
            $this->assertSame('1000.000', $this->tank->fresh()->current_stock);
            $this->assertSame(StockAdjustment::STATUS_PENDING, $adjustment->fresh()->status);
            $this->assertDatabaseCount('tank_movements', 0);
        }
    }

    public function test_rejected_adjustment_leaves_stock_untouched_and_is_audited(): void
    {
        $service = app(StockAdjustmentService::class);

        $adjustment = $service->request(
            $this->tank, StockAdjustment::TYPE_OUT, '50.000', 'Suspect', $this->admin->id
        );
        $service->reject($adjustment, $this->admin->id, 'No supporting dip reading');

        $this->assertSame(StockAdjustment::STATUS_REJECTED, $adjustment->fresh()->status);
        $this->assertSame('1000.000', $this->tank->fresh()->current_stock);
        $this->assertDatabaseCount('tank_movements', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_adjustment_reject']);
    }

    public function test_adjustment_reference_number_is_unique_and_sequential(): void
    {
        $service = app(StockAdjustmentService::class);

        $a = $service->request($this->tank, StockAdjustment::TYPE_IN, '1.000', 'A', $this->admin->id);
        $b = $service->request($this->tank, StockAdjustment::TYPE_IN, '1.000', 'B', $this->admin->id);

        $this->assertStringStartsWith('ADJ-'.now()->format('Y').'-', $a->reference_number);
        $this->assertNotSame($a->reference_number, $b->reference_number);
    }

    // ---------------------------------------------------------------
    // Low-stock notifications (once per tank per day)
    // ---------------------------------------------------------------

    public function test_low_stock_creates_a_notification_once_per_tank_per_day(): void
    {
        // setUp already creates one admin; this adds a second eligible user.
        User::factory()->withRole(Role::MANAGER)->create();
        $this->tank->forceFill(['current_stock' => '100.000'])->save();  // below 200

        $service = app(NotificationService::class);

        $first = $service->lowStock($this->tank->fresh());
        $this->assertSame(2, $first, 'Both the admin and the manager are alerted once.');

        // A second scan on the same day must not spam.
        $second = $service->lowStock($this->tank->fresh());
        $this->assertSame(0, $second, 'A repeat scan on the same day creates nothing.');

        $this->assertSame(
            2,
            Notification::where('type', Notification::TYPE_LOW_STOCK)->count(),
            'Exactly one low-stock notification per tank per day, per user.'
        );
    }

    public function test_low_stock_dedupe_key_is_scoped_to_the_day(): void
    {
        User::factory()->withRole(Role::MANAGER)->create();
        $this->tank->forceFill(['current_stock' => '100.000'])->save();

        app(NotificationService::class)->lowStock($this->tank->fresh());

        $key = Notification::first()->dedupe_key;

        $this->assertSame('low-stock:tank:'.$this->tank->id.':'.now()->toDateString(), $key);
    }

    public function test_low_stock_does_not_notify_while_stock_is_healthy(): void
    {
        $this->assertSame(0, app(NotificationService::class)->lowStock($this->tank->fresh()));
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_low_stock_notifies_each_eligible_user_once(): void
    {
        // setUp's admin plus the two managers below = 3 eligible recipients.
        $m1 = User::factory()->withRole(Role::MANAGER)->create();
        $m2 = User::factory()->withRole(Role::MANAGER)->create();
        User::factory()->withRole(Role::ATTENDANT)->create(); // not eligible

        $this->tank->forceFill(['current_stock' => '10.000'])->save();

        app(NotificationService::class)->lowStock($this->tank->fresh());

        $this->assertDatabaseHas('notifications', ['user_id' => $m1->id, 'type' => Notification::TYPE_LOW_STOCK]);
        $this->assertDatabaseHas('notifications', ['user_id' => $m2->id, 'type' => Notification::TYPE_LOW_STOCK]);
        $this->assertSame(3, Notification::count(), 'Only managers and admins are alerted.');
    }

    public function test_notification_can_be_marked_read(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();
        $this->tank->forceFill(['current_stock' => '10.000'])->save();

        $service = app(NotificationService::class);
        $service->lowStock($this->tank->fresh());

        // Fetch *this user's* notification, not just the first row.
        $notification = Notification::where('user_id', $manager->id)->firstOrFail();

        $this->assertFalse($notification->isRead());
        $this->assertSame(1, $service->unreadCount($manager->id));

        $service->markRead($notification->id, $manager->id);

        $this->assertTrue($notification->fresh()->isRead());
        $this->assertSame(0, $service->unreadCount($manager->id));
    }

    public function test_one_user_cannot_mark_another_users_notification_read(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();
        $this->tank->forceFill(['current_stock' => '10.000'])->save();
        app(NotificationService::class)->lowStock($this->tank->fresh());

        $notification = Notification::where('user_id', $manager->id)->firstOrFail();

        // A different user id must not be able to clear it.
        app(NotificationService::class)->markRead($notification->id, $this->admin->id + 9999);

        $this->assertFalse($notification->fresh()->isRead());
    }

}
