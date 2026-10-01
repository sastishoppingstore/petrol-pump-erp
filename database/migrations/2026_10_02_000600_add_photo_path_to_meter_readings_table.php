<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meter reading ki photo (K3): manual meter entry wizard me attendant
     * meter ki tasveer le sakta hai — reading ke saboot ke tor par
     * meter_readings row ke saath path save hota hai (public disk,
     * purchases ke bill_photo wala hi pattern).
     */
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('meter_readings', 'photo_path')) {
                $table->string('photo_path', 255)->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            if (Schema::hasColumn('meter_readings', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });
    }
};
