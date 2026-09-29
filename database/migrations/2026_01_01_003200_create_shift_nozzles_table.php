<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nozzle assignments per shift (spec section 6).
     *
     * Tracks opening meter, closing meter, and variance.
     */
    public function up(): void
    {
        Schema::create('shift_nozzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('nozzle_id')->constrained('nozzles')->restrictOnDelete();
            $table->decimal('opening_meter', 14, 3);
            $table->decimal('closing_meter', 14, 3)->nullable();
            $table->decimal('meter_sales_litres', 12, 3)->default(0);
            $table->decimal('system_litres', 12, 3)->default(0);
            $table->decimal('meter_variance', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'nozzle_id']);
            $table->index('nozzle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_nozzles');
    }
};
