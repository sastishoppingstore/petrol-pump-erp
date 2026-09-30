<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\System\BackupService;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {
    }

    public function index(Request $request): View
    {
        $backups = Backup::query()->orderByDesc('created_at')->paginate(20);

        return view('backups.index', [
            'backups' => $backups,
            'backupService' => $this->backupService,
        ]);
    }

    public function createDatabase(Request $request): RedirectResponse
    {
        try {
            $backup = $this->backupService->createDatabaseBackup($request->user());
            return redirect()->route('backups.index')
                ->with('success', "Database backup created successfully: {$backup->file_name} ({$backup->formatted_size})");
        } catch (\Throwable $e) {
            return back()->with('error', 'Database backup failed: ' . $e->getMessage());
        }
    }

    public function createFull(Request $request): RedirectResponse
    {
        try {
            $backup = $this->backupService->createFullBackup($request->user());
            return redirect()->route('backups.index')
                ->with('success', "Full ZIP backup created successfully: {$backup->file_name} ({$backup->formatted_size})");
        } catch (\Throwable $e) {
            return back()->with('error', 'Full backup failed: ' . $e->getMessage());
        }
    }

    public function download(Request $request, Backup $backup): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link.');
        }

        if ($request->get('hash') !== $backup->download_hash) {
            abort(403, 'Unauthorized download token.');
        }

        $filePath = storage_path("app/{$backup->file_path}");
        if (! File::exists($filePath)) {
            abort(404, 'Backup file no longer exists on disk.');
        }

        return response()->download($filePath, $backup->file_name);
    }
}
