<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suppliers, fuel decantation purchases, and supplier ledgers.
     */
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('code', 30)->unique();
                $table->string('name', 150);
                $table->string('contact_person', 100)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->text('address')->nullable();
                $table->string('ntn_number', 30)->nullable();
                $table->string('strn_number', 30)->nullable();
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->decimal('current_balance', 14, 2)->default(0);
                $table->string('status', 20)->default('ACTIVE');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('branch_id');
                $table->index('status');
                $table->index('name');
            });
        }

        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('purchase_number', 30)->unique();
            $table->string('invoice_number', 50)->nullable();
            $table->date('purchase_date');
            $table->foreignId('tank_id')->nullable()->constrained('tanks')->nullOnDelete();
            $table->foreignId('fuel_product_id')->nullable()->constrained('fuel_products')->nullOnDelete();
            $table->decimal('volume_ordered', 12, 3)->default(0);
            $table->decimal('volume_received', 12, 3)->default(0);
            $table->decimal('density', 8, 4)->nullable();
            $table->decimal('temperature', 6, 2)->nullable();
            $table->string('tanker_number', 30)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->decimal('purchase_rate', 10, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('freight_charges', 14, 2)->default(0);
            $table->decimal('other_charges', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->enum('payment_status', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            $table->enum('status', ['RECEIVED', 'APPROVED', 'VOID'])->default('RECEIVED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'purchase_date']);
            $table->index('supplier_id');
            $table->index('status');
        });
        }

        if (! Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('product_name', 150);
            $table->enum('item_type', ['FUEL', 'LUBE', 'GENERAL'])->default('FUEL');
            $table->foreignId('fuel_product_id')->nullable()->constrained('fuel_products')->nullOnDelete();
            $table->foreignId('tank_id')->nullable()->constrained('tanks')->nullOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index('purchase_id');
        });
        }

        if (! Schema::hasTable('supplier_ledger')) {
            Schema::create('supplier_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('date');
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255);
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('running_balance', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['branch_id', 'supplier_id', 'date']);
            $table->index(['reference_type', 'reference_id']);
        });
        }

        if (! Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->string('payment_number', 30)->unique();
            $table->date('payment_date');
            $table->enum('payment_method', ['CASH', 'BANK_TRANSFER', 'CHEQUE'])->default('CASH');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->enum('cheque_status', ['PENDING', 'CLEARED', 'BOUNCED'])->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'supplier_id', 'payment_date']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_ledger');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
