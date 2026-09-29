<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Physical dip readings. Append-only: variance is derived, never overwritten.
     */
    public function up(): void
    {
        Schema::create('tank_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained('fuel_products')->cascadeOnDelete();
            $table->date('reading_date');
            $table->decimal('physical_quantity', 12, 3);
            $table->decimal('expected_quantity', 12, 3);
            // physical - expected, signed.
            $table->decimal('variance_quantity', 12, 3)->default(0);
            $table->string('variance_type', 20)->nullable();
            $table->decimal('dip_height', 12, 3)->nullable();
            $table->decimal('dip_width', 12, 3)->nullable();
            $table->decimal('dip_length', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['tank_id', 'reading_date']);
            $table->index('branch_id');
            $table->index('reading_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_readings');
    }
};
