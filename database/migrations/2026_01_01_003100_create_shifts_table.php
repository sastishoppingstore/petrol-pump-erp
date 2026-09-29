<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shifts table (spec section 6).
     *
     * Only one OPEN shift per employee and per nozzle.
     * Cash difference = actual_cash - expected_cash.
     * When |difference| > shift_variance_threshold, approval is required.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('shift_number', 32)->unique();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // Financials (exact decimals, never float)
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('cash_difference', 14, 2)->nullable();
            $table->decimal('card_total', 14, 2)->default(0);
            $table->decimal('credit_total', 14, 2)->default(0);
            $table->decimal('other_total', 14, 2)->default(0);
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->decimal('total_litres', 12, 3)->default(0);
            $table->decimal('expenses_total', 14, 2)->default(0);
            $table->decimal('cash_drops_total', 14, 2)->default(0);

            // Lifecycle
            $table->string('status', 20)->default('OPEN'); // OPEN, CLOSED, PENDING_APPROVAL
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('opened_at');
        });

        // Add foreign key constraint to meter_readings.shift_id now that shifts exists
        if (Schema::hasTable('meter_readings') && Schema::hasColumn('meter_readings', 'shift_id')) {
            Schema::table('meter_readings', function (Blueprint $table) {
                $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('meter_readings') && Schema::hasColumn('meter_readings', 'shift_id')) {
            Schema::table('meter_readings', function (Blueprint $table) {
                $table->dropForeign(['shift_id']);
            });
        }

        Schema::dropIfExists('shifts');
    }
};
