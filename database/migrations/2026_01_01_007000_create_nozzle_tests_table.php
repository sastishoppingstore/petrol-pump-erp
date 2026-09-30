<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nozzle_tests')) {
            Schema::create('nozzle_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('nozzle_id')->constrained('nozzles')->cascadeOnDelete();
                $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('test_number', 30)->unique();
                $table->decimal('litres', 12, 3);
                $table->decimal('meter_start', 14, 3);
                $table->decimal('meter_end', 14, 3);
                $table->dateTime('tested_at');
                $table->string('reason', 150)->default('Calibration test / پیمانہ ٹیسٹ');
                $table->boolean('returned_to_tank')->default(true);
                $table->string('status', 20)->default('COMPLETED');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'tested_at']);
                $table->index('nozzle_id');
                $table->index('tank_id');
                $table->index('shift_id');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nozzle_tests');
    }
};
