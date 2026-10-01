@extends('layouts.app')

@section('title', __('ui.nav.tank_readings'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.nav.tank_readings') }}</li>
@endsection

{{--
    Tank Readings — 2026 redesign.
    Dip reading form + filter + history table, sab glass boxes me,
    labels aur table cells centered. Routes, field names aur
    variance logic pehle jaisay hi hain.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.readings.heading') }}</h1>
        <p>{{ __('forecourt.readings.sub') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- ============ Record a dip reading ============ --}}
        <div class="lg:col-span-4">
            @can('stock.stock_adjustment')
                <div class="glass-card p-6">
                    <h2 class="text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.readings.record') }}</h2>

                    <form method="POST" action="{{ route('tank-readings.store') }}" novalidate class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="tank_id" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.tank') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <select id="tank_id" name="tank_id" class="input-3d @error('tank_id') border-red-400 @enderror" required>
                                    <option value="">{{ __('forecourt.common.select_tank') }}</option>
                                    @foreach ($tanks as $tank)
                                        <option value="{{ $tank->id }}" @selected((string) old('tank_id') === (string) $tank->id)>
                                            {{ $tank->displayName() }} — {{ $tank->fuelName() }}
                                            {{ __('forecourt.readings.system_suffix', ['stock' => number_format((float) $tank->current_stock, 3)]) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('tank_id') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="physical_quantity" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.readings.physical_qty') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <input type="number" step="0.001" min="0" id="physical_quantity" name="physical_quantity"
                                       value="{{ old('physical_quantity') }}"
                                       class="input-3d @error('physical_quantity') border-red-400 @enderror" required>
                            </div>
                            @error('physical_quantity') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1.5 text-center text-xs text-slate-400">{{ __('forecourt.readings.physical_help') }}</p>
                        </div>

                        <div>
                            <label for="reading_date" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.readings.reading_date') }}</label>
                            <div class="field-3d">
                                <input type="date" id="reading_date" name="reading_date"
                                       value="{{ old('reading_date', now()->toDateString()) }}" class="input-3d">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="temperature_c" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ session('locale') === 'ur' ? 'درجہ حرارت °C' : 'Temperature °C' }}</label>
                                <div class="field-3d">
                                    <input type="number" step="0.01" min="-10" max="80" id="temperature_c" name="temperature_c"
                                           value="{{ old('temperature_c') }}" placeholder="—"
                                           class="input-3d @error('temperature_c') border-red-400 @enderror">
                                </div>
                                @error('temperature_c') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="density" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ session('locale') === 'ur' ? 'کثافت (Density)' : 'Density' }}</label>
                                <div class="field-3d">
                                    <input type="number" step="0.0001" min="0.5" max="1.2" id="density" name="density"
                                           value="{{ old('density') }}" placeholder="0.0000"
                                           class="input-3d @error('density') border-red-400 @enderror">
                                </div>
                                @error('density') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="notes" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.notes') }}</label>
                            <div class="field-3d">
                                <textarea id="notes" name="notes" rows="2" class="input-3d">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-3d btn-3d-primary w-full">{{ __('forecourt.readings.save') }}</button>
                    </form>
                </div>
            @endcan
        </div>

        {{-- ============ Filter + history ============ --}}
        <div class="lg:col-span-8">
            <form method="GET" action="{{ route('tank-readings.index') }}" class="glass-card mb-5 p-5">
                <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-12">
                    <div class="sm:col-span-5">
                        <label for="filter_tank" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.common.tank') }}</label>
                        <div class="field-3d">
                            <select id="filter_tank" name="tank_id" class="input-3d">
                                <option value="">{{ __('forecourt.common.all_tanks') }}</option>
                                @foreach ($tanks as $tank)
                                    <option value="{{ $tank->id }}" @selected((string) request('tank_id') === (string) $tank->id)>
                                        {{ $tank->displayName() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="sm:col-span-4">
                        <label for="filter_date" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.common.date') }}</label>
                        <div class="field-3d">
                            <input type="date" id="filter_date" name="date" value="{{ request('date') }}"
                                   class="input-3d">
                        </div>
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit" class="btn-3d btn-3d-navy w-full">{{ __('ui.actions.filter') }}</button>
                    </div>
                </div>
            </form>

            <div class="glass-card overflow-hidden">
                <div class="table-3d">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('forecourt.common.date') }}</th>
                                <th>{{ __('forecourt.common.tank') }}</th>
                                <th>{{ __('forecourt.common.fuel') }}</th>
                                <th>{{ __('forecourt.readings.expected') }}</th>
                                <th>{{ __('forecourt.readings.physical') }}</th>
                                <th>{{ session('locale') === 'ur' ? 'درجہ حرارت' : 'Temp °C' }}</th>
                                <th>{{ session('locale') === 'ur' ? 'کثافت' : 'Density' }}</th>
                                <th>{{ __('forecourt.readings.variance') }}</th>
                                <th>{{ __('forecourt.common.type') }}</th>
                                <th>{{ __('forecourt.common.by') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($readings as $reading)
                                <tr>
                                    <td class="whitespace-nowrap text-xs">{{ $reading->reading_date?->format('d M Y') }}</td>
                                    <td class="text-xs font-semibold">{{ $reading->tank?->tank_number ?? '—' }}</td>
                                    <td class="text-xs">{{ $reading->fuelProduct?->name ?? '—' }}</td>
                                    <td class="tabular text-xs">{{ number_format((float) $reading->expected_quantity, 3) }}</td>
                                    <td class="tabular text-xs">{{ number_format((float) $reading->physical_quantity, 3) }}</td>
                                    <td class="tabular text-xs">{{ $reading->temperature_c !== null ? number_format((float) $reading->temperature_c, 1) . ' °C' : '—' }}</td>
                                    <td class="tabular text-xs">{{ $reading->density !== null ? number_format((float) $reading->density, 4) : '—' }}</td>
                                    <td class="tabular text-xs font-black
                                        {{ (float) $reading->variance_quantity < 0 ? 'text-red-600'
                                            : ((float) $reading->variance_quantity > 0 ? 'text-emerald-600' : '') }}">
                                        {{ number_format((float) $reading->variance_quantity, 3) }}
                                    </td>
                                    <td>
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $reading->variance_type === 'SHORTAGE' ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300'
                                            : ($reading->variance_type === 'SURPLUS' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' : 'bg-slate-200/70 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300') }}">
                                            {{ $reading->variance_type }}
                                        </span>
                                    </td>
                                    <td class="text-xs text-slate-400">{{ $reading->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 text-slate-400">{{ __('forecourt.readings.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $readings->links() }}</div>
        </div>
    </div>
@endsection
