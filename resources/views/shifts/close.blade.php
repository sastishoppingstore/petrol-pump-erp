@extends('layouts.app')

@section('title', 'Close Shift ' . $shift->shift_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item"><a href="{{ route('shifts.show', $shift) }}">{{ $shift->shift_number }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Close Shift</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Reconcile & Close Shift: {{ $shift->shift_number }}</h1>
                <p class="text-muted small mb-0">
                    Attendant: <strong>{{ $shift->user?->name }}</strong> | Station: <strong>{{ $shift->branch?->name }}</strong>
                    | Opened: <strong>{{ $shift->opened_at->format('d M Y, h:i A') }}</strong>
                </p>
            </div>
            <a href="{{ route('shifts.show', $shift) }}" class="btn btn-outline-secondary">← Cancel</a>
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

        <form method="POST" action="{{ route('shifts.close', $shift) }}">
            @csrf

            {{-- 1. Nozzles Closing Meters --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">1. Physical Closing Meter Readings</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nozzle #</th>
                                    <th>Dispenser</th>
                                    <th>Fuel</th>
                                    <th class="text-end">Opening Meter</th>
                                    <th width="240" class="text-end">Closing Physical Meter (L) <span class="text-danger">*</span></th>
                                    <th class="text-end">Dispensed Litres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($shift->shiftNozzles as $index => $sn)
                                    @php
                                        $openMeter = (float)$sn->opening_meter;
                                        $currMeter = (float)$sn->nozzle?->current_meter;
                                        $val = old("nozzles.{$index}.closing_meter", $currMeter);
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="nozzles[{{ $index }}][nozzle_id]" value="{{ $sn->nozzle_id }}">
                                            <strong>{{ $sn->nozzle?->nozzle_number }}</strong>
                                        </td>
                                        <td>{{ $sn->nozzle?->dispenser?->name }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $sn->nozzle?->fuelProduct?->name }}</span>
                                        </td>
                                        <td class="text-end font-monospace">
                                            {{ number_format($openMeter, 3) }} L
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.001" min="{{ $openMeter }}"
                                                       name="nozzles[{{ $index }}][closing_meter]"
                                                       id="meter_input_{{ $index }}"
                                                       data-open="{{ $openMeter }}"
                                                       class="form-control font-monospace text-end closing-meter-input @error("nozzles.{$index}.closing_meter") is-invalid @enderror"
                                                       value="{{ $val }}" required>
                                                <span class="input-group-text">L</span>
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary">
                                            <span id="dispensed_calc_{{ $index }}">
                                                {{ number_format(max(0, (float)$val - $openMeter), 3) }} L
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 2. Cash Reconciliation --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">2. Cash & Card Reconciliation</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <span class="text-muted small text-uppercase fw-semibold d-block">Expected Cash in Hand</span>
                                <h2 class="fw-bold font-monospace text-primary mb-0 mt-1" id="expected_cash_display">
                                    Rs. {{ number_format((float)$expectedCash, 2) }}
                                </h2>
                                <small class="text-muted">Calculated by system ledger</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="actual_cash" class="form-label fw-semibold">
                                Actual Physical Cash Counted (Rs.) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" min="0" id="actual_cash" name="actual_cash"
                                       class="form-control font-monospace fw-bold @error('actual_cash') is-invalid @enderror"
                                       value="{{ old('actual_cash', $expectedCash) }}" required>
                            </div>
                            @error('actual_cash')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="card_total" class="form-label fw-semibold">
                                Card Settlement Total (Rs.)
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" min="0" id="card_total" name="card_total"
                                       class="form-control font-monospace @error('card_total') is-invalid @enderror"
                                       value="{{ old('card_total', '0.00') }}">
                            </div>
                            <div class="form-text small">POS machine bank batch slip total</div>
                            @error('card_total')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Live difference alert --}}
                    <div class="mt-4 p-3 rounded d-none" id="variance_alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1" id="variance_title">Cash Difference: Rs. 0.00</h6>
                                <p class="small mb-0" id="variance_desc"></p>
                            </div>
                            <span class="badge fs-5" id="variance_badge">0.00</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="closing_notes" class="form-label fw-semibold">
                            Closing Notes / Variance Justification
                            <span id="note_required_star" class="text-danger d-none">*</span>
                        </label>
                        <textarea id="closing_notes" name="closing_notes" rows="3"
                                  class="form-control @error('closing_notes') is-invalid @enderror"
                                  placeholder="Provide handover details or explanation of any cash/meter variances...">{{ old('closing_notes') }}</textarea>
                        @error('closing_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('shifts.show', $shift) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger px-4 fw-semibold">
                    🔒 Confirm & Close Shift
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const expectedCash = parseFloat("{{ (float)$expectedCash }}");
    const threshold = parseFloat("{{ (float)$threshold }}");
    const actualCashInput = document.getElementById('actual_cash');
    const varianceAlert = document.getElementById('variance_alert');
    const varianceTitle = document.getElementById('variance_title');
    const varianceDesc = document.getElementById('variance_desc');
    const varianceBadge = document.getElementById('variance_badge');
    const noteRequiredStar = document.getElementById('note_required_star');
    const closingNotesInput = document.getElementById('closing_notes');

    function updateVariance() {
        const actual = parseFloat(actualCashInput.value) || 0;
        const diff = actual - expectedCash;
        const absDiff = Math.abs(diff);

        varianceAlert.classList.remove('d-none', 'alert-success', 'alert-warning', 'alert-danger');

        if (diff === 0) {
            varianceAlert.classList.add('alert-success');
            varianceTitle.textContent = 'Exact Cash Match!';
            varianceDesc.textContent = 'Actual physical cash perfectly matches expected ledger cash.';
            varianceBadge.className = 'badge bg-success fs-6';
            varianceBadge.textContent = 'Rs. 0.00';
            noteRequiredStar.classList.add('d-none');
            closingNotesInput.removeAttribute('required');
        } else if (absDiff <= threshold) {
            varianceAlert.classList.add('alert-warning');
            varianceTitle.textContent = 'Minor Cash Variance (Within Tolerance)';
            varianceDesc.textContent = `Difference of Rs. ${diff.toFixed(2)} is within the Rs. ${threshold} threshold.`;
            varianceBadge.className = 'badge bg-warning text-dark fs-6';
            varianceBadge.textContent = (diff > 0 ? '+' : '') + diff.toFixed(2);
            noteRequiredStar.classList.add('d-none');
            closingNotesInput.removeAttribute('required');
        } else {
            varianceAlert.classList.add('alert-danger');
            varianceTitle.textContent = '⚠️ Excessive Cash Variance Detected!';
            varianceDesc.textContent = `Difference of Rs. ${diff.toFixed(2)} exceeds the Rs. ${threshold} threshold. A closing note is mandatory and this shift will require manager review.`;
            varianceBadge.className = 'badge bg-danger fs-6';
            varianceBadge.textContent = (diff > 0 ? '+' : '') + diff.toFixed(2);
            noteRequiredStar.classList.remove('d-none');
            closingNotesInput.setAttribute('required', 'required');
        }
    }

    actualCashInput.addEventListener('input', updateVariance);
    updateVariance();

    // Closing meter calculations
    document.querySelectorAll('.closing-meter-input').forEach(function(input, idx) {
        input.addEventListener('input', function() {
            const open = parseFloat(input.dataset.open) || 0;
            const close = parseFloat(input.value) || 0;
            const dispensed = Math.max(0, close - open);
            const calcSpan = document.getElementById('dispensed_calc_' + idx);
            if (calcSpan) {
                calcSpan.textContent = dispensed.toFixed(3) + ' L';
            }
        });
    });
});
</script>
@endpush
@endsection
