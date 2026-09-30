<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer ledger and customer payments.
     */
    public function up(): void
    {
        Schema::create('customer_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('date');
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255);
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('running_balance', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['branch_id', 'customer_id', 'date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
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

            $table->index(['branch_id', 'customer_id', 'payment_date']);
        });

        // Also add current_balance to customers table if not present
        if (! Schema::hasColumn('customers', 'current_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('current_balance', 14, 2)->default(0)->after('opening_balance');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('customer_ledger');
        if (Schema::hasColumn('customers', 'current_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('current_balance');
            });
        }
    }
};
