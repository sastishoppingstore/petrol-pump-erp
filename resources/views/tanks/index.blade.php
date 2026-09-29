@extends('layouts.app')

@section('title', 'Tanks')
@section('breadcrumb')
    <li class="breadcrumb-item active">Tanks</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Tanks</h1>
        @can('fuel.create')
            <a href="{{ route('tanks.create') }}" class="btn btn-primary">Add Tank</a>
        @endcan
    </div>

    <div class="row g-3">
        @forelse ($tanks as $tank)
            <div class="col-md-6 col-xl-4">
                <div class="erp-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="h6 mb-0">{{ $tank->displayName() }}</h2>
                            <span class="text-muted small">{{ $tank->fuelName() }} · {{ $tank->branch?->name }}</span>
                        </div>
                        <span class="badge bg-{{ $tank->isActive() ? 'success' : 'secondary' }}">{{ $tank->status }}</span>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Stock level</span>
                            <span class="fw-semibold">{{ $tank->stockPercent() }}%</span>
                        </div>
                        <div class="erp-stock-bar {{ $tank->stockLevel() }}">
                            <div class="erp-stock-fill" style="width: {{ min(100, (float) $tank->stockPercent()) }}%"></div>
                        </div>
                    </div>

                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Current stock</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $tank->current_stock, 3) }} L</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Capacity</td>
                                <td class="text-end">{{ number_format((float) $tank->capacity, 3) }} L</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Min / Max level</td>
                                <td class="text-end">
                                    {{ number_format((float) $tank->min_level, 3) }} /
                                    {{ number_format((float) $tank->max_level, 3) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Low-stock alert at</td>
                                <td class="text-end">{{ number_format((float) $tank->low_stock_threshold, 3) }} L</td>
                            </tr>
                        </tbody>
                    </table>

                    @can('fuel.edit')
                        <a href="{{ route('tanks.edit', $tank) }}" class="btn btn-sm btn-outline-secondary mt-3">Edit</a>
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="erp-card p-4 text-center text-muted">
                    No tanks yet. @can('fuel.create')<a href="{{ route('tanks.create') }}">Add the first tank</a>.@endcan
                </div>
            </div>
        @endforelse
    </div>
@endsection
