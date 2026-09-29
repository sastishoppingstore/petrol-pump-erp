<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            // The attendant on duty. Users are the staff record until Phase 8
            // adds the HR-specific employees table.
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('shift_number', 30)->unique();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Money: DECIMAL(14,2) per spec section 2.
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->nullable();
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('cash_difference', 14, 2)->nullable();
            $table->decimal('card_settlement', 14, 2)->default(0);

            // Live totals for the active-shift screen, maintained as documents
            // are posted. They are a convenience read; the authoritative
            // figures are always the underlying sales/expense rows.
            $table->decimal('card_total', 14, 2)->default(0);
            $table->decimal('credit_total', 14, 2)->default(0);
            $table->decimal('other_total', 14, 2)->default(0);
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->decimal('total_litres', 12, 3)->default(0);
            $table->decimal('expenses_total', 14, 2)->default(0);
            $table->decimal('cash_drops_total', 14, 2)->default(0);

            $table->string('status', 20)->default('OPEN');  // OPEN|CLOSED|PENDING_APPROVAL
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['branch_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index('status');
        });

        Schema::create('shift_nozzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('nozzle_id')->constrained('nozzles')->cascadeOnDelete();

            // Meters are DECIMAL(14,3). The opening value is copied from the
            // nozzle at open time so the closing variance is measurable even
            // if the nozzle is later edited.
            $table->decimal('opening_meter', 14, 3);
            $table->decimal('closing_meter', 14, 3)->nullable();
            $table->decimal('sold_litres', 14, 3)->default(0);
            // Litres the nozzle meter advanced by, as an independent check on
            // sold_litres.
            $table->decimal('system_litres', 12, 3)->default(0);
            $table->decimal('meter_variance', 14, 3)->default(0);
            $table->boolean('variance_flagged')->default(false);
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['shift_id', 'nozzle_id']);
            $table->index('nozzle_id');
        });

        Schema::create('shift_cash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            // Opening float, cash sales, payments, expenses, drops, closing count.
            $table->string('entry_type', 30);
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['shift_id', 'entry_type']);
        });

        // The meter_readings.shift_id foreign key was deferred in Phase 2
        // because the shifts table did not exist yet.
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
        });

        Schema::dropIfExists('shift_cash');
        Schema::dropIfExists('shift_nozzles');
        Schema::dropIfExists('shifts');
    }
};
