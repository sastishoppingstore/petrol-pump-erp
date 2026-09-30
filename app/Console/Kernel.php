<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily closing: 23:30 (after business hours)
        $schedule->command('erp:daily-closing --branch=1')->dailyAt('23:30');

        // Database backup: every day at 2:00 AM
        $schedule->command('erp:backup --retention=7')->dailyAt('02:00');

        // Notification cleanup: every week
        $schedule->command('erp:notifications-cleanup --days=30')->weekly();

        // Sync notifications: every hour
        $schedule->command('erp:sync-notifications')->hourly();

        // Generate auto-reports every hour
        $schedule->command('reports:generate')->hourly();
        
        // Send daily closing reports at configured time (default 23:00)
        $schedule->command('reports:send-daily')
            ->dailyAt('23:00')
            ->runInBackground();
        
        // Clean up old alerts (every 6 hours)
        $schedule->command('alerts:cleanup')->everyFourHours();
        
        // Prune old audit logs (weekly)
        $schedule->command('audit-logs:prune')->weekly();
    }
    
    /**
     * Get configured report send time from settings
     */
    private function getReportSendTime()
    {
        $time = app(\App\Services\Admin\SettingsService::class)
            ->get('report_send_time', '23:00');
        return $time;
    }
}
