@extends('layouts.app')

@section('title', __('forecourt.shifts.close_shift') . ' ' . $shift->shift_number)

@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('shifts.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.shifts') }}</a></li>
    <li class="text-slate-500"><a href="{{ route('shifts.show', $shift) }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ $shift->shift_number }}</a></li>
    <li class="text-slate-500">{{ __('forecourt.shifts.close_shift') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="page-head">
        <h1>{{ __('forecourt.shifts.close.heading') }} {{ $shift->shift_number }}</h1>
        <p>
            {{ __('forecourt.shifts.close.attendant') }} <strong>{{ $shift->user?->name }}</strong> {{ __('forecourt.shifts.close.station') }} <strong>{{ $shift->branch?->name }}</strong>
            {{ __('forecourt.shifts.close.opened') }} <strong>{{ $shift->opened_at->format('d M Y, h:i A') }}</strong>
        </p>
        <div class="page-actions">
            <a href="{{ route('shifts.show', $shift) }}" class="btn-3d btn-3d-ghost">{{ __('forecourt.shifts.close.cancel') }}</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="font-black">{{ __('forecourt.shifts.please_correct') }}</div>
            <ul class="mt-1 list-inside list-disc text-left">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('shifts.close', $shift) }}">
        @csrf

        {{-- 1. Nozzles Closing Meters --}}
        <div class="glass-card mb-6 overflow-hidden">
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">{{ __('forecourt.shifts.close.sec1') }}</h2>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('forecourt.shifts.close.nozzle_no') }}</th>
                            <th>{{ __('forecourt.common.dispenser') }}</th>
                            <th>{{ __('forecourt.common.fuel') }}</th>
                            <th>{{ __('forecourt.common.opening_meter') }}</th>
                            <th>{{ __('forecourt.shifts.close.closing_physical') }} <span class="text-red-600">*</span></th>
                            <th>{{ __('forecourt.shifts.close.dispensed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shift->shiftNozzles as $index => $sn)
                            @php
                                $openMeter = (float)$sn->opening_meter;
                                $currMeter = (float)$sn->nozzle?->current_meter;
                                $val = old("nozzles.{$index}.closing_meter", $currMeter);
                                $fuelName = strtoupper($sn->nozzle?->fuelProduct?->name ?? '');
                                $badgeClass = str_contains($fuelName, 'OCTANE') || str_contains($fuelName, 'HI-')
                                    ? 'badge-fuel-octane'
                                    : (str_contains($fuelName, 'HSD') || str_contains($fuelName, 'DIESEL')
                                        ? 'badge-fuel-diesel'
                                        : (str_contains($fuelName, 'PETROL') || str_contains($fuelName, 'SUPER') || str_contains($fuelName, 'MOGAS') || str_contains($fuelName, 'PMG')
                                            ? 'badge-fuel-petrol'
                                            : 'badge-fuel-other'));
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="nozzles[{{ $index }}][nozzle_id]" value="{{ $sn->nozzle_id }}">
                                    <span class="text-base font-black text-slate-800 dark:text-white">{{ $sn->nozzle?->nozzle_number }}</span>
                                </td>
                                <td>{{ $sn->nozzle?->dispenser?->name }}</td>
                                <td><span class="badge-fuel {{ $badgeClass }}">{{ $sn->nozzle?->fuelProduct?->name }}</span></td>
                                <td class="tabular font-mono text-slate-500">
                                    {{ number_format($openMeter, 3) }} L
                                </td>
                                <td>
                                    <div class="field-3d mx-auto w-48">
                                        <input type="number" step="0.001" min="{{ $openMeter }}"
                                               name="nozzles[{{ $index }}][closing_meter]"
                                               id="meter_input_{{ $index }}"
                                               data-open="{{ $openMeter }}"
                                               class="input-3d closing-meter-input text-center font-mono font-bold"
                                               value="{{ $val }}" required>
                                    </div>
                                    @error("nozzles.{$index}.closing_meter")
                                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="tabular font-mono text-base font-black text-vital-primary">
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

        {{-- 2. Cash Reconciliation --}}
        <div class="glass-card mb-6 p-6">
            <h2 class="mb-5 text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.shifts.close.sec2') }}</h2>
            <div class="grid items-start gap-5 md:grid-cols-3">
                <div class="stat-tile-3d tilt-3d stat-navy">
                    <div class="stat-label">{{ __('forecourt.shifts.close.expected_hand') }}</div>
                    <div class="stat-value" id="expected_cash_display">
                        Rs. {{ number_format((float)$expectedCash, 2) }}
                    </div>
                    <div class="stat-sub">{{ __('forecourt.shifts.close.calc_note') }}</div>
                </div>

                <div class="field-3d">
                    <label for="actual_cash">
                        {{ __('forecourt.shifts.close.actual_cash') }} <span class="text-red-600">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="actual_cash" name="actual_cash"
                           class="input-3d text-center font-mono text-lg font-black"
                           value="{{ old('actual_cash', $expectedCash) }}" required>
                    @error('actual_cash')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d">
                    <label for="card_total">{{ __('forecourt.shifts.close.card_total') }}</label>
                    <input type="number" step="0.01" min="0" id="card_total" name="card_total"
                           class="input-3d text-center font-mono font-bold"
                           value="{{ old('card_total', '0.00') }}">
                    <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.shifts.close.card_help') }}</p>
                    @error('card_total')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Live difference alert --}}
            <div class="mt-5 hidden rounded-2xl p-4 text-center shadow-3d transition" id="variance_alert">
                <h3 class="text-base font-black" id="variance_title">{{ __('forecourt.shifts.close.cash_difference') }}</h3>
                <p class="mt-0.5 text-sm" id="variance_desc"></p>
                <span class="pill-status pill-active mt-2 text-sm" id="variance_badge">0.00</span>
            </div>

            <div class="field-3d mx-auto mt-5 max-w-2xl">
                <label for="closing_notes">
                    {{ __('forecourt.shifts.close.closing_notes') }}
                    <span id="note_required_star" class="hidden text-red-600">*</span>
                </label>
                <textarea id="closing_notes" name="closing_notes" rows="3"
                          class="input-3d"
                          placeholder="{{ __('forecourt.shifts.close.notes_placeholder') }}">{{ old('closing_notes') }}</textarea>
                @error('closing_notes')
                    <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('shifts.show', $shift) }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
            <button type="submit" class="btn-3d btn-3d-primary px-8">
                {{ __('forecourt.shifts.close.confirm') }}
            </button>
        </div>
    </form>
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

    const alertBase = 'mt-5 rounded-2xl p-4 text-center shadow-3d transition ';
    const alertStates = {
        success: 'border border-emerald-300 bg-gradient-to-b from-emerald-50 to-emerald-100 text-emerald-900',
        warning: 'border border-amber-300 bg-gradient-to-b from-amber-50 to-amber-100 text-amber-900',
        danger: 'border border-red-300 bg-gradient-to-b from-red-50 to-red-100 text-red-900'
    };

    function updateVariance() {
        const actual = parseFloat(actualCashInput.value) || 0;
        const diff = actual - expectedCash;
        const absDiff = Math.abs(diff);

        varianceAlert.classList.remove('hidden');

        if (diff === 0) {
            varianceAlert.className = alertBase + alertStates.success;
            varianceTitle.textContent = 'Exact Cash Match!';
            varianceDesc.textContent = 'Actual physical cash perfectly matches expected ledger cash.';
            varianceBadge.className = 'pill-status pill-active mt-2 text-sm';
            varianceBadge.textContent = 'Rs. 0.00';
            noteRequiredStar.classList.add('hidden');
            closingNotesInput.removeAttribute('required');
        } else if (absDiff <= threshold) {
            varianceAlert.className = alertBase + alertStates.warning;
            varianceTitle.textContent = 'Minor Cash Variance (Within Tolerance)';
            varianceDesc.textContent = `Difference of Rs. ${diff.toFixed(2)} is within the Rs. ${threshold} threshold.`;
            varianceBadge.className = 'pill-status pill-pending mt-2 text-sm';
            varianceBadge.textContent = (diff > 0 ? '+' : '') + diff.toFixed(2);
            noteRequiredStar.classList.add('hidden');
            closingNotesInput.removeAttribute('required');
        } else {
            varianceAlert.className = alertBase + alertStates.danger;
            varianceTitle.textContent = '⚠️ Excessive Cash Variance Detected!';
            varianceDesc.textContent = `Difference of Rs. ${diff.toFixed(2)} exceeds the Rs. ${threshold} threshold. A closing note is mandatory and this shift will require manager review.`;
            varianceBadge.className = 'pill-status pill-danger mt-2 text-sm';
            varianceBadge.textContent = (diff > 0 ? '+' : '') + diff.toFixed(2);
            noteRequiredStar.classList.remove('hidden');
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
