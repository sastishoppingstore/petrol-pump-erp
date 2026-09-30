<?php

namespace Database\Seeders;

use App\Services\System\SettingService;
use Illuminate\Database\Seeder;

/**
 * Business identity and thresholds for Mehar Filling Station.
 *
 * Idempotent — seedDefaults() only inserts keys that do not already exist, so
 * re-running never overwrites a correction made through the Settings screen.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        app(SettingService::class)->seedDefaults();
    }
}
