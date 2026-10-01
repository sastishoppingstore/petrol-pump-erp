<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shift handover sign-off (K3): jab ek cashier shift band karta hai,
     * agli shift khulne se pehle incoming cashier ko drawer ka cash gin
     * kar apne PIN se handover accept karna hota hai. Ye teen columns
     * usi acceptance ka record hain:
     *  - handed_over_to        kis ne handover accept kiya (incoming cashier)
     *  - handover_accepted_at kab accept hua
     *  - handover_cash_counted incoming ne kitna cash gina (Rs.)
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'handed_over_to')) {
                $table->foreignId('handed_over_to')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('shifts', 'handover_accepted_at')) {
                $table->timestamp('handover_accepted_at')->nullable()->after('handed_over_to');
            }

            if (! Schema::hasColumn('shifts', 'handover_cash_counted')) {
                $table->decimal('handover_cash_counted', 14, 2)->nullable()->after('handover_accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'handed_over_to')) {
                $table->dropForeign(['handed_over_to']);
            }

            $columns = array_values(array_filter(
                ['handed_over_to', 'handover_accepted_at', 'handover_cash_counted'],
                fn (string $column) => Schema::hasColumn('shifts', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
