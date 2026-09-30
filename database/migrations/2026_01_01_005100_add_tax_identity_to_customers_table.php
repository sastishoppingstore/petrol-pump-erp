<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FBR requires the buyer's identity on an invoice when the buyer is
     * tax-liable or the invoice exceeds Rs.100,000, so a customer needs an
     * NTN and a CNIC to be identified.
     */
    public function up(): void
    {
        // ntn_number already exists on customers from the Phase 5 migration;
        // only the CNIC and the tax-liability flag are new here.
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cnic', 20)->nullable()->after('ntn_number');
            $table->boolean('is_tax_liable')->default(false)->after('cnic');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cnic', 'is_tax_liable']);
        });
    }
};
