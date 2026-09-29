<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only meter history (spec section 3).
     *
     * Type values:
     *   SALE       — normal increment from a fuel sale
     *   OPENING    — recorded when a shift opens
     *   CLOSING    — physical reading at shift close
     *   CORRECTION — authorised meter reset / rollover / correction
     */
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('nozzle_id')->constrained('nozzles')->cascadeOnDelete();
            // The `shifts` table arrives in Phase 4; its foreign key constraint
            // is added there (see the Phase 4 shifts migration). The column
            // exists now because meter readings are written from Phase 2 on.
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('type', 20);
            $table->decimal('previous_meter', 14, 3);
            $table->decimal('current_meter', 14, 3);
            $table->decimal('quantity', 14, 3)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['nozzle_id', 'created_at']);
            $table->index('type');
            $table->index('branch_id');
            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
