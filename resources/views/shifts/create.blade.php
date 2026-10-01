@extends('layouts.app')

@section('title', 'Open Shift')

@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('shifts.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Shifts</a></li>
    <li class="text-slate-500">Open Shift</li>
@endsection

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="page-head">
        <h1>🚀 Open New Shift</h1>
        <p>Attendant ko nozzles assign karein, opening cash float declare karein aur starting meters verify karein</p>
        <div class="page-actions">
            <a href="{{ route('shifts.index') }}" class="btn-3d btn-3d-ghost">← Back to Shifts</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="font-black">Please correct the following errors:</div>
            <ul class="mt-1 list-inside list-disc text-left">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('shifts.store') }}">
        @csrf

        <div class="glass-card mb-6 p-6">
            <h2 class="mb-5 text-center text-base font-black text-slate-800 dark:text-white">1. Shift Operator & Station</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="branch_id">Station / Branch <span class="text-red-600">*</span></label>
                    <select id="branch_id" name="branch_id" class="input-3d" required onchange="window.location.href = '{{ route('shifts.create') }}?branch_id=' + this.value">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $targetBranchId) == $branch->id)>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d">
                    <label for="user_id">Shift Attendant / Cashier <span class="text-red-600">*</span></label>
                    <select id="user_id" name="user_id" class="input-3d" required>
                        <option value="">Select Employee...</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('user_id') == $emp->id || (old('user_id') === null && auth()->id() == $emp->id))>
                                {{ $emp->name }} ({{ $emp->employee_code ?: 'EMP-'.$emp->id }}) - {{ $emp->roleLabel() }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d">
                    <label for="opening_cash">Opening Cash Float (Rs.) <span class="text-red-600">*</span></label>
                    <input type="number" step="0.01" min="0" id="opening_cash" name="opening_cash"
                           class="input-3d text-center font-mono font-bold"
                           value="{{ old('opening_cash', '0.00') }}" required>
                    <p class="mt-1 text-center text-xs text-slate-400">Initial physical cash handed to the attendant at shift start.</p>
                    @error('opening_cash')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d">
                    <label for="opening_notes">Opening Notes</label>
                    <input type="text" id="opening_notes" name="opening_notes"
                           class="input-3d"
                           value="{{ old('opening_notes') }}" placeholder="e.g. Morning Shift A, handed 5x1000 notes...">
                    @error('opening_notes')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="glass-card mb-6 overflow-hidden">
            <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-200/70 px-6 py-4 dark:border-slate-700/50 sm:flex-row">
                <h2 class="text-base font-black text-slate-800 dark:text-white">2. Assign Nozzles & Verify Opening Meters</h2>
                <div class="flex gap-2">
                    <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" onclick="toggleNozzles(true)">Select All</button>
                    <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" onclick="toggleNozzles(false)">Deselect All</button>
                </div>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Nozzle #</th>
                            <th>Dispenser</th>
                            <th>Fuel Product</th>
                            <th>Current System Meter</th>
                            <th>Opening Physical Meter</th>
                            <th>Notes (if variance)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($nozzles as $index => $nozzle)
                            @php
                                $isBusy = in_array($nozzle->id, $busyNozzleIds);
                            @endphp
                            <tr class="{{ $isBusy ? 'opacity-60' : '' }}">
                                <td>
                                    <input type="checkbox"
                                           name="nozzles[{{ $index }}][nozzle_id]"
                                           value="{{ $nozzle->id }}"
                                           class="nozzle-checkbox h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                           id="nozzle_check_{{ $nozzle->id }}"
                                           {{ $isBusy ? 'disabled' : '' }}
                                           @checked(! $isBusy && (old("nozzles.{$index}.nozzle_id") == $nozzle->id || empty(old('nozzles'))))>
                                </td>
                                <td>
                                    <label for="nozzle_check_{{ $nozzle->id }}" class="cursor-pointer text-base font-black text-slate-800 dark:text-white">
                                        {{ $nozzle->nozzle_number }}
                                    </label>
                                    @if ($isBusy)
                                        <span class="pill-status pill-pending ml-1"><span class="dot"></span>Active in another shift</span>
                                    @endif
                                </td>
                                <td>{{ $nozzle->dispenser?->name }}</td>
                                <td>
                                    @php
                                        $fuelName = strtoupper($nozzle->fuelProduct?->name ?? '');
                                        $badgeClass = str_contains($fuelName, 'OCTANE') || str_contains($fuelName, 'HI-')
                                            ? 'badge-fuel-octane'
                                            : (str_contains($fuelName, 'HSD') || str_contains($fuelName, 'DIESEL')
                                                ? 'badge-fuel-diesel'
                                                : (str_contains($fuelName, 'PETROL') || str_contains($fuelName, 'SUPER') || str_contains($fuelName, 'MOGAS') || str_contains($fuelName, 'PMG')
                                                    ? 'badge-fuel-petrol'
                                                    : 'badge-fuel-other'));
                                    @endphp
                                    <span class="badge-fuel {{ $badgeClass }}">
                                        {{ $nozzle->fuelProduct?->name }}
                                    </span>
                                </td>
                                <td class="tabular font-mono text-slate-500">
                                    {{ number_format((float)$nozzle->current_meter, 3) }} L
                                </td>
                                <td>
                                    <div class="field-3d mx-auto w-44">
                                        <input type="number" step="0.001" min="0"
                                               name="nozzles[{{ $index }}][opening_meter]"
                                               class="input-3d text-center font-mono"
                                               value="{{ old("nozzles.{$index}.opening_meter", $nozzle->current_meter) }}"
                                               {{ $isBusy ? 'disabled' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="field-3d mx-auto w-52">
                                        <input type="text"
                                               name="nozzles[{{ $index }}][notes]"
                                               class="input-3d"
                                               placeholder="Required if meter differs from system"
                                               value="{{ old("nozzles.{$index}.notes") }}"
                                               {{ $isBusy ? 'disabled' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-500">
                                    <div class="text-4xl">⛽</div>
                                    <div class="mt-2 font-semibold">No nozzles registered for this branch. Please create nozzles in Fuel Master first.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('shifts.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
            <button type="submit" class="btn-3d btn-3d-success px-8" {{ $nozzles->isEmpty() ? 'disabled' : '' }}>
                🚀 Confirm & Open Shift
            </button>
        </div>
    </form>
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
