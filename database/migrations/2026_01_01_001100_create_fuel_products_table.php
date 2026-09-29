<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            // LITRE is the only realistic unit, but it is data, not an assumption.
            $table->string('unit', 20)->default('LITRE');
            $table->string('color', 20)->nullable();

            // Current selling price, DECIMAL(10,2) per spec section 2.
            $table->decimal('selling_price', 10, 2)->default(0);
            // Weighted average cost, maintained by PurchaseService in Phase 7.
            $table->decimal('average_cost', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->boolean('price_requires_approval')->default(false);
            $table->string('status', 20)->default('ACTIVE');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_products');
    }
};
