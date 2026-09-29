<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production seed: reference data only.
 *
 * Creates roles, permissions and the role matrix. It deliberately does NOT
 * create any user, password or business record — the first administrator is
 * created interactively by `php artisan erp:install`, so no default
 * password ever exists in a production database.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
    }
}
