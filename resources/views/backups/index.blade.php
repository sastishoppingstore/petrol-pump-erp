@extends('layouts.app')

@section('title', 'Backup & Restore — بیک اپ سسٹم')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="h3 fw-bold mb-1">System Backup & Security Archive</h2>
            <div class="text-muted" style="font-family: 'Jameel Noori Nastaleeq', Tahoma;">
                ڈیٹابیس اور اٹیچمنٹس کا محفوظ بیک اپ (Pure PHP No-Mysqldump Engine)
            </div>
        </div>
        <div class="btn-group">
            <form method="POST" action="{{ route('backups.database') }}" class="d-inline me-2">
                @csrf
                <button type="submit" class="btn btn-outline-danger fw-bold">
                    💾 Create Database Backup (SQL.GZ)
                </button>
            </form>
            <form method="POST" action="{{ route('backups.full') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger fw-bold">
                    📦 Create Full Archive (SQL + Attachments ZIP)
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Saved Backups (محفوظ شدہ بیک اپ فائلز)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>File Size</th>
                            <th>Created At</th>
                            <th>Created By</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $b)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $b->file_name }}</td>
                                <td>
                                    <span class="badge {{ $b->backup_type === 'FULL' ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $b->backup_type }}
                                    </span>
                                </td>
                                <td>{{ $b->formatted_size }}</td>
                                <td>{{ $b->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $b->creator?->name ?? 'System' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $b->status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ $backupService->getDownloadUrl($b) }}" class="btn btn-sm btn-outline-danger">
                                        ⬇️ Download Signed
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No backups generated yet. Click buttons above to create one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $backups->links() }}
        </div>
    </div>
</div>
@endsection
