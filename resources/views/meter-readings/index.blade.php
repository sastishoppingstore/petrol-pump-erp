@extends('layouts.app')

@section('title', 'Meter Readings')
@section('breadcrumb')
    <li class="breadcrumb-item active">Meter Readings</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Meter Readings</h1>

    <form method="GET" action="{{ route('meter-readings.index') }}" class="erp-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="filter_nozzle" class="form-label small mb-1">Nozzle</label>
                <select id="filter_nozzle" name="nozzle_id" class="form-select form-select-sm">
                    <option value="">All nozzles</option>
                    @foreach ($nozzles as $n)
                        <option value="{{ $n->id }}" @selected((string) request('nozzle_id') === (string) $n->id)>
                            {{ $n->label() }} — {{ $n->fuelProduct?->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="filter_type" class="form-label small mb-1">Type</label>
                <select id="filter_type" name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    @foreach (['SALE', 'OPENING', 'CLOSING', 'CORRECTION'] as $t)
                        <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Nozzle</th>
                        <th>Type</th>
                        <th class="text-end">Previous</th>
                        <th class="text-end">Current</th>
                        <th class="text-end">Quantity</th>
                        <th>By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($readings as $reading)
                        <tr>
                            <td class="small">{{ $reading->created_at?->format('d M Y H:i') }}</td>
                            <td class="small">{{ $reading->nozzle?->label() ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $reading->type === 'CORRECTION' ? 'warning' : 'secondary' }}">
                                    {{ $reading->type }}
                                </span>
                            </td>
                            <td class="text-end small">{{ number_format((float) $reading->previous_meter, 3) }}</td>
                            <td class="text-end small fw-semibold">{{ number_format((float) $reading->current_meter, 3) }}</td>
                            <td class="text-end small">{{ number_format((float) $reading->quantity, 3) }}</td>
                            <td class="small text-muted">{{ $reading->user?->name ?? 'System' }}</td>
                            <td class="small text-muted">{{ $reading->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No meter readings recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $readings->links() }}</div>
@endsection
