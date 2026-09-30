<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bank Transactions (Bank Book / Ledger)
        if (! Schema::hasTable('bank_transactions')) {
            Schema::create('bank_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
                $table->enum('type', [
                    'DEPOSIT', 'WITHDRAWAL', 'TRANSFER_IN', 'TRANSFER_OUT',
                    'CHARGES', 'MARKUP', 'CHEQUE_DEPOSIT', 'CHEQUE_BOUNCE'
                ]);
                $table->decimal('amount', 14, 2);
                $table->decimal('balance_before', 14, 2)->default(0);
                $table->decimal('balance_after', 14, 2)->default(0);
                $table->string('reference_number', 50)->nullable();
                $table->dateTime('transaction_date');
                $table->string('description', 255)->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('slip_path', 255)->nullable();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->foreignId('related_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->boolean('reconciled')->default(false);
                $table->dateTime('reconciled_at')->nullable();
                $table->unsignedBigInteger('reconciliation_id')->nullable();
                $table->string('status', 20)->default('COMPLETED');
                $table->timestamps();

                $table->index(['bank_account_id', 'transaction_date']);
                $table->index(['branch_id', 'transaction_date']);
                $table->index('type');
                $table->index('reference_number');
                $table->index('reconciled');
            });
        }

        // 2. Bank Reconciliations
        if (! Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
                $table->date('statement_date');
                $table->decimal('statement_balance', 14, 2);
                $table->decimal('ledger_balance', 14, 2);
                $table->decimal('difference', 14, 2)->default(0);
                $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('statement_file_path', 255)->nullable();
                $table->string('status', 20)->default('COMPLETED');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['bank_account_id', 'statement_date']);
            });
        }

        // 3. Cheques Register
        if (! Schema::hasTable('cheques')) {
            Schema::create('cheques', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->enum('type', ['RECEIVED', 'ISSUED']);
                $table->string('cheque_number', 50);
                $table->string('bank_name', 100);
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('payee_name', 150)->nullable();
                $table->decimal('amount', 14, 2);
                $table->date('cheque_date');
                $table->date('due_date');
                $table->boolean('is_pdc')->default(false);
                $table->enum('status', ['RECEIVED', 'DEPOSITED', 'CLEARED', 'BOUNCED', 'CANCELLED'])->default('RECEIVED');
                $table->date('deposit_date')->nullable();
                $table->date('cleared_date')->nullable();
                $table->date('bounced_date')->nullable();
                $table->string('bounce_reason', 255)->nullable();
                $table->decimal('bank_charges', 14, 2)->default(0);
                $table->string('image_path', 255)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['branch_id', 'status']);
                $table->index(['type', 'due_date']);
                $table->index('cheque_number');
                $table->index('customer_id');
                $table->index('supplier_id');
            });
        }

        // 4. Employee Attendances
        if (! Schema::hasTable('employee_attendances')) {
            Schema::create('employee_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('date');
                $table->enum('status', ['PRESENT', 'ABSENT', 'LEAVE', 'HALF_DAY'])->default('PRESENT');
                $table->time('in_time')->nullable();
                $table->time('out_time')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['employee_id', 'date']);
                $table->index(['branch_id', 'date']);
            });
        }

        // 5. Employee Advances (Loans)
        if (! Schema::hasTable('employee_advances')) {
            Schema::create('employee_advances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->decimal('amount', 14, 2);
                $table->decimal('balance', 14, 2);
                $table->decimal('monthly_deduction', 14, 2)->default(0);
                $table->date('advance_date');
                $table->enum('payment_method', ['CASH', 'BANK_TRANSFER'])->default('CASH');
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->string('reason', 255)->nullable();
                $table->enum('status', ['ACTIVE', 'RECOVERED', 'CANCELLED'])->default('ACTIVE');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['employee_id', 'status']);
                $table->index(['branch_id', 'advance_date']);
            });
        }

        // 6. Employee Adjustments (Overtime, bonuses, fines, shortage recovery)
        if (! Schema::hasTable('employee_adjustments')) {
            Schema::create('employee_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->enum('type', ['OVERTIME', 'BONUS', 'FINE', 'SHORTAGE_RECOVERY']);
                $table->decimal('amount', 14, 2);
                $table->date('effective_date');
                $table->string('payroll_month', 7);
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->string('reason', 255);
                $table->boolean('is_applied')->default(false);
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['employee_id', 'payroll_month']);
                $table->index('type');
            });
        }

        // 7. Update employee_salaries table to support detailed breakdown
        if (Schema::hasTable('employee_salaries')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_salaries', 'present_days')) {
                    $table->unsignedSmallInteger('present_days')->default(0)->after('month');
                    $table->unsignedSmallInteger('absent_days')->default(0)->after('present_days');
                    $table->unsignedSmallInteger('half_days')->default(0)->after('absent_days');
                    $table->unsignedSmallInteger('leave_days')->default(0)->after('half_days');
                    $table->decimal('overtime_amount', 14, 2)->default(0)->after('basic_salary');
                    $table->decimal('bonus_amount', 14, 2)->default(0)->after('overtime_amount');
                    $table->decimal('fine_amount', 14, 2)->default(0)->after('bonus_amount');
                    $table->decimal('advance_deduction', 14, 2)->default(0)->after('fine_amount');
                    $table->foreignId('shift_id')->nullable()->after('bank_account_id')->constrained('shifts')->nullOnDelete();
                }
            });
        }

        // 8. Approval Requests
        if (! Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->enum('request_type', [
                    'METER_CORRECTION',
                    'CREDIT_OVERRIDE',
                    'CASH_SHORTAGE',
                    'LARGE_CASHOUT',
                    'STOCK_ADJUSTMENT',
                    'EXPENSE',
                    'ADVANCE'
                ]);
                $table->string('title', 150);
                $table->text('description');
                $table->decimal('amount', 14, 2)->nullable();
                $table->string('reference_type', 100)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('payload')->nullable();
                $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('actioned_at')->nullable();
                $table->text('action_reason')->nullable();
                $table->string('whatsapp_url', 500)->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'status']);
                $table->index('request_type');
                $table->index(['reference_type', 'reference_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
        if (Schema::hasTable('employee_salaries') && Schema::hasColumn('employee_salaries', 'present_days')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn([
                    'present_days', 'absent_days', 'half_days', 'leave_days',
                    'overtime_amount', 'bonus_amount', 'fine_amount', 'advance_deduction', 'shift_id'
                ]);
            });
        }
        Schema::dropIfExists('employee_adjustments');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('employee_attendances');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
    }
};
