<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backups table.
     */
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 150);
            $table->string('file_path', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('backup_type', ['DATABASE', 'FULL'])->default('DATABASE');
            $table->enum('status', ['COMPLETED', 'FAILED'])->default('COMPLETED');
            $table->string('download_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
