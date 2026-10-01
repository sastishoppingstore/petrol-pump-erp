@extends('layouts.app')

@section('title', __('forecourt.stock.adjustments.title'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('stock.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('forecourt.stock.index.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('forecourt.stock.index.adjustments') }}</li>
@endsection

{{--
    Stock Adjustments — 2026 redesign.
    Request form + approvals table glass boxes me. Reject modal
    pehle table ke andar Bootstrap markup tha; ab wo page ke end
    par Alpine modal hai (brief rule: modal kabhi table ke andar
    nahi). Approve/reject routes, confirm aur fields same hain.
--}}
@section('content')
<div x-data="{ rejectId: null }" @keydown.escape.window="rejectId = null">
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.stock.adjustments.heading') }}</h1>
        <p>{{ __('forecourt.stock.adjustments.sub') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- ============ Raise an adjustment ============ --}}
        <div class="lg:col-span-4">
            @can('stock.stock_adjustment')
                <div class="glass-card p-6">
                    <h2 class="text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.stock.adjustments.raise') }}</h2>
                    <p class="mt-1 text-center text-xs text-slate-400">
                        {{ __('forecourt.stock.adjustments.raise_note') }}
                    </p>

                    <form method="POST" action="{{ route('stock.adjustments.store') }}" novalidate class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="tank_id" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.tank') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <select id="tank_id" name="tank_id" class="input-3d @error('tank_id') border-red-400 @enderror" required>
                                    <option value="">{{ __('forecourt.common.select_tank') }}</option>
                                    @foreach ($tanks as $tank)
                                        <option value="{{ $tank->id }}" @selected((string) old('tank_id') === (string) $tank->id)>
                                            {{ $tank->displayName() }} — {{ $tank->fuelName() }}
                                            ({{ __('forecourt.stock.adjustments.stock') }}: {{ number_format((float) $tank->current_stock, 3) }} L)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('tank_id') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="type" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.type') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <select id="type" name="type" class="input-3d @error('type') border-red-400 @enderror" required>
                                    @foreach (['IN' => 'Stock in', 'OUT' => 'Stock out', 'LOSS' => 'Loss / evaporation', 'CORRECTION' => 'Correction'] as $k => $v)
                                        <option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('type') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="quantity" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.stock.adjustments.qty_l') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <input type="number" step="0.001" min="0.001" id="quantity" name="quantity"
                                       value="{{ old('quantity') }}"
                                       class="input-3d @error('quantity') border-red-400 @enderror" required>
                            </div>
                            @error('quantity') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="reason" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.reason') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                                       class="input-3d @error('reason') border-red-400 @enderror"
                                       maxlength="200" required placeholder="{{ __('forecourt.stock.adjustments.reason_placeholder') }}">
                            </div>
                            @error('reason') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="notes" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.notes') }}</label>
                            <div class="field-3d">
                                <textarea id="notes" name="notes" rows="2" class="input-3d">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-3d btn-3d-primary w-full">{{ __('forecourt.stock.adjustments.submit_approval') }}</button>
                    </form>
                </div>
            @endcan
        </div>

        {{-- ============ Adjustments table ============ --}}
        <div class="lg:col-span-8">
            <div class="glass-card overflow-hidden">
                <div class="table-3d">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('forecourt.common.reference') }}</th>
                                <th>{{ __('forecourt.common.tank') }}</th>
                                <th>{{ __('forecourt.common.type') }}</th>
                                <th>{{ __('forecourt.stock.adjustments.qty') }}</th>
                                <th>{{ __('forecourt.stock.adjustments.before_after') }}</th>
                                <th>{{ __('forecourt.common.reason') }}</th>
                                <th>{{ __('forecourt.common.status') }}</th>
                                <th>{{ __('forecourt.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $a)
                                @php
                                    $statusChip = match ($a->statusBadgeClass()) {
                                        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                        'danger' => 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                        default => 'bg-slate-200/70 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-xs"><code class="rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $a->reference_number }}</code></td>
                                    <td class="text-xs font-semibold">{{ $a->tank?->tank_number ?? '—' }}</td>
                                    <td class="text-xs">{{ $a->type }}</td>
                                    <td class="tabular text-xs font-semibold">{{ number_format((float) $a->quantity, 3) }}</td>
                                    <td class="tabular text-xs">
                                        @if ($a->stock_after !== null)
                                            {{ number_format((float) $a->stock_before, 3) }} →
                                            <strong>{{ number_format((float) $a->stock_after, 3) }}</strong>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        {{ $a->reason }}
                                        @if ($a->rejection_reason)
                                            <br><span class="font-semibold text-red-600">{{ __('forecourt.stock.adjustments.rejected') }} {{ $a->rejection_reason }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusChip }}">{{ $a->status }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if ($a->isPending())
                                            @can('stock.approve')
                                                <form method="POST" action="{{ route('stock.adjustments.approve', $a) }}"
                                                      class="inline"
                                                      onsubmit="return confirm('Approve this adjustment? Stock will be updated.');">
                                                    @csrf
                                                    <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">{{ __('forecourt.stock.adjustments.approve') }}</button>
                                                </form>

                                                <button type="button" class="btn-3d btn-3d-primary btn-3d-sm"
                                                        @click="rejectId = {{ $a->id }}">
                                                    {{ __('forecourt.stock.adjustments.reject') }}
                                                </button>
                                            @endcan
                                        @else
                                            <span class="text-xs text-slate-400">
                                                {{ __('forecourt.stock.adjustments.by_frag') }} {{ $a->approver?->name ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-slate-400">{{ __('forecourt.stock.adjustments.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $adjustments->links() }}</div>
        </div>
    </div>

    {{-- ================= Reject modals (page end — kabhi table ke andar nahi) ================= --}}
    @foreach ($adjustments as $a)
        @if ($a->isPending())
            <div x-show="rejectId === {{ $a->id }}" x-cloak
                 class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 backdrop-blur-[2px] sm:items-center sm:p-6"
                 role="dialog" aria-modal="true">
                <div @click.outside="rejectId = null" class="glass-card modal-bounce relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-b-none p-6 sm:rounded-b-2xl">
                    <form method="POST" action="{{ route('stock.adjustments.reject', $a) }}">
                        @csrf
                        <h3 class="text-center text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.stock.adjustments.reject') }} {{ $a->reference_number }}</h3>

                        <div class="mt-4">
                            <label for="rej{{ $a->id }}" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.reason') }} <span class="text-red-500">*</span></label>
                            <div class="field-3d">
                                <input type="text" id="rej{{ $a->id }}" name="rejection_reason"
                                       class="input-3d" maxlength="500" required>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap justify-center gap-3">
                            <button type="button" class="btn-3d btn-3d-ghost" @click="rejectId = null">{{ __('ui.actions.cancel') }}</button>
                            <button type="submit" class="btn-3d btn-3d-primary">{{ __('forecourt.stock.adjustments.reject') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
