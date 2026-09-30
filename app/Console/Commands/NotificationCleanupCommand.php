<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotificationCleanupCommand extends Command
{
    protected $signature = 'erp:notifications-cleanup {--days=30}';
    protected $description = 'Clean old read notifications (cron job)';

    public function handle()
    {
        try {
            $days = $this->option('days');
            $cutoffDate = now()->subDays($days);

            $deleted = Notification::where('read_at', '!=', null)
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $this->info("✓ Deleted {$deleted} old read notifications (older than {$days} days).");
            Log::info('Notification cleanup completed', ['deleted_count' => $deleted, 'days' => $days]);
            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Cleanup failed: {$e->getMessage()}");
            Log::error('Notification cleanup failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }
}
