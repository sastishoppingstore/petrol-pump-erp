<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * auto_reports.period ENUM me 'custom' shamil karna — admin apna
 * custom interval (hours) select kar sakta hai (setting
 * auto_report_custom_hours), us period ki reports 'custom' key se
 * save hoti hain. ENUM me na ho to MySQL strict mode par insert fail
 * ho jata.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE auto_reports MODIFY period ENUM('12h', '24h', '7d', '15d', '30d', 'custom') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE auto_reports MODIFY period ENUM('12h', '24h', '7d', '15d', '30d') NOT NULL");
        }
    }
};
