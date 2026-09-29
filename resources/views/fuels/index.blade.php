@extends('layouts.app')

@section('title', 'Fuel Products')
@section('breadcrumb')
    <li class="breadcrumb-item active">Fuel Products</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Fuel Products</h1>
        @can('fuel.create')
            <a href="{{ route('fuels.create') }}" class="btn btn-primary">Add Fuel</a>
        @endcan
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Unit</th>
                        <th class="text-end">Selling Price</th>
                        <th class="text-end">Avg Cost</th>
                        <th class="text-end">Min Stock</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fuels as $fuel)
                        <tr>
                            <td class="fw-semibold"><code>{{ $fuel->code }}</code></td>
                            <td>{{ $fuel->name }}</td>
                            <td class="text-muted">{{ $fuel->unit }}</td>
                            <td class="text-end">{{ number_format((float) $fuel->selling_price, 2) }}</td>
                            <td class="text-end text-muted">{{ number_format((float) $fuel->average_cost, 2) }}</td>
                            <td class="text-end text-muted">{{ number_format((float) $fuel->minimum_stock, 3) }}</td>
                            <td>
                                <span class="badge bg-{{ $fuel->isActive() ? 'success' : 'secondary' }}">
                                    {{ $fuel->status }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('fuel.edit')
                                    <a href="{{ route('fuels.edit', $fuel) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No fuel products yet. @can('fuel.create')<a href="{{ route('fuels.create') }}">Add the first one</a>.@endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $fuels->links() }}</div>
@endsection
