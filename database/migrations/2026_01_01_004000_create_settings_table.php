<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Key/value application settings (spec section 4: `settings`).
     *
     * Two value columns on purpose:
     *   - `value`        VARCHAR, for text (company name, address, email)
     *   - `value_numeric` DECIMAL(14,3), for money, rates and thresholds
     *
     * Storing money in a string column would allow a rounding surprise, and
     * storing text in a decimal column is impossible. Keeping them separate
     * means a threshold like 0.500 keeps all three decimal places.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key', 100);
            $table->string('value', 255)->nullable();
            $table->decimal('value_numeric', 14, 3)->nullable();
            // Which column is authoritative: 'text' or 'number'.
            $table->string('value_type', 10)->default('text');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
