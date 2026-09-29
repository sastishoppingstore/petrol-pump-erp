@extends('layouts.app')

@section('title', 'Shifts')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Shifts</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Shift Management</h1>
        <p class="text-muted small mb-0">Monitor active shifts, cash reconciliation, nozzle meter readings and variances.</p>
    </div>
    @can('shift.create')
        <a href="{{ route('shifts.create') }}" class="btn btn-primary">
            ➕ Open New Shift
        </a>
    @endcan
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('shifts.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="filter-status" class="form-label small text-muted">Status</label>
                <select id="filter-status" name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="OPEN" @selected(($filters['status'] ?? '') === 'OPEN')>🟢 Active (OPEN)</option>
                    <option value="PENDING_APPROVAL" @selected(($filters['status'] ?? '') === 'PENDING_APPROVAL')>🟡 Pending Approval</option>
                    <option value="CLOSED" @selected(($filters['status'] ?? '') === 'CLOSED')>⚪ Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-branch" class="form-label small text-muted">Branch</label>
                <select id="filter-branch" name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-employee" class="form-label small text-muted">Attendant</label>
                <select id="filter-employee" name="user_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(($filters['user_id'] ?? '') == $emp->id)>
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-date" class="form-label small text-muted">Date</label>
                <input type="date" id="filter-date" name="date" class="form-control form-control-sm" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
                <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear">✖</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Shift #</th>
                    <th>Branch</th>
                    <th>Attendant</th>
                    <th>Opened</th>
                    <th>Closed</th>
                    <th class="text-end">Opening Float</th>
                    <th class="text-end">Expected Cash</th>
                    <th class="text-end">Actual Cash</th>
                    <th class="text-end">Difference</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    <tr>
                        <td>
                            <a href="{{ route('shifts.show', $shift) }}" class="fw-bold text-decoration-none">
                                {{ $shift->shift_number }}
                            </a>
                        </td>
                        <td>{{ $shift->branch?->name }}</td>
                        <td>
                            <span class="fw-semibold">{{ $shift->user?->name }}</span>
                            <span class="text-muted small d-block">{{ $shift->user?->employee_code }}</span>
                        </td>
                        <td class="small">{{ $shift->opened_at->format('d M Y, h:i A') }}</td>
                        <td class="small">
                            {{ $shift->closed_at ? $shift->closed_at->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td class="text-end font-monospace">Rs. {{ number_format((float)$shift->opening_cash, 2) }}</td>
                        <td class="text-end font-monospace">Rs. {{ number_format((float)$shift->expected_cash, 2) }}</td>
                        <td class="text-end font-monospace">
                            @if ($shift->actual_cash !== null)
                                Rs. {{ number_format((float)$shift->actual_cash, 2) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end font-monospace">
                            @if ($shift->cash_difference !== null)
                                @php
                                    $diff = (float)$shift->cash_difference;
                                    $diffBadge = $diff == 0 ? 'bg-success' : ($diff > 0 ? 'bg-info' : 'bg-danger');
                                @endphp
                                <span class="badge {{ $diffBadge }}">
                                    {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $shift->statusBadgeClass() }}">
                                {{ $shift->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('shifts.show', $shift) }}" class="btn btn-outline-secondary" title="View details">
                                    👁️
                                </a>
                                @if ($shift->isOpen() && (auth()->id() === $shift->user_id || auth()->user()->hasPermission('shift.close')))
                                    <a href="{{ route('shifts.close.form', $shift) }}" class="btn btn-outline-warning" title="Close Shift">
                                        🔒 Close
                                    </a>
                                @endif
                                <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="btn btn-outline-dark" title="Print Report">
                                    🖨️
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            No shifts found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($shifts->hasPages())
        <div class="card-footer py-2">
            {{ $shifts->links() }}
        </div>
    @endif
</div>
@endsection
