<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail (spec section 6).
 *
 * There is deliberately no update() and no delete(). A row written here
 * cannot be edited or removed through application code. The deployment docs
 * tell the DBA to revoke UPDATE and DELETE on this table for the app's
 * database user as a second layer.
 */
class AuditLogService
{
    public function log(
        \App\Models\User|int|null $user,
        string $action,
        string $module,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        $userId = $user instanceof \App\Models\User ? $user->id : $user;
        $this->record($userId, $action, $module, $referenceType, $referenceId, $oldData, $newData, $ip, $userAgent);
    }

    public function record(
        ?int $userId,
        string $action,
        string $module,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        try {
            // The table may not exist yet during a fresh install.
            if (! Schema::hasTable('audit_logs')) {
                return;
            }

            \DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'old_data' => $oldData ? json_encode($oldData) : null,
                'new_data' => $newData ? json_encode($newData) : null,
                'ip_address' => $ip ?? request()->ip(),
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : mb_substr((string) request()->userAgent(), 0, 255),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // An audit failure must never break the business operation, but it
            // must be loud in the log so it can be investigated.
            Log::error('Audit log write failed', [
                'action' => $action,
                'module' => $module,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
