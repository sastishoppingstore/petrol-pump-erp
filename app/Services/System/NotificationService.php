<?php

namespace App\Services\System;

use App\Models\Notification;
use App\Models\Tank;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;

/**
 * Notifications (Phase 3 low stock, expanded in Phase 10).
 *
 * Every notification carries a dedupe_key that is unique per user, so a
 * repeating condition cannot spam the bell. The spec requires a low-stock
 * alert once per tank per day.
 */
class NotificationService
{
    /**
     * Raise a notification unless the same key already exists for this user.
     *
     * The daily rule is expressed in the key itself ("tank:5:2026-09-29"),
     * so the unique index does the de-duplication rather than a timestamp
     * comparison that a concurrent request could race.
     */
    public function notifyOnce(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $dedupeKey,
        string $level = Notification::LEVEL_INFO,
        ?string $module = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): ?Notification {
        // Concurrency-safe: the unique index rejects a duplicate insert.
        try {
            return Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => mb_substr($title, 0, 200),
                'message' => $message,
                'level' => $level,
                'module' => $module,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'dedupe_key' => $dedupeKey,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate key = already notified. Not an error.
            return null;
        }
    }

    /**
     * Alert every user who should see a low-stock tank — once per tank per day.
     */
    public function lowStock(Tank $tank, ?int $excludeUserId = null): int
    {
        $threshold = Quantity::n($tank->low_stock_threshold);

        if (Quantity::isZero($threshold)) {
            return 0;
        }

        if (Quantity::compare(Quantity::n($tank->current_stock), $threshold) > 0) {
            return 0;
        }

        $recipients = $this->recipients($excludeUserId);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $dayKey = now()->toDateString();
        $created = 0;

        $message = sprintf(
            'Tank %s (%s) has %s L remaining, at or below the %s L alert level.',
            $tank->tank_number,
            $tank->fuelProduct?->name ?? 'unknown fuel',
            Quantity::format($tank->current_stock),
            Quantity::format($threshold),
        );

        foreach ($recipients as $userId) {
            $notification = $this->notifyOnce(
                userId: $userId,
                type: Notification::TYPE_LOW_STOCK,
                title: 'Low stock: '.$tank->tank_number,
                message: $message,
                // Once per tank per day.
                dedupeKey: 'low-stock:tank:'.$tank->id.':'.$dayKey,
                level: Notification::LEVEL_WARNING,
                module: 'stock',
                referenceType: Tank::class,
                referenceId: $tank->id,
            );

            if ($notification) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Users who should receive stock alerts: managers and admins.
     */
    private function recipients(?int $excludeUserId)
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.name', ['ADMIN', 'MANAGER'])
            ->when($excludeUserId, fn ($q) => $q->where('user_roles.user_id', '!=', $excludeUserId))
            ->distinct()
            ->pluck('user_roles.user_id');
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    public function markRead(int $notificationId, int $userId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['read_at' => now()]);
    }

    public function markAllRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
