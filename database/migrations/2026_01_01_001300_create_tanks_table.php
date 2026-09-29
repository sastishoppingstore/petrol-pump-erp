<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained('fuel_products')->restrictOnDelete();
            $table->string('tank_number', 30);
            $table->string('name', 100)->nullable();

            // Litres: DECIMAL(12,3) per spec section 2.
            $table->decimal('capacity', 12, 3)->default(0);
            $table->decimal('min_level', 12, 3)->default(0);
            $table->decimal('max_level', 12, 3)->default(0);
            $table->decimal('opening_stock', 12, 3)->default(0);
            // Only ever changed by StockService::move() under a row lock.
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->default(0);

            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // A branch cannot have two tanks with the same number.
            $table->unique(['branch_id', 'tank_number']);
            $table->index('fuel_product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};
