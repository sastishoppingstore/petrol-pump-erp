<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\Tank;
use App\Models\User;
use App\Support\Quantity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Row-level locking, proven against two genuinely separate MySQL sessions.
 *
 * This class uses DatabaseMigrations rather than RefreshDatabase on purpose.
 * RefreshDatabase wraps each test in one long transaction on the default
 * connection, which holds a lock on every row it touches — so a second
 * connection can never demonstrate anything about concurrency. Real commits
 * are required to test SELECT ... FOR UPDATE honestly.
 */
class StockConcurrencyTest extends TestCase
{

    private User $admin;
    private Tank $tank;
    private string $connectionA;
    private string $connectionB;

    protected function setUp(): void
    {
        parent::setUp();

        // Deliberately no RefreshDatabase / DatabaseMigrations here: both wrap
        // the test in a transaction, and a transaction holds a lock on every
        // row it touches — which is exactly what a second connection is
        // supposed to contend for. The schema is therefore built and committed
        // directly, so every write below is a real, visible commit.
        Artisan::call('migrate:fresh', ['--force' => true]);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = User::factory()->withRole(Role::ADMIN)->create();

        $this->tank = Tank::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'capacity' => '10000.000',
            'opening_stock' => '1000.000',
        ]);
        $this->tank->forceFill(['current_stock' => '1000.000'])->save();

        $this->connectionA = $this->makeConnection();
        $this->connectionB = $this->makeConnection();
    }

    protected function tearDown(): void
    {
        DB::purge($this->connectionA);
        DB::purge($this->connectionB);

        // This class commits real rows, so it must leave nothing behind.
        // RefreshDatabase only migrates once per test *process*, so any
        // leftover user/tank created here would otherwise be visible to every
        // later test as pre-existing data and break their assumptions.
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
        } catch (\Throwable $e) {
            // Never let cleanup mask a test failure.
        }

        parent::tearDown();
    }

    /**
     * Both sessions lock the same tank and sell 100 L. Without
     * SELECT ... FOR UPDATE each would read 1000.000 and write 900.000, and
     * 100 L of dispensed fuel would vanish. With the lock, the second session
     * blocks until the first commits, then reads the updated value.
     */
    public function test_for_update_prevents_a_lost_update(): void
    {
        $tankId = $this->tank->id;
        $fuelId = $this->tank->fuel_product_id;
        $branchId = $this->tank->branch_id;
        $userId = $this->admin->id;

        $sell = function (string $conn) use ($tankId, $fuelId, $branchId, $userId) {
            $db = DB::connection($conn);
            $db->beginTransaction();

            try {
                $row = $db->table('tanks')->where('id', $tankId)->lockForUpdate()->first();

                $before = (string) $row->current_stock;
                $after = Quantity::add($before, '-100.000');

                $db->table('tanks')->where('id', $tankId)
                    ->update(['current_stock' => $after, 'updated_at' => now()]);

                $db->table('tank_movements')->insert([
                    'branch_id' => $branchId,
                    'tank_id' => $tankId,
                    'fuel_product_id' => $fuelId,
                    'type' => 'SALE',
                    'quantity' => '-100.000',
                    'before_quantity' => $before,
                    'after_quantity' => $after,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $db->commit();

                return ['before' => $before, 'after' => $after];
            } catch (\Throwable $e) {
                $db->rollBack();

                throw $e;
            }
        };

        $first = $sell($this->connectionA);
        $second = $sell($this->connectionB);

        // The critical assertion: the second session must have seen the first
        // session's committed value, not a stale one.
        $this->assertSame('1000.000', $first['before']);
        $this->assertSame('900.000', $first['after']);
        $this->assertSame(
            '900.000',
            $second['before'],
            'A lost update would show 1000.000 here.'
        );
        $this->assertSame('800.000', $second['after']);

        $this->assertSame(
            '800.000',
            $this->tank->fresh()->current_stock,
            'Both 100 L sales must be reflected: 1000 - 100 - 100 = 800.'
        );
    }

    /**
     * The ledger must form an unbroken chain: each row's "before" equals the
     * previous row's "after". A lost update would produce two rows both
     * claiming 1000.000.
     */
    public function test_movement_ledger_is_a_consistent_chain(): void
    {
        $tankId = $this->tank->id;
        $fuelId = $this->tank->fuel_product_id;
        $branchId = $this->tank->branch_id;
        $userId = $this->admin->id;

        $sell = function (string $conn) use ($tankId, $fuelId, $branchId, $userId) {
            $db = DB::connection($conn);
            $db->beginTransaction();

            $row = $db->table('tanks')->where('id', $tankId)->lockForUpdate()->first();
            $before = (string) $row->current_stock;
            $after = Quantity::add($before, '-25.000');

            $db->table('tanks')->where('id', $tankId)
                ->update(['current_stock' => $after, 'updated_at' => now()]);

            $db->table('tank_movements')->insert([
                'branch_id' => $branchId, 'tank_id' => $tankId, 'fuel_product_id' => $fuelId,
                'type' => 'SALE', 'quantity' => '-25.000',
                'before_quantity' => $before, 'after_quantity' => $after,
                'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $db->commit();
        };

        $sell($this->connectionA);
        $sell($this->connectionB);
        $sell($this->connectionA);

        $movements = DB::table('tank_movements')->where('tank_id', $tankId)->orderBy('id')->get();

        $this->assertCount(3, $movements);
        $this->assertSame('1000.000', $movements[0]->before_quantity);
        $this->assertSame('975.000', $movements[0]->after_quantity);
        $this->assertSame('975.000', $movements[1]->before_quantity);
        $this->assertSame('950.000', $movements[1]->after_quantity);
        $this->assertSame('950.000', $movements[2]->before_quantity);
        $this->assertSame('925.000', $movements[2]->after_quantity);

        $this->assertSame('925.000', $this->tank->fresh()->current_stock);
    }

    /**
     * A row locked by an uncommitted transaction blocks a second session until
     * the first one finishes. This is the property that stops two attendants
     * selling the same fuel at the same moment.
     */
    public function test_a_second_session_blocks_while_the_row_is_locked(): void
    {
        $tankId = $this->tank->id;

        $holder = DB::connection($this->connectionA);
        $holder->beginTransaction();
        $holder->table('tanks')->where('id', $tankId)->lockForUpdate()->first();

        // The waiter runs on a separate connection with a short lock timeout.
        $waiter = DB::connection($this->connectionB);
        $waiter->statement('SET SESSION innodb_lock_wait_timeout = 2');

        $blocked = false;

        try {
            $waiter->beginTransaction();
            $waiter->table('tanks')->where('id', $tankId)->lockForUpdate()->first();
        } catch (\Illuminate\Database\QueryException $e) {
            // 1205 = lock wait timeout, 1213 = deadlock.
            $blocked = str_contains($e->getMessage(), 'Lock wait timeout')
                || str_contains($e->getMessage(), 'Deadlock');

            $waiter->rollBack();
        }

        $holder->commit();

        $this->assertTrue(
            $blocked,
            'A second session must not be able to take the same row lock while the first holds it.'
        );
    }

    private function makeConnection(): string
    {
        $name = 'conc_'.uniqid();

        config(['database.connections.'.$name => [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => config('database.connections.mysql.database'),
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => true,
            'engine' => 'InnoDB',
            'prefix' => '',
        ]]);

        DB::purge($name);

        return $name;
    }
}
