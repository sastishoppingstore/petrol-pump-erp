<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            // Unique index: the number sequence can never hand out a duplicate,
            // and a second sale with the same number is impossible at the
            // database level too.
            $table->string('invoice_number', 30)->unique();

            $table->foreignId('customer_id')->nullable();
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('sale_date');
            $table->string('sale_mode', 20)->default('LITRES');   // LITRES|AMOUNT

            // Money: DECIMAL(14,2). Litres: DECIMAL(12,3).
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('total_litres', 12, 3)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);

            $table->string('status', 20)->default('COMPLETED');    // COMPLETED|VOIDED|REFUNDED
            $table->text('notes')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('branch_id');
            $table->index('shift_id');
            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('employee_id');
            $table->index('sale_date');
            $table->index('status');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained('fuel_products')->restrictOnDelete();
            $table->foreignId('tank_id')->constrained('tanks')->restrictOnDelete();
            $table->foreignId('dispenser_id')->nullable()->constrained('dispensers')->nullOnDelete();
            $table->foreignId('nozzle_id')->constrained('nozzles')->restrictOnDelete();

            $table->decimal('litres', 12, 3);
            $table->decimal('rate', 10, 2);
            // Historical cost at the moment of sale. Never recalculated.
            $table->decimal('cost_rate', 10, 2)->default(0);
            $table->decimal('amount', 14, 2);
            $table->decimal('cost_amount', 14, 2)->default(0);

            $table->decimal('meter_start', 14, 3);
            $table->decimal('meter_end', 14, 3);

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('sale_id');
            $table->index('nozzle_id');
            $table->index('fuel_product_id');
            $table->index('tank_id');
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('method', 20);   // CASH|CARD|BANK|WALLET|CREDIT
            $table->decimal('amount', 14, 2);
            $table->string('reference', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('sale_id');
            $table->index('method');
        });

        /*
         * Idempotency (spec section 4, step 2). The browser holds a token; a
         * double click or a refresh replays it, and the unique index stops the
         * second sale from being created.
         */
        Schema::create('sale_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_token')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('PROCESSING');  // PROCESSING|COMPLETED|FAILED
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('invoice_number', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_requests');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
