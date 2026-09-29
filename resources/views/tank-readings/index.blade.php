@extends('layouts.app')

@section('title', 'Tank Readings')
@section('breadcrumb')
    <li class="breadcrumb-item active">Tank Readings</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Tank Readings</h1>

    <div class="row g-3">
        <div class="col-lg-4">
            @can('stock.stock_adjustment')
                <div class="erp-card p-4">
                    <h2 class="h6 mb-3">Record a dip reading</h2>
                    <form method="POST" action="{{ route('tank-readings.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="tank_id" class="form-label">Tank <span class="text-danger">*</span></label>
                            <select id="tank_id" name="tank_id" class="form-select @error('tank_id') is-invalid @enderror" required>
                                <option value="">Select tank…</option>
                                @foreach ($tanks as $tank)
                                    <option value="{{ $tank->id }}" @selected((string) old('tank_id') === (string) $tank->id)>
                                        {{ $tank->displayName() }} — {{ $tank->fuelName() }}
                                        (system: {{ number_format((float) $tank->current_stock, 3) }} L)
                                    </option>
                                @endforeach
                            </select>
                            @error('tank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="physical_quantity" class="form-label">Physical quantity (L) <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0" id="physical_quantity" name="physical_quantity"
                                   value="{{ old('physical_quantity') }}"
                                   class="form-control @error('physical_quantity') is-invalid @enderror" required>
                            @error('physical_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Measured by dip stick. Variance against system stock is calculated automatically.</div>
                        </div>

                        <div class="mb-3">
                            <label for="reading_date" class="form-label">Reading date</label>
                            <input type="date" id="reading_date" name="reading_date"
                                   value="{{ old('reading_date', now()->toDateString()) }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Save reading</button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            <form method="GET" action="{{ route('tank-readings.index') }}" class="erp-card p-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label for="filter_tank" class="form-label small mb-1">Tank</label>
                        <select id="filter_tank" name="tank_id" class="form-select form-select-sm">
                            <option value="">All tanks</option>
                            @foreach ($tanks as $tank)
                                <option value="{{ $tank->id }}" @selected((string) request('tank_id') === (string) $tank->id)>
                                    {{ $tank->displayName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_date" class="form-label small mb-1">Date</label>
                        <input type="date" id="filter_date" name="date" value="{{ request('date') }}"
                               class="form-control form-control-sm">
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
                                <th>Date</th>
                                <th>Tank</th>
                                <th>Fuel</th>
                                <th class="text-end">Expected</th>
                                <th class="text-end">Physical</th>
                                <th class="text-end">Variance</th>
                                <th>Type</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($readings as $reading)
                                <tr>
                                    <td class="small">{{ $reading->reading_date?->format('d M Y') }}</td>
                                    <td class="small">{{ $reading->tank?->tank_number ?? '—' }}</td>
                                    <td class="small">{{ $reading->fuelProduct?->name ?? '—' }}</td>
                                    <td class="text-end small">{{ number_format((float) $reading->expected_quantity, 3) }}</td>
                                    <td class="text-end small">{{ number_format((float) $reading->physical_quantity, 3) }}</td>
                                    <td class="text-end small fw-semibold
                                        {{ (float) $reading->variance_quantity < 0 ? 'text-danger'
                                            : ((float) $reading->variance_quantity > 0 ? 'text-success' : '') }}">
                                        {{ number_format((float) $reading->variance_quantity, 3) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $reading->variance_type === 'SHORTAGE' ? 'danger'
                                            : ($reading->variance_type === 'SURPLUS' ? 'info' : 'secondary') }}">
                                            {{ $reading->variance_type }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $reading->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No readings recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $readings->links() }}</div>
        </div>
    </div>
@endsection
