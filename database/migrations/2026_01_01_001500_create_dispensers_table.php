<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('dispenser_number', 30);
            $table->string('name', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->unsignedTinyInteger('nozzle_count')->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['branch_id', 'dispenser_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensers');
    }
};
