@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-6">📋 Settings Change History</h1>
            <p class="text-muted">Complete audit trail of all setting modifications</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-primary">
                ← Back to Settings
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-6">
            <input type="text" class="form-control" id="searchUser" placeholder="Search by user...">
        </div>
        <div class="col-md-6">
            <input type="date" class="form-control" id="filterDate" placeholder="Filter by date...">
        </div>
    </div>
    
    <!-- Audit Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Setting Key</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($changes as $change)
                        @php
                            $oldData = json_decode($change->old_data, true) ?? [];
                            $newData = json_decode($change->new_data, true) ?? [];
                            $key = $newData['key'] ?? 'N/A';
                            $oldValue = $oldData['value'] ?? '—';
                            $newValue = $newData['value'] ?? '—';
                        @endphp
                        <tr>
                            <td class="small">
                                <strong>{{ $change->created_at->format('d M Y H:i') }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ $change->user->name ?? 'System' }}
                                </span>
                            </td>
                            <td>
                                <code>{{ $key }}</code>
                            </td>
                            <td>
                                <span class="text-danger">{{ $oldValue }}</span>
                            </td>
                            <td>
                                <span class="text-success">{{ $newValue }}</span>
                            </td>
                            <td class="small text-muted">
                                {{ $change->ip }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No setting changes found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-light">
            {{ $changes->links() }}
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row mt-5">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total Changes</h6>
                    <h2>{{ $changes->total() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">This Month</h6>
                    <h2>{{ $changes->where('created_at', '>=', now()->startOfMonth())->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Today</h6>
                    <h2>{{ $changes->where('created_at', '>=', now()->startOfDay())->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Last 7 Days</h6>
                    <h2>{{ $changes->where('created_at', '>=', now()->subDays(7))->count() }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchUser = document.getElementById('searchUser');
    const filterDate = document.getElementById('filterDate');
    
    // Simple client-side filtering
    const rows = document.querySelectorAll('tbody tr');
    
    function filterTable() {
        const userFilter = searchUser.value.toLowerCase();
        const dateFilter = filterDate.value;
        
        rows.forEach(row => {
            const user = row.cells[1].textContent.toLowerCase();
            const dateCell = row.cells[0].textContent;
            
            let show = true;
            if (userFilter && !user.includes(userFilter)) show = false;
            if (dateFilter && !dateCell.includes(dateFilter)) show = false;
            
            row.style.display = show ? '' : 'none';
        });
    }
    
    searchUser.addEventListener('keyup', filterTable);
    filterDate.addEventListener('change', filterTable);
});
</script>
@endsection
