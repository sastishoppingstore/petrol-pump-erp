@extends('layouts.app')

@section('title', __('ui.nav.fuel_prices'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.nav.fuel_prices') }}</li>
@endsection

{{--
    Fuel Prices — 2026 redesign.
    Price change form + current prices + history table, sab glass
    boxes me aur centered. Route, permission aur fields same hain.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.prices.heading') }}</h1>
        <p>{{ __('forecourt.prices.sub') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- ============ Change price + current prices ============ --}}
        <div class="lg:col-span-5">
            @can('fuel.price_change')
                <div class="glass-card p-6">
                    <h2 class="text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.prices.change_price') }}</h2>

                    <form method="POST" action="{{ route('fuel-prices.store') }}" novalidate class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="fuel_product_id" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.fuel') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <select id="fuel_product_id" name="fuel_product_id"
                                        class="input-3d @error('fuel_product_id') border-red-400 @enderror" required>
                                    <option value="">{{ __('forecourt.common.select_fuel') }}</option>
                                    @foreach ($fuels as $fuel)
                                        <option value="{{ $fuel->id }}"
                                            @selected((string) old('fuel_product_id') === (string) $fuel->id)>
                                            {{ $fuel->name }} — {{ number_format((float) $fuel->selling_price, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('fuel_product_id') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="selling_price" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.prices.new_price') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <input type="number" step="0.01" min="0" id="selling_price" name="selling_price"
                                       value="{{ old('selling_price') }}"
                                       class="input-3d @error('selling_price') border-red-400 @enderror" required>
                            </div>
                            @error('selling_price') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="effective_from" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.prices.effective_from') }}</label>
                            <div class="field-3d">
                                <input type="datetime-local" id="effective_from" name="effective_from"
                                       value="{{ old('effective_from') }}" class="input-3d">
                            </div>
                            <p class="mt-1.5 text-center text-xs text-slate-400">{{ __('forecourt.prices.effective_help') }}</p>
                        </div>

                        <div>
                            <label for="reason" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.reason') }}</label>
                            <div class="field-3d">
                                <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                                       class="input-3d" maxlength="500" placeholder="{{ __('forecourt.prices.reason_placeholder') }}">
                            </div>
                        </div>

                        <button type="submit" class="btn-3d btn-3d-primary w-full">{{ __('forecourt.prices.update_price') }}</button>
                    </form>
                </div>
            @endcan

            <div class="glass-card mt-5 p-6">
                <h2 class="text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.prices.current_prices') }}</h2>
                <div class="mt-3 divide-y divide-slate-200/70 dark:divide-slate-700/50">
                    @foreach ($fuels as $fuel)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $fuel->name }}</span>
                            <span class="tabular text-base font-black text-vital-primary">{{ number_format((float) $fuel->selling_price, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============ Price history ============ --}}
        <div class="lg:col-span-7">
            <div class="glass-card overflow-hidden">
                <div class="table-3d">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('forecourt.common.fuel') }}</th>
                                <th>{{ __('forecourt.prices.scope') }}</th>
                                <th>{{ __('forecourt.prices.price') }}</th>
                                <th>{{ __('forecourt.prices.effective_from') }}</th>
                                <th>{{ __('forecourt.prices.effective_to') }}</th>
                                <th>{{ __('forecourt.common.reason') }}</th>
                                <th>{{ __('forecourt.common.by') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $row)
                                <tr>
                                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $row->fuelProduct?->name ?? '—' }}</td>
                                    <td class="text-xs text-slate-500">{{ $row->branch?->name ?? 'All branches' }}</td>
                                    <td class="tabular font-black text-slate-800 dark:text-white">{{ number_format((float) $row->price, 2) }}</td>
                                    <td class="whitespace-nowrap text-xs">{{ $row->effective_from?->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap text-xs">
                                        @if ($row->effective_to)
                                            {{ $row->effective_to->format('d M Y H:i') }}
                                        @else
                                            <span class="font-bold text-emerald-600">{{ __('forecourt.prices.current') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-500">{{ $row->reason ?? '—' }}</td>
                                    <td class="text-xs text-slate-400">{{ $row->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-slate-400">{{ __('forecourt.prices.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $history->links() }}</div>
        </div>
    </div>
@endsection
