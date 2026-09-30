<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expense categories, expenses, employees, and employee salaries.
     */
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('urdu_name', 100)->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->string('expense_number', 30)->unique();
            $table->date('date');
            $table->string('title', 150);
            $table->decimal('amount', 14, 2)->default(0);
            $table->enum('payment_method', ['CASH', 'BANK_TRANSFER', 'CHEQUE'])->default('CASH');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('payee', 150)->nullable();
            $table->string('receipt_number', 50)->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->enum('status', ['PAID', 'PENDING', 'VOID'])->default('PAID');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'date']);
            $table->index('category_id');
            $table->index('status');
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('designation', 100);
            $table->string('phone', 30)->nullable();
            $table->string('cnic', 30)->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('daily_wage', 14, 2)->default(0);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('month', 10); // YYYY-MM
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['CASH', 'BANK_TRANSFER'])->default('CASH');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->enum('status', ['PAID', 'PENDING'])->default('PAID');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'month']);
            $table->index(['branch_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
