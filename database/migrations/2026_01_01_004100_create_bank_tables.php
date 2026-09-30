<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference data: every bank a station could plausibly deposit into.
        // Seeded from the State Bank / Banking Mohtasib lists, and editable by
        // an admin so a new scheduled bank can be added without a release.
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('short_name', 20);
            $table->string('bank_type', 20)->default('COMMERCIAL');  // COMMERCIAL|ISLAMIC|PUBLIC|DIGITAL
            $table->string('branch_name', 150)->nullable();
            $table->string('branch_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('ntn', 30)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['name', 'branch_name']);
            $table->index('bank_type');
            $table->index('status');
        });

        // The station's own accounts. A deposit is always made into one of these.
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();

            $table->string('account_title', 150);
            $table->string('account_number', 30);
            $table->string('iban', 34)->nullable();
            $table->string('account_type', 20)->default('CURRENT');  // CURRENT|SAVINGS
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('status', 20)->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['bank_id', 'account_number']);
            $table->index('branch_id');
            $table->index('status');
        });

        // Deposits: cash leaving the shift till into a bank account.
        Schema::create('bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            // Kept so the bank name survives an account rename.
            $table->string('bank_name', 150);

            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            // Money: DECIMAL(14,2).
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2)->nullable();
            $table->decimal('balance_after', 14, 2)->nullable();

            $table->string('reference_number', 50);
            $table->dateTime('deposited_at');
            $table->text('reason')->nullable();
            $table->string('deposit_type', 20)->default('CASH');  // CASH|CHEQUE

            $table->foreignId('deposited_by')->nullable()->constrained('users')->nullOnDelete();
            // Uploaded deposit slip, stored outside public/.
            $table->string('slip_path', 255)->nullable();

            $table->string('status', 20)->default('COMPLETED');  // COMPLETED|PENDING_APPROVAL|REVERSED
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('branch_id');
            $table->index('bank_account_id');
            $table->index('shift_id');
            $table->index('deposited_at');
            $table->index('reference_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('banks');
    }
};
