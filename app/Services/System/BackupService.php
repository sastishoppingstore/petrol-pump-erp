<?php

namespace App\Services\System;

use App\Models\Backup;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\PermissionList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDO;
use Throwable;
use ZipArchive;

class BackupService
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Create a pure-PHP chunked SQL dump of the database (no mysqldump binary required).
     */
    public function createDatabaseBackup(?User $actor = null, bool $compressGzip = true): Backup
    {
        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $ext = $compressGzip ? 'sql.gz' : 'sql';
        $fileName = "backup_{$timestamp}.{$ext}";
        $filePath = "{$backupDir}/{$fileName}";

        $fp = $compressGzip ? gzopen($filePath, 'wb9') : fopen($filePath, 'wb');
        if (! $fp) {
            throw new \RuntimeException("Unable to open backup file for writing at {$filePath}");
        }

        $write = function (string $text) use ($fp, $compressGzip) {
            if ($compressGzip) {
                gzwrite($fp, $text);
            } else {
                fwrite($fp, $text);
            }
        };

        try {
            /** @var PDO $pdo */
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            }

            $write("-- ========================================================\n");
            $write("-- Vital Petroleum ERP Database Backup (Pure PHP Dumper)\n");
            $write("-- Generated at: " . now()->toDateTimeString() . "\n");
            $write("-- Driver: {$driver}\n");
            $write("-- Database: " . config('database.connections.mysql.database') . "\n");
            $write("-- ========================================================\n\n");

            if ($driver === 'mysql') {
                $write("SET FOREIGN_KEY_CHECKS=0;\n");
                $write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
                $write("SET time_zone = '+00:00';\n\n");
            } else {
                $write("PRAGMA foreign_keys = OFF;\n\n");
            }

            // Fetch table names
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            } else {
                $stmt = $pdo->query('SHOW TABLES');
            }
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $stmt->closeCursor();

            foreach ($tables as $table) {
                // Ignore temporary or migration locks if any
                if ($table === 'cache_locks') {
                    continue;
                }

                $write("\n-- --------------------------------------------------------\n");
                $write("-- Table structure for table `{$table}`\n");
                $write("-- --------------------------------------------------------\n");
                $write("DROP TABLE IF EXISTS `{$table}`;\n");

                if ($driver === 'sqlite') {
                    $createStmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
                    $createStmt->execute([$table]);
                    $createTableSql = $createStmt->fetchColumn() ?: '';
                    $createStmt->closeCursor();
                } else {
                    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                    $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                    $createStmt->closeCursor();
                    $createTableSql = $createRow['Create Table'] ?? '';
                }
                $write("{$createTableSql};\n\n");

                // Dump rows in memory-safe chunks
                $write("-- Dumping data for table `{$table}`\n");

                if ($driver === 'sqlite') {
                    $colStmt = $pdo->query("PRAGMA table_info(`{$table}`)");
                    $columns = [];
                    while ($colRow = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = $colRow['name'];
                    }
                    $colStmt->closeCursor();
                } else {
                    $colStmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                    $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
                    $colStmt->closeCursor();
                }
                $colNames = implode('`, `', $columns);

                $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
                $chunkSize = 200;
                $currentChunk = [];

                while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                    $escapedValues = [];
                    foreach ($columns as $col) {
                        $val = $row[$col] ?? null;
                        if ($val === null) {
                            $escapedValues[] = 'NULL';
                        } else {
                            $escapedValues[] = $pdo->quote($val);
                        }
                    }
                    $currentChunk[] = '(' . implode(', ', $escapedValues) . ')';

                    if (count($currentChunk) >= $chunkSize) {
                        $write("INSERT INTO `{$table}` (`{$colNames}`) VALUES\n" . implode(",\n", $currentChunk) . ";\n");
                        $currentChunk = [];
                    }
                }

                if (! empty($currentChunk)) {
                    $write("INSERT INTO `{$table}` (`{$colNames}`) VALUES\n" . implode(",\n", $currentChunk) . ";\n");
                }
                $dataStmt->closeCursor();
            }

            if ($driver === 'mysql') {
                $write("\nSET FOREIGN_KEY_CHECKS=1;\n");
            } else {
                $write("\nPRAGMA foreign_keys = ON;\n");
            }
            $write("-- Dump completed on " . now()->toDateTimeString() . "\n");

            if ($compressGzip) {
                gzclose($fp);
            } else {
                fclose($fp);
            }

            $fileSize = filesize($filePath);
            $downloadHash = Str::random(40);

            $backup = Backup::create([
                'file_name' => $fileName,
                'file_path' => "backups/{$fileName}",
                'file_size' => $fileSize,
                'backup_type' => Backup::TYPE_DATABASE,
                'status' => Backup::STATUS_COMPLETED,
                'download_hash' => $downloadHash,
                'created_by' => $actor?->id ?? auth()->id(),
            ]);

            $this->audit->record(
                userId: $actor?->id ?? 1,
                action: 'backup_create',
                module: 'backup',
                referenceType: Backup::class,
                referenceId: $backup->id,
                newData: ['file' => $fileName, 'size' => $fileSize]
            );

            return $backup;
        } catch (Throwable $e) {
            if ($compressGzip) {
                @gzclose($fp);
            } else {
                @fclose($fp);
            }
            if (File::exists($filePath)) {
                @File::delete($filePath);
            }
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a full backup archive (SQL dump + storage files in a ZIP).
     */
    public function createFullBackup(?User $actor = null): Backup
    {
        $dbBackup = $this->createDatabaseBackup($actor, compressGzip: false);
        $sqlFilePath = storage_path("app/{$dbBackup->file_path}");

        $timestamp = now()->format('Y-m-d_His');
        $zipFileName = "full_backup_{$timestamp}.zip";
        $zipFilePath = storage_path("app/backups/{$zipFileName}");

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create ZIP archive at {$zipFilePath}");
        }

        // Add SQL dump to ZIP
        $zip->addFile($sqlFilePath, basename($sqlFilePath));

        // Add uploaded files from storage/app/public if exists
        $publicStorage = storage_path('app/public');
        if (File::isDirectory($publicStorage)) {
            $files = File::allFiles($publicStorage);
            foreach ($files as $file) {
                $relativeName = 'uploads/' . $file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativeName);
            }
        }

        $zip->close();

        // Delete raw SQL file after bundling into ZIP
        File::delete($sqlFilePath);
        $dbBackup->delete();

        $fileSize = filesize($zipFilePath);
        $downloadHash = Str::random(40);

        return Backup::create([
            'file_name' => $zipFileName,
            'file_path' => "backups/{$zipFileName}",
            'file_size' => $fileSize,
            'backup_type' => Backup::TYPE_FULL,
            'status' => Backup::STATUS_COMPLETED,
            'download_hash' => $downloadHash,
            'created_by' => $actor?->id ?? auth()->id(),
        ]);
    }

    /**
     * Generate secure temporary signed URL for admin download.
     */
    public function getDownloadUrl(Backup $backup, int $expiryMinutes = 120): string
    {
        return URL::temporarySignedRoute(
            'backups.download',
            now()->addMinutes($expiryMinutes),
            [
                'backup' => $backup->id,
                'hash' => $backup->download_hash,
            ]
        );
    }
}
