<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cash_entries')) {
            Schema::create('cash_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->string('voucher_number', 30)->unique();
                $table->string('type', 20); // CASH_IN | CASH_OUT
                $table->string('category', 60);
                $table->decimal('amount', 14, 2);
                $table->string('person_name', 150);
                $table->string('reference_no', 100)->nullable();
                $table->string('attachment_path')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 20)->default('APPROVED');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('entry_date');
                $table->timestamps();

                $table->index(['branch_id', 'entry_date']);
                $table->index(['shift_id', 'type']);
                $table->index('type');
                $table->index('category');
                $table->index('voucher_number');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
