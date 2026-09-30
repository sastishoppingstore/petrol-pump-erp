<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pakistani Petrol Pump Chart of Accounts and Double-Entry General Ledger.
     */
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('code', 20)->unique();
                $table->string('name', 100);
                $table->string('urdu_name', 100)->nullable();
                $table->enum('type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']);
                $table->string('subcategory', 50)->nullable();
                $table->enum('normal_balance', ['DEBIT', 'CREDIT']);
                $table->boolean('is_system')->default(false);
                $table->string('status', 20)->default('ACTIVE');
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->decimal('current_balance', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('branch_id');
                $table->index('type');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('entry_number', 30)->unique();
                $table->date('date');
                $table->string('reference_type', 80)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('narration');
                $table->enum('status', ['POSTED', 'VOID'])->default('POSTED');
                $table->boolean('is_closing_entry')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at');
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('void_reason')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'date']);
                $table->index(['reference_type', 'reference_id']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->decimal('debit', 14, 2)->default(0);
                $table->decimal('credit', 14, 2)->default(0);
                $table->string('memo', 255)->nullable();
                $table->timestamps();

                $table->index('journal_entry_id');
                $table->index('account_id');
            });
        }

        if (! Schema::hasTable('financial_period_locks')) {
            Schema::create('financial_period_locks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->date('locked_until_date');
                $table->string('reason', 255)->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_locked')->default(true);
                $table->timestamps();

                $table->index(['branch_id', 'locked_until_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_period_locks');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
