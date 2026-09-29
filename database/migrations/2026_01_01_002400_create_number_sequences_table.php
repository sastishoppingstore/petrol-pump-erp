<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document number sequences (spec section 4, step 7).
     *
     * One row per prefix per year. The counter is advanced inside a
     * transaction with SELECT ... FOR UPDATE, so two concurrent sales can
     * never be handed the same invoice number.
     */
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->year('year');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedTinyInteger('digits')->default(6);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
