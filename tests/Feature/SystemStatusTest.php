<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 0 smoke tests: the application boots, is wired to a real MySQL
 * database, and migrations run cleanly from an empty schema.
 */
class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_boots_and_renders_the_status_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(config('app.name'));
        $response->assertSee('Database connected.', escape: false);
    }

    public function test_health_endpoint_reports_database_up(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'database' => 'up',
            ]);
    }

    public function test_application_runs_against_mysql_not_sqlite(): void
    {
        // The whole stock/money engine depends on MySQL semantics
        // (FOR UPDATE locking, DECIMAL precision). Guard against drift.
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame(
            'mysql',
            DB::connection()->getDriverName(),
            'Feature tests must run against MySQL.'
        );
    }

    public function test_migrations_create_the_expected_base_tables(): void
    {
        foreach (['migrations', 'users', 'sessions', 'cache', 'jobs'] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table [{$table}] to exist after migration."
            );
        }
    }
}
