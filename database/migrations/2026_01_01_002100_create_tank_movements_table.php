<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only stock ledger (spec section 3).
     *
     * Every row records the quantity before and after the move, so the
     * balance can always be re-derived and audited. Rows are never updated
     * or deleted; a correction is a new row.
     */
    public function up(): void
    {
        Schema::create('tank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained('fuel_products')->cascadeOnDelete();

            $table->string('type', 20);
            // Signed: in movements positive, out movements negative.
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_quantity', 12, 3);
            $table->decimal('after_quantity', 12, 3);

            // Polymorphic link back to the document that caused the move.
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['tank_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('branch_id');
            $table->index('fuel_product_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_movements');
    }
};
