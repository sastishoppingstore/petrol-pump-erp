@extends('layouts.app')

@section('title', 'Nozzles')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Nozzles</li>
@endsection

{{--
    Nozzles — 2026 redesign + layout bug fix.

    BUG JO FIX HUA: meter-correction ka modal div pehle table ke tbody
    ke andar har row ke saath render hota tha. Div table ke andar valid
    HTML nahi hai — browser use tbody se bahar nikaal deta tha, is liye
    "Corrected meter / Reason" fields page ke neeche behke hue (bleed)
    nazar aate thay, aur Bootstrap JS na hone se modal khulta bhi nahi tha.

    AB: tamam modals table ke BAAD, alag section me render hote hain aur
    Alpine (x-data) se khulte/band hote hain. Form, route aur fields
    (new_meter, reason) bilkul pehle jaisay hain — backend same hai.
--}}
@section('content')
<div x-data="{ correctId: null }" @keydown.escape.window="correctId = null">

    {{-- ================= Header ================= --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Nozzles</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $nozzles->total() }} nozzle{{ $nozzles->total() === 1 ? '' : 's' }} across all dispensers</p>
        </div>
        @can('fuel.create')
            <a href="{{ route('nozzles.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                <span aria-hidden="true">＋</span> Add Nozzle
            </a>
        @endcan
    </div>

    @if ($nozzles->isNotEmpty())
        {{-- ================= Desktop / tablet table ================= --}}
        <div class="glass-card hidden overflow-hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:border-slate-700/60">
                            <th class="px-5 py-3.5">Dispenser</th>
                            <th class="px-5 py-3.5">Nozzle</th>
                            <th class="px-5 py-3.5">Fuel</th>
                            <th class="px-5 py-3.5">Tank</th>
                            <th class="px-5 py-3.5 text-right">Opening Meter</th>
                            <th class="px-5 py-3.5 text-right">Current Meter</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($nozzles as $nozzle)
                            <tr class="transition hover:bg-white/60 dark:hover:bg-white/5">
                                <td class="px-5 py-3.5"><code class="rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $nozzle->dispenser?->dispenser_number ?? '—' }}</code></td>
                                <td class="px-5 py-3.5 font-bold text-slate-800 dark:text-slate-100">{{ $nozzle->nozzle_number }}</td>
                                <td class="px-5 py-3.5">{{ $nozzle->fuelProduct?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-500">{{ $nozzle->tank?->tank_number ?? '—' }}</td>
                                <td class="tabular px-5 py-3.5 text-right text-slate-500">{{ number_format((float) $nozzle->opening_meter, 3) }}</td>
                                <td class="tabular px-5 py-3.5 text-right font-black text-slate-800 dark:text-slate-100">{{ number_format((float) $nozzle->current_meter, 3) }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="pill-status {{ $nozzle->isActive() ? 'pill-active' : 'pill-inactive' }}">
                                        <span class="dot" aria-hidden="true"></span>{{ $nozzle->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('fuel.edit')
                                            <a href="{{ route('nozzles.edit', $nozzle) }}" class="btn-3d btn-3d-ghost btn-3d-sm">Edit</a>
                                        @endcan
                                        @can('stock.stock_adjustment')
                                            <button type="button" @click="correctId = {{ $nozzle->id }}" class="btn-3d btn-3d-amber btn-3d-sm">
                                                Correct meter
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================= Mobile cards ================= --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:hidden">
            @foreach ($nozzles as $nozzle)
                <article class="glass-card card-3d p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-base font-black text-slate-900 dark:text-white">
                                {{ $nozzle->dispenser?->dispenser_number ?? '—' }} / {{ $nozzle->nozzle_number }}
                            </div>
                            <div class="mt-0.5 text-xs font-semibold text-slate-500">
                                {{ $nozzle->fuelProduct?->name ?? '—' }} • Tank {{ $nozzle->tank?->tank_number ?? '—' }}
                            </div>
                        </div>
                        <span class="pill-status {{ $nozzle->isActive() ? 'pill-active' : 'pill-inactive' }}">
                            <span class="dot" aria-hidden="true"></span>{{ $nozzle->status }}
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-xl bg-slate-900/[0.03] px-2 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Opening Meter</dt>
                            <dd class="tabular mt-0.5 text-sm font-bold text-slate-600 dark:text-slate-300">{{ number_format((float) $nozzle->opening_meter, 3) }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-900/[0.03] px-2 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Current Meter</dt>
                            <dd class="tabular mt-0.5 text-sm font-black text-slate-800 dark:text-slate-100">{{ number_format((float) $nozzle->current_meter, 3) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3 flex gap-2">
                        @can('fuel.edit')
                            <a href="{{ route('nozzles.edit', $nozzle) }}" class="btn-3d btn-3d-ghost btn-3d-sm flex-1">Edit</a>
                        @endcan
                        @can('stock.stock_adjustment')
                            <button type="button" @click="correctId = {{ $nozzle->id }}" class="btn-3d btn-3d-amber btn-3d-sm flex-1">
                                Correct meter
                            </button>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="glass-card p-10 text-center">
            <div class="text-4xl" aria-hidden="true">🔧</div>
            <p class="mt-3 font-semibold text-slate-600 dark:text-slate-300">No nozzles yet.</p>
            @can('fuel.create')
                <a href="{{ route('nozzles.create') }}" class="btn-3d btn-3d-primary mt-4">Add the first one</a>
            @endcan
        </div>
    @endif

    <div class="mt-6">{{ $nozzles->links() }}</div>

    {{-- ================= Floating Action Button ================= --}}
    @can('fuel.create')
        <a href="{{ route('nozzles.create') }}" class="fab-3d" title="Add a new nozzle">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> Add Nozzle
        </a>
    @endcan

    {{-- ================= Meter correction modals =================
         NOTE: ye modals jaan boojh kar table ke BAAD hain — table ke andar
         modal rakhna hi purana layout bug tha. Har modal ek saaf card hai:
         label hamesha input ke UPAR, proper gap, koi overflow nahi. --}}
    @can('stock.stock_adjustment')
        @foreach ($nozzles as $nozzle)
            <div x-show="correctId === {{ $nozzle->id }}" x-cloak
                 class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-4"
                 role="dialog" aria-modal="true" aria-label="Correct meter — {{ $nozzle->label() }}">
                {{-- Backdrop click = band --}}
                <div class="absolute inset-0" @click="correctId = null" aria-hidden="true"></div>

                <form method="POST" action="{{ route('nozzles.meter-correction', $nozzle) }}"
                      class="glass-card modal-bounce relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-b-none p-6 sm:rounded-b-2xl">
                    @csrf

                    <div class="mb-1 flex items-start justify-between gap-3">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Correct meter — {{ $nozzle->label() }}</h2>
                        <button type="button" @click="correctId = null"
                                class="rounded-lg bg-slate-900/5 px-2 py-1 text-sm font-bold text-slate-500 transition hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300"
                                aria-label="Close">✕</button>
                    </div>
                    <p class="text-sm text-slate-500">
                        Current meter:
                        <strong class="tabular text-slate-800 dark:text-slate-100">{{ number_format((float) $nozzle->current_meter, 3) }}</strong>.
                        A correction writes an immutable CORRECTION record to the audit trail.
                    </p>

                    {{-- Label-above-input, saaf vertical rhythm (gap-4) --}}
                    <div class="mt-5 flex flex-col gap-4">
                        <div class="field-3d">
                            <label for="new_meter_{{ $nozzle->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                                Corrected meter <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="0.001" min="0"
                                   id="new_meter_{{ $nozzle->id }}" name="new_meter"
                                   value="{{ old('new_meter') }}"
                                   class="input-3d tabular @error('new_meter') !border-red-500 @enderror" required>
                            @error('new_meter') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="field-3d">
                            <label for="reason_{{ $nozzle->id }}" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                                Reason <span class="text-red-600">*</span>
                            </label>
                            <input type="text" id="reason_{{ $nozzle->id }}" name="reason"
                                   value="{{ old('reason') }}"
                                   class="input-3d @error('reason') !border-red-500 @enderror"
                                   maxlength="500" required
                                   placeholder="Meter rollover / faulty meter">
                            @error('reason') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="correctId = null" class="btn-3d btn-3d-ghost">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-amber">Apply correction</button>
                    </div>
                </form>
            </div>
        @endforeach
    @endcan
</div>
@endsection
