<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock adjustment requests and their approval trail.
     *
     * The tank is only moved when an adjustment is approved (or by a user who
     * already holds the approve permission).
     */
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained('fuel_products')->cascadeOnDelete();

            $table->string('type', 20);   // IN | OUT | LOSS | CORRECTION
            $table->decimal('quantity', 12, 3);
            $table->string('reason', 200);
            $table->text('notes')->nullable();

            $table->decimal('stock_before', 12, 3)->nullable();
            $table->decimal('stock_after', 12, 3)->nullable();

            $table->string('status', 20)->default('PENDING');  // PENDING|APPROVED|REJECTED
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('tank_movement_id')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['branch_id', 'status']);
            $table->index('tank_id');
            $table->index('status');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
