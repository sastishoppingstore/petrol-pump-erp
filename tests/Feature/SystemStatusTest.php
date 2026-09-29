<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Baseline checks: the app boots against a real MySQL database, migrations
 * run from empty, and the liveness probe works.
 */
class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_guests_to_login(): void
    {
        // "/" is the auth-protected dashboard since Phase 1.
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_health_endpoint_reports_database_up(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'database' => 'up',
            ]);
    }

    public function test_health_endpoint_leaks_no_internal_detail(): void
    {
        $body = $this->getJson('/health')->getContent();

        foreach (['mariadb', 'mysql', 'petrol_pump_erp', '8.4', 'Laravel'] as $secretish) {
            $this->assertStringNotContainsStringIgnoringCase(
                $secretish,
                (string) $body,
                "Health output must not disclose [{$secretish}]."
            );
        }
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
        $expected = [
            'migrations', 'users', 'sessions', 'cache', 'jobs',
            'branches', 'roles', 'permissions', 'role_permissions',
            'user_roles', 'user_branches', 'login_attempts',
        ];

        foreach ($expected as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table [{$table}] to exist after migration."
            );
        }
    }
}
