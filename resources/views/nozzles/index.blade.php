@extends('layouts.app')

@section('title', 'Nozzles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Nozzles</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Nozzles</h1>
        @can('fuel.create')
            <a href="{{ route('nozzles.create') }}" class="btn btn-primary">Add Nozzle</a>
        @endcan
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Dispenser</th>
                        <th>Nozzle</th>
                        <th>Fuel</th>
                        <th>Tank</th>
                        <th class="text-end">Opening Meter</th>
                        <th class="text-end">Current Meter</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nozzles as $nozzle)
                        <tr>
                            <td><code>{{ $nozzle->dispenser?->dispenser_number ?? '—' }}</code></td>
                            <td class="fw-semibold">{{ $nozzle->nozzle_number }}</td>
                            <td>{{ $nozzle->fuelProduct?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $nozzle->tank?->tank_number ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $nozzle->opening_meter, 3) }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $nozzle->current_meter, 3) }}</td>
                            <td>
                                <span class="badge bg-{{ $nozzle->isActive() ? 'success' : 'secondary' }}">
                                    {{ $nozzle->status }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('fuel.edit')
                                    <a href="{{ route('nozzles.edit', $nozzle) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                                @can('stock.stock_adjustment')
                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                            data-bs-toggle="modal" data-bs-target="#correctMeter{{ $nozzle->id }}">
                                        Correct meter
                                    </button>
                                @endcan
                            </td>
                        </tr>

                        @can('stock.stock_adjustment')
                            <div class="modal fade" id="correctMeter{{ $nozzle->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form class="modal-content" method="POST"
                                          action="{{ route('nozzles.meter-correction', $nozzle) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Correct meter — {{ $nozzle->label() }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small text-muted">
                                                Current meter:
                                                <strong>{{ number_format((float) $nozzle->current_meter, 3) }}</strong>.
                                                A correction writes an immutable CORRECTION record to the audit trail.
                                            </p>
                                            <div class="mb-3">
                                                <label for="new_meter_{{ $nozzle->id }}" class="form-label">
                                                    Corrected meter <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" step="0.001" min="0"
                                                       id="new_meter_{{ $nozzle->id }}" name="new_meter"
                                                       value="{{ old('new_meter') }}"
                                                       class="form-control @error('new_meter') is-invalid @enderror" required>
                                                @error('new_meter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="mb-0">
                                                <label for="reason_{{ $nozzle->id }}" class="form-label">
                                                    Reason <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" id="reason_{{ $nozzle->id }}" name="reason"
                                                       value="{{ old('reason') }}"
                                                       class="form-control @error('reason') is-invalid @enderror"
                                                       maxlength="500" required
                                                       placeholder="Meter rollover / faulty meter">
                                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Apply correction</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endcan
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No nozzles yet. @can('fuel.create')<a href="{{ route('nozzles.create') }}">Add the first one</a>.@endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
