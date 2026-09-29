@extends('layouts.app')

@section('title', 'Open Shift')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item active" aria-current="page">Open Shift</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Open New Shift</h1>
                <p class="text-muted small mb-0">Assign an attendant to active nozzles, declare initial cash float, and verify starting meters.</p>
            </div>
            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">← Back to Shifts</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <h6 class="fw-bold mb-1">Please correct the following errors:</h6>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('shifts.store') }}">
            @csrf

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">1. Shift Operator & Station</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label fw-semibold">Station / Branch <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required onchange="window.location.href = '{{ route('shifts.create') }}?branch_id=' + this.value">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $targetBranchId) == $branch->id)>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="user_id" class="form-label fw-semibold">Shift Attendant / Cashier <span class="text-danger">*</span></label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                <option value="">Select Employee...</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected(old('user_id') == $emp->id || (old('user_id') === null && auth()->id() == $emp->id))>
                                        {{ $emp->name }} ({{ $emp->employee_code ?: 'EMP-'.$emp->id }}) - {{ $emp->roleLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="opening_cash" class="form-label fw-semibold">Opening Cash Float (Rs.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" min="0" id="opening_cash" name="opening_cash"
                                       class="form-control font-monospace @error('opening_cash') is-invalid @enderror"
                                       value="{{ old('opening_cash', '0.00') }}" required>
                            </div>
                            <div class="form-text small">Initial physical cash handed to the attendant at shift start.</div>
                            @error('opening_cash')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="opening_notes" class="form-label fw-semibold">Opening Notes</label>
                            <input type="text" id="opening_notes" name="opening_notes"
                                   class="form-control @error('opening_notes') is-invalid @enderror"
                                   value="{{ old('opening_notes') }}" placeholder="e.g. Morning Shift A, handed 5x1000 notes...">
                            @error('opening_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">2. Assign Nozzles & Verify Opening Meters</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="toggleNozzles(true)">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleNozzles(false)">Deselect All</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">Select</th>
                                    <th>Nozzle #</th>
                                    <th>Dispenser</th>
                                    <th>Fuel Product</th>
                                    <th>Current System Meter</th>
                                    <th width="200">Opening Physical Meter</th>
                                    <th>Notes (if variance)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($nozzles as $index => $nozzle)
                                    @php
                                        $isBusy = in_array($nozzle->id, $busyNozzleIds);
                                    @endphp
                                    <tr class="{{ $isBusy ? 'table-secondary opacity-75' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="nozzles[{{ $index }}][nozzle_id]"
                                                   value="{{ $nozzle->id }}"
                                                   class="form-check-input nozzle-checkbox"
                                                   id="nozzle_check_{{ $nozzle->id }}"
                                                   {{ $isBusy ? 'disabled' : '' }}
                                                   @checked(! $isBusy && (old("nozzles.{$index}.nozzle_id") == $nozzle->id || empty(old('nozzles'))))>
                                        </td>
                                        <td>
                                            <label for="nozzle_check_{{ $nozzle->id }}" class="fw-bold cursor-pointer mb-0">
                                                {{ $nozzle->nozzle_number }}
                                            </label>
                                            @if ($isBusy)
                                                <span class="badge bg-warning text-dark small ms-1">Active in another shift</span>
                                            @endif
                                        </td>
                                        <td>{{ $nozzle->dispenser?->name }}</td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $nozzle->fuelProduct?->name }}
                                            </span>
                                        </td>
                                        <td class="font-monospace text-muted">
                                            {{ number_format((float)$nozzle->current_meter, 3) }} L
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0"
                                                   name="nozzles[{{ $index }}][opening_meter]"
                                                   class="form-control form-control-sm font-monospace"
                                                   value="{{ old("nozzles.{$index}.opening_meter", $nozzle->current_meter) }}"
                                                   {{ $isBusy ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <input type="text"
                                                   name="nozzles[{{ $index }}][notes]"
                                                   class="form-control form-control-sm"
                                                   placeholder="Required if meter differs from system"
                                                   value="{{ old("nozzles.{$index}.notes") }}"
                                                   {{ $isBusy ? 'disabled' : '' }}>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No nozzles registered for this branch. Please create nozzles in Fuel Master first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-success px-4 fw-semibold" {{ $nozzles->isEmpty() ? 'disabled' : '' }}>
                    🚀 Confirm & Open Shift
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleNozzles(state) {
    document.querySelectorAll('.nozzle-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = state;
    });
}
</script>
@endpush
@endsection
