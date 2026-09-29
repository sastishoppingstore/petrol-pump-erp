<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shift cash movements: handovers, drops, float additions (spec section 3 & 6).
     */
    public function up(): void
    {
        Schema::create('shift_cash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // FLOAT_ADDITION, DROP, HANDOVER, EXPENSE_PAYOUT
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shift_id', 'type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_cash');
    }
};
