<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customers are introduced alongside sales because a credit sale must be
     * able to reference and credit-limit-check a customer. The customer UI,
     * ledger and payments are built in Phase 6.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('ntn_number', 30)->nullable();

            // Money: DECIMAL(14,2). Zero means "no limit".
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('status', 20)->default('ACTIVE');
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('branch_id');
            $table->index('status');
            $table->index('name');
        });

        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('registration_number', 30);
            $table->string('make', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('colour', 50)->nullable();
            $table->string('type', 30)->default('CAR');
            $table->decimal('tank_capacity', 12, 3)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // A registration is unique per customer, not globally: the same
            // plate may legitimately appear for two accounts in edge cases.
            $table->unique(['customer_id', 'registration_number']);
            $table->index('registration_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_vehicles');
        Schema::dropIfExists('customers');
    }
};
