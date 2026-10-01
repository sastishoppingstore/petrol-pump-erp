<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-bill type chosen on the bill-create screen (W2):
     * 'fbr'    = FBR Tax Invoice (fiscal record banta hai, D-013 chain)
     * 'simple' = Simple Bill (sirf Invoice record, koi fiscalisation nahi)
     *
     * The global tax.fbr_invoicing_enabled setting only preselects the
     * default on the POS screens; the per-bill choice stored here wins.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'bill_type')) {
                $table->string('bill_type', 10)->default('simple')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'bill_type')) {
                $table->dropColumn('bill_type');
            }
        });
    }
};
