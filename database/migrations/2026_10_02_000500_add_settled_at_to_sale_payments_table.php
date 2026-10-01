<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fleet card settlement tracking (K4): OMC (PSO waghera) fleet card
     * ki sale_payments jab company se settle ho jayen to settled_at par
     * timestamp lagta hai. NULL = abhi settlement baqi (pending).
     * Sirf ek bookkeeping timestamp hai — payment row kabhi delete ya
     * amount change nahi hoti (append-only usool barqarar).
     */
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_payments', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sale_payments', 'settled_at')) {
                $table->dropColumn('settled_at');
            }
        });
    }
};
