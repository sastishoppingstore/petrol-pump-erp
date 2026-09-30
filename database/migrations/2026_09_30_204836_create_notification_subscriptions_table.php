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
        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('notification_type')->index(); // low_stock, variance, overdue_credit, etc.
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('threshold', 12, 2)->nullable(); // For variance, stock level alerts
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->unique(['user_id', 'branch_id', 'notification_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_subscriptions');
    }
};
