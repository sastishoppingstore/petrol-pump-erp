<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tank dip ke saath temperature / density (K3): dip reading ke waqt
     * fuel ka darja-e-hararat (°C) aur density note karne ke liye do
     * optional columns. Sirf record ke liye hain — variance calculation
     * in par depend nahi karti.
     */
    public function up(): void
    {
        Schema::table('tank_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('tank_readings', 'temperature_c')) {
                $table->decimal('temperature_c', 5, 2)->nullable()->after('dip_cm');
            }

            if (! Schema::hasColumn('tank_readings', 'density')) {
                $table->decimal('density', 8, 4)->nullable()->after('temperature_c');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tank_readings', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['temperature_c', 'density'],
                fn (string $column) => Schema::hasColumn('tank_readings', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
