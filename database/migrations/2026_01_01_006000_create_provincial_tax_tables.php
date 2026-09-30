<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pakistan levies sales tax through a provincial authority, and fuel is
     * taxed differently in each province. A station's province therefore
     * decides which authority and which rate apply.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();          // PB, SD, KPK, BA
            $table->string('name', 60);                    // Punjab, Sindh, ...
            // PRA (Punjab), SRB (Sindh), KPRA (KP), BRA (Balochistan).
            $table->string('tax_authority', 10)->unique();
            $table->string('authority_name', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('province_id')
                ->nullable()
                ->after('country')
                ->constrained('provinces')
                ->nullOnDelete();
        });

        /**
         * Sales tax rates, per province and per product class. Append-only
         * history: a rate change adds a row, it never edits one.
         */
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->nullable()->constrained('fuel_products')->cascadeOnDelete();

            // Rate as a percentage, DECIMAL(5,2) — e.g. 16.00 for 16%.
            $table->decimal('rate', 5, 2);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();

            $table->string('source', 60)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['province_id', 'fuel_product_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('province_id');
        });

        Schema::dropIfExists('provinces');
    }
};
