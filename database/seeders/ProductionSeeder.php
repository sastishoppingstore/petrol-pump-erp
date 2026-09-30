<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\System\SettingService;
use Illuminate\Database\Seeder;

/**
 * Production seed: reference data only.
 *
 * Creates roles, permissions, the role matrix, and the station's business
 * settings. It deliberately does NOT create a user or password — the first
 * administrator is created interactively by `php artisan erp:install`, so no
 * default credential ever exists in a production database.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SettingSeeder::class);
    }
}
