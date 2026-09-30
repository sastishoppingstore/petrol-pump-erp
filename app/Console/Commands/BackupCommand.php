<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupCommand extends Command
{
    protected $signature = 'erp:backup {--retention=7}';
    protected $description = 'Backup database and storage (cron job)';

    public function handle()
    {
        try {
            $this->info('Starting backup...');

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupDir = storage_path('backups');

            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // MySQL backup
            $dbBackup = $backupDir . "/database_{$timestamp}.sql";
            $dbCommand = sprintf(
                "mysqldump -u %s -p%s %s > %s",
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_DATABASE'),
                $dbBackup
            );

            Process::run($dbCommand);
            $this->info("✓ Database backed up: {$dbBackup}");

            // Gzip compress
            Process::run("gzip {$dbBackup}");
            $this->info("✓ Compressed: {$dbBackup}.gz");

            // Clean old backups (retention)
            $this->cleanOldBackups($backupDir, $this->option('retention'));

            Log::info('Backup completed successfully', ['backup_dir' => $backupDir]);
            $this->info('✓ Backup completed.');
            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Backup failed: {$e->getMessage()}");
            Log::error('Backup failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    protected function cleanOldBackups($backupDir, $retentionDays)
    {
        $files = scandir($backupDir);
        $cutoffDate = now()->subDays($retentionDays)->timestamp;

        foreach ($files as $file) {
            $filePath = $backupDir . '/' . $file;
            if (is_file($filePath) && filemtime($filePath) < $cutoffDate) {
                unlink($filePath);
                $this->line("Deleted old backup: {$file}");
            }
        }
    }
}
