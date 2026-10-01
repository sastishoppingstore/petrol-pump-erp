<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amanat — customer prepaid deposits ledger (append-only).
     *
     * Every row is one money movement against a customer's trust deposit:
     * a `deposit` (customer jama karata hai), a `deduction` (fuel sale ya
     * manual adjustment par kata) or an `adjustment` (signed correction).
     * Rows are never updated or deleted; `balance_after` freezes the
     * running balance at the moment of the entry.
     */
    public function up(): void
    {
        if (! Schema::hasTable('amanat_deposits')) {
            Schema::create('amanat_deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->enum('type', ['deposit', 'deduction', 'adjustment']);
                $table->decimal('amount', 14, 2);
                $table->decimal('balance_after', 14, 2);
                $table->string('reference', 100)->nullable();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['branch_id', 'customer_id']);
                $table->index(['customer_id', 'created_at']);
                $table->index('type');
                $table->index('sale_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('amanat_deposits');
    }
};
