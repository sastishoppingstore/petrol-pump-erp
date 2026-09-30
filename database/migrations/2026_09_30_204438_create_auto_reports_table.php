<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auto_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->enum('period', ['12h', '24h', '7d', '15d', '30d'])->index();
            $table->date('report_date')->index();
            $table->text('summary_json'); // Sales, fuel, revenue, expenses, margin
            $table->text('data_json')->nullable(); // Full report data (large)
            $table->string('status')->default('generated'); // generated, sent_email, sent_sms
            $table->integer('pdf_size_bytes')->nullable();
            $table->integer('excel_size_bytes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->text('recipient_email')->nullable(); // JSON array of emails
            $table->text('recipient_phone')->nullable(); // JSON array of phones
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('sent_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['branch_id', 'period', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_reports');
    }
};
