<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document Vault — station compliance documents (OGRA licence,
     * dealership agreement, NOCs, calibration certificates) with
     * issue/expiry tracking so nothing lapses unnoticed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('station_documents')) {
            Schema::create('station_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('title', 200);
                $table->enum('category', ['ogra_licence', 'dealership', 'noc', 'calibration', 'other'])->default('other');
                $table->string('file_path', 255);
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['branch_id', 'category']);
                $table->index('expiry_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('station_documents');
    }
};
