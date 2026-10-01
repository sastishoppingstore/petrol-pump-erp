@extends('layouts.app')

@section('title', 'Meter Readings')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Meter Readings</li>
@endsection

{{--
    Meter Readings — 2026 redesign.
    Filter + history table glass boxes me, tamam cells centered.
    Filter fields aur data pehle jaisay hi hain.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>🎛️ Meter Readings</h1>
        <p>Nozzle meter history — sales, openings, closings &amp; corrections</p>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('forecourt.meters.index') }}" class="btn-3d btn-3d-ghost">Forecourt Terminal</a>
            <a href="{{ route('meter-readings.create') }}" class="btn-3d btn-3d-primary">➕ New Reading Entry</a>
        </div>
    </div>

    {{-- ================= Filter ================= --}}
    <form method="GET" action="{{ route('meter-readings.index') }}" class="glass-card mb-5 p-5">
        <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-12">
            <div class="sm:col-span-5">
                <label for="filter_nozzle" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Nozzle</label>
                <div class="field-3d">
                    <select id="filter_nozzle" name="nozzle_id" class="input-3d">
                        <option value="">All nozzles</option>
                        @foreach ($nozzles as $n)
                            <option value="{{ $n->id }}" @selected((string) request('nozzle_id') === (string) $n->id)>
                                {{ $n->label() }} — {{ $n->fuelProduct?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="sm:col-span-4">
                <label for="filter_type" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Type</label>
                <div class="field-3d">
                    <select id="filter_type" name="type" class="input-3d">
                        <option value="">All types</option>
                        @foreach (['SALE', 'OPENING', 'CLOSING', 'CORRECTION'] as $t)
                            <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="btn-3d btn-3d-navy w-full">Filter</button>
            </div>
        </div>
    </form>

    {{-- ================= History table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Nozzle</th>
                        <th>Type</th>
                        <th>Previous</th>
                        <th>Current</th>
                        <th>Quantity</th>
                        <th>By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($readings as $reading)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $reading->created_at?->format('d M Y H:i') }}</td>
                            <td class="text-xs font-semibold">{{ $reading->nozzle?->label() ?? '—' }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $reading->type === 'CORRECTION' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-slate-200/70 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300' }}">
                                    {{ $reading->type }}
                                </span>
                            </td>
                            <td class="tabular text-xs">{{ number_format((float) $reading->previous_meter, 3) }}</td>
                            <td class="tabular text-xs font-black text-slate-800 dark:text-white">{{ number_format((float) $reading->current_meter, 3) }}</td>
                            <td class="tabular text-xs font-semibold">{{ number_format((float) $reading->quantity, 3) }}</td>
                            <td class="text-xs text-slate-400">{{ $reading->user?->name ?? 'System' }}</td>
                            <td class="text-xs text-slate-400">{{ $reading->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-slate-400">No meter readings recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $readings->links() }}</div>
@endsection
