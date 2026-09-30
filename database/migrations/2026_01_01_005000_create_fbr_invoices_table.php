<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FBR fiscalised invoices (SRO 1006(I)/2021).
     *
     * Kept separate from `sales` because the FBR document has its own
     * lifecycle: it is submitted, it can be rejected, and it is never
     * re-issued with the same fiscal number.
     */
    public function up(): void
    {
        Schema::create('fbr_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            // The six-character POS branch code printed on the invoice.
            $table->string('pos_branch_code', 6);

            // XXXXXX-DDMMYYHHMMSS-0001 — unique by FBR's own definition.
            $table->string('fiscal_number', 30)->unique();

            // QR payload, stored so a reprinted invoice produces the same code.
            $table->json('qr_payload')->nullable();

            $table->string('buyer_ntn', 30)->nullable();
            $table->string('buyer_cnic', 20)->nullable();
            $table->string('buyer_name', 150)->nullable();
            // True when the buyer had to be identified because the invoice
            // exceeded Rs.100,000 or the buyer is tax-liable.
            $table->boolean('buyer_details_required')->default(false);

            // Money: DECIMAL(14,2).
            $table->decimal('total_amount', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('pos_service_fee', 14, 2)->default(1);

            // DRAFT -> SUBMITTED -> ACCEPTED, or REJECTED. Never re-issued.
            $table->string('status', 20)->default('DRAFT');
            $table->timestamp('submitted_at')->nullable();
            $table->string('fbr_response', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('sale_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('buyer_ntn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_invoices');
    }
};
