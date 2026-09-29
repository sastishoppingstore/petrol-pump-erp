<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ERP-specific user columns, added on top of the Laravel skeleton
     * users table (0001_01_01_000000_create_users_table).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 30)->nullable()->after('id');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('status', 20)->default('ACTIVE')->after('password');
            $table->boolean('must_change_password')->default(false)->after('status');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_ip');
            $table->unsignedInteger('login_attempt_count')->default(0)->after('password_changed_at');

            $table->index('status');
            $table->index('employee_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['employee_code']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'employee_code',
                'phone',
                'status',
                'must_change_password',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
                'login_attempt_count',
            ]);
        });
    }
};
