@extends('layouts.app')

@section('title', __('forecourt.shifts.open_shift'))

@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('shifts.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.shifts') }}</a></li>
    <li class="text-slate-500">{{ __('forecourt.shifts.open_shift') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="page-head">
        <h1>{{ __('forecourt.shifts.create.heading') }}</h1>
        <p>{{ __('forecourt.shifts.create.sub') }}</p>
        <div class="page-actions">
            <a href="{{ route('shifts.index') }}" class="btn-3d btn-3d-ghost">{{ __('forecourt.shifts.create.back') }}</a>
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

    @if (! empty($pendingHandover))
        <div class="glass-card mb-6 border-2 border-amber-300/70 p-6 dark:border-amber-500/40">
            <h2 class="text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.handover.title') }}</h2>
            <p class="mx-auto mt-2 max-w-2xl text-center text-sm text-slate-500 dark:text-slate-300">{{ __('forecourt.handover.sub') }}</p>

            <div class="mx-auto mt-5 grid max-w-3xl gap-3 text-center sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-2xl bg-slate-100/80 p-3 dark:bg-slate-800/70">
                    <div class="text-[11px] font-black uppercase tracking-wider text-slate-400">{{ __('forecourt.handover.previous_cashier') }}</div>
                    <div class="mt-1 font-bold text-slate-800 dark:text-white">{{ $pendingHandover->employee?->name ?? '—' }}</div>
                    <div class="text-xs text-slate-400">{{ __('forecourt.handover.shift_number') }} {{ $pendingHandover->shift_number }}</div>
                </div>
                <div class="rounded-2xl bg-slate-100/80 p-3 dark:bg-slate-800/70">
                    <div class="text-[11px] font-black uppercase tracking-wider text-slate-400">{{ __('forecourt.handover.closed_at') }}</div>
                    <div class="mt-1 font-bold text-slate-800 dark:text-white">{{ $pendingHandover->closed_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    <div class="text-xs text-slate-400">{{ $pendingHandover->branch?->name }}</div>
                </div>
                <div class="rounded-2xl bg-slate-100/80 p-3 dark:bg-slate-800/70">
                    <div class="text-[11px] font-black uppercase tracking-wider text-slate-400">{{ __('forecourt.handover.closing_variance') }}</div>
                    <div class="tabular mt-1 font-black {{ (float) $pendingHandover->cash_difference < 0 ? 'text-red-600' : ((float) $pendingHandover->cash_difference > 0 ? 'text-emerald-600' : 'text-slate-700 dark:text-slate-200') }}">
                        Rs. {{ number_format((float) $pendingHandover->cash_difference, 2) }}
                    </div>
                    <div class="text-xs text-slate-400">{{ __('forecourt.handover.counted_by_previous') }}: Rs. {{ number_format((float) $pendingHandover->actual_cash, 2) }}</div>
                </div>
            </div>

            <div class="mx-auto mt-3 max-w-3xl text-center text-sm text-slate-500 dark:text-slate-300">
                {{ __('forecourt.handover.expected_cash') }}:
                <span class="tabular font-bold text-slate-800 dark:text-white">Rs. {{ number_format((float) $pendingHandover->expected_cash, 2) }}</span>
            </div>

            <form method="POST" action="{{ route('shifts.handover.accept', $pendingHandover) }}" class="mx-auto mt-5 max-w-3xl">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label for="handover_cash_counted">{{ __('forecourt.handover.counted_cash') }} <span class="text-red-600">*</span></label>
                        <input type="number" step="0.01" min="0" id="handover_cash_counted" name="handover_cash_counted"
                               class="input-3d text-center font-mono font-bold"
                               value="{{ old('handover_cash_counted') }}"
                               placeholder="{{ __('forecourt.handover.counted_placeholder') }}" required>
                        <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.handover.counted_help') }}</p>
                        @error('handover_cash_counted')
                            <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field-3d">
                        <label for="handover_pin">{{ __('forecourt.handover.your_pin') }} <span class="text-red-600">*</span></label>
                        <input type="password" id="handover_pin" name="pin" inputmode="numeric" autocomplete="off" maxlength="20"
                               class="input-3d text-center font-mono font-bold tracking-[0.4em]"
                               placeholder="{{ __('forecourt.handover.pin_placeholder') }}" required>
                        <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.handover.pin_help') }}</p>
                        @error('pin')
                            <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        @error('handover')
                            <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 flex justify-center">
                    <button type="submit" class="btn-3d btn-3d-success px-8">{{ __('forecourt.handover.accept') }}</button>
                </div>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('shifts.store') }}">
        @csrf

        <div class="glass-card mb-6 p-6">
            <h2 class="mb-5 text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.shifts.create.sec1') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="branch_id">{{ __('forecourt.shifts.create.station_branch') }} <span class="text-red-600">*</span></label>
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
                    <label for="user_id">{{ __('forecourt.shifts.create.attendant_label') }} <span class="text-red-600">*</span></label>
                    <select id="user_id" name="user_id" class="input-3d" required>
                        <option value="">{{ __('forecourt.shifts.create.select_employee') }}</option>
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
                    <label for="opening_cash">{{ __('forecourt.shifts.create.opening_float') }} <span class="text-red-600">*</span></label>
                    <input type="number" step="0.01" min="0" id="opening_cash" name="opening_cash"
                           class="input-3d text-center font-mono font-bold"
                           value="{{ old('opening_cash', '0.00') }}" required>
                    <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.shifts.create.float_help') }}</p>
                    @error('opening_cash')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d">
                    <label for="opening_notes">{{ __('forecourt.shifts.create.opening_notes') }}</label>
                    <input type="text" id="opening_notes" name="opening_notes"
                           class="input-3d"
                           value="{{ old('opening_notes') }}" placeholder="{{ __('forecourt.shifts.create.notes_placeholder') }}">
                    @error('opening_notes')
                        <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="glass-card mb-6 overflow-hidden">
            <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-200/70 px-6 py-4 dark:border-slate-700/50 sm:flex-row">
                <h2 class="text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.shifts.create.sec2') }}</h2>
                <div class="flex gap-2">
                    <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" onclick="toggleNozzles(true)">{{ __('forecourt.shifts.create.select_all') }}</button>
                    <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" onclick="toggleNozzles(false)">{{ __('forecourt.shifts.create.deselect_all') }}</button>
                </div>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('forecourt.shifts.create.select') }}</th>
                            <th>{{ __('forecourt.shifts.create.nozzle_no') }}</th>
                            <th>{{ __('forecourt.common.dispenser') }}</th>
                            <th>{{ __('forecourt.common.fuel_product') }}</th>
                            <th>{{ __('forecourt.shifts.create.current_system') }}</th>
                            <th>{{ __('forecourt.shifts.create.opening_physical') }}</th>
                            <th>{{ __('forecourt.shifts.create.notes_variance') }}</th>
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
                                        <span class="pill-status pill-pending ml-1"><span class="dot"></span>{{ __('forecourt.shifts.create.busy') }}</span>
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
                                               placeholder="{{ __('forecourt.shifts.create.variance_placeholder') }}"
                                               value="{{ old("nozzles.{$index}.notes") }}"
                                               {{ $isBusy ? 'disabled' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-500">
                                    <div class="text-4xl">⛽</div>
                                    <div class="mt-2 font-semibold">{{ __('forecourt.shifts.create.no_nozzles') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('shifts.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
            <button type="submit" class="btn-3d btn-3d-success px-8" {{ $nozzles->isEmpty() ? 'disabled' : '' }}>
                {{ __('forecourt.shifts.create.confirm') }}
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
