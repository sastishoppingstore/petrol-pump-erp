<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer documents — CNIC copies, NTN/tax documents and other
 * customer paperwork uploaded from the customer khata (profile) page.
 * Files live on the `public` disk (project pattern, same as the
 * station Document Vault) and are metadata-only rows here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_documents')) {
            return;
        }

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('category', 30)->default('other');
            $table->string('file_path');
            $table->string('note', 500)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
