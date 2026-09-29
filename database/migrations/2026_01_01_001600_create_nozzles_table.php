<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nozzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('dispenser_id')->constrained('dispensers')->cascadeOnDelete();
            // The tank this nozzle draws from.
            $table->foreignId('tank_id')->constrained('tanks')->restrictOnDelete();
            // Must equal the tank's fuel — enforced in NozzleService, not just here.
            $table->foreignId('fuel_product_id')->constrained('fuel_products')->restrictOnDelete();
            $table->string('nozzle_number', 20);

            // Meter readings: DECIMAL(14,3) per spec section 2.
            $table->decimal('opening_meter', 14, 3)->default(0);
            // Incremented by SaleService; only ever moves upward.
            $table->decimal('current_meter', 14, 3)->default(0);
            $table->decimal('meter_multiplier', 10, 3)->default(1);

            $table->string('status', 20)->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['dispenser_id', 'nozzle_number']);
            $table->index('branch_id');
            $table->index('tank_id');
            $table->index('fuel_product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nozzles');
    }
};
