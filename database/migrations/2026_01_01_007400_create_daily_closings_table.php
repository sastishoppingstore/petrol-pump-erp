<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily Closing records, checklist verification, variance tracking and date locks.
     */
    public function up(): void
    {
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('closing_date');
            $table->string('closing_number', 30)->unique();
            $table->enum('status', ['PENDING', 'CLOSED', 'LOCKED'])->default('PENDING');

            // Sales summary
            $table->decimal('total_fuel_litres', 12, 3)->default(0);
            $table->decimal('total_fuel_sales', 14, 2)->default(0);
            $table->decimal('total_lube_sales', 14, 2)->default(0);
            $table->decimal('total_sales_amount', 14, 2)->default(0);
            $table->decimal('total_cash_sales', 14, 2)->default(0);
            $table->decimal('total_credit_sales', 14, 2)->default(0);
            $table->decimal('total_card_sales', 14, 2)->default(0);

            // Inflows & outflows
            $table->decimal('total_customer_receipts', 14, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('total_bank_deposits', 14, 2)->default(0);
            $table->decimal('total_supplier_payments', 14, 2)->default(0);

            // Cash reconciliation
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash_counted', 14, 2)->default(0);
            $table->decimal('cash_variance', 14, 2)->default(0);

            // Stock dip reconciliation
            $table->decimal('total_opening_stock', 12, 3)->default(0);
            $table->decimal('total_purchases_stock', 12, 3)->default(0);
            $table->decimal('total_sales_stock', 12, 3)->default(0);
            $table->decimal('expected_dip_stock', 12, 3)->default(0);
            $table->decimal('actual_dip_stock', 12, 3)->default(0);
            $table->decimal('dip_variance_litres', 12, 3)->default(0);

            // Checklist verification
            $table->unsignedInteger('shifts_count')->default(0);
            $table->boolean('shifts_verified')->default(false);
            $table->boolean('tank_dips_verified')->default(false);
            $table->boolean('cash_verified')->default(false);
            $table->boolean('bank_deposits_verified')->default(false);

            // Approval & email
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_recipient', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'closing_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
