@extends('layouts.app')

@section('title', 'Dispensers')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dispensers</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Dispensers</h1>
        @can('fuel.create')
            <a href="{{ route('dispensers.create') }}" class="btn btn-primary">Add Dispenser</a>
        @endcan
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Number</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Model</th>
                        <th>Serial</th>
                        <th class="text-center">Nozzles</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dispensers as $dispenser)
                        <tr>
                            <td class="fw-semibold"><code>{{ $dispenser->dispenser_number }}</code></td>
                            <td>{{ $dispenser->name ?: '—' }}</td>
                            <td class="text-muted">{{ $dispenser->branch?->name }}</td>
                            <td class="text-muted">{{ $dispenser->model ?: '—' }}</td>
                            <td class="text-muted small">{{ $dispenser->serial_number ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $dispenser->nozzles_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $dispenser->isActive() ? 'success' : 'secondary' }}">
                                    {{ $dispenser->status }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('fuel.edit')
                                    <a href="{{ route('dispensers.edit', $dispenser) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No dispensers yet. @can('fuel.create')<a href="{{ route('dispensers.create') }}">Add the first one</a>.@endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
