@extends('layouts.app')

@section('title', 'Stock Movements')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('stock.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Stock</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Movements</li>
@endsection

{{--
    Stock Movements — 2026 redesign.
    Filter + ledger table glass boxes me, cells centered.
    Type chip ka rang model ke typeBadgeClass() se map hota hai.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>🔄 Stock Movements</h1>
        <p>Every tank stock movement ever recorded</p>
    </div>

    {{-- ================= Filter ================= --}}
    <form method="GET" action="{{ route('stock.movements') }}" class="glass-card mb-5 p-5">
        <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label for="f_tank" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Tank</label>
                <div class="field-3d">
                    <select id="f_tank" name="tank_id" class="input-3d">
                        <option value="">All tanks</option>
                        @foreach ($tanks as $tank)
                            <option value="{{ $tank->id }}" @selected((string) request('tank_id') === (string) $tank->id)>
                                {{ $tank->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="lg:col-span-3">
                <label for="f_type" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Type</label>
                <div class="field-3d">
                    <select id="f_type" name="type" class="input-3d">
                        <option value="">All types</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="lg:col-span-2">
                <label for="f_from" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">From</label>
                <div class="field-3d">
                    <input type="date" id="f_from" name="from" value="{{ request('from') }}" class="input-3d">
                </div>
            </div>
            <div class="lg:col-span-2">
                <label for="f_to" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">To</label>
                <div class="field-3d">
                    <input type="date" id="f_to" name="to" value="{{ request('to') }}" class="input-3d">
                </div>
            </div>
            <div class="lg:col-span-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">Filter</button>
            </div>
        </div>
    </form>

    {{-- ================= Movements table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Tank</th>
                        <th>Fuel</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Reference</th>
                        <th>Reason</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $m)
                        @php
                            $chip = match ($m->typeBadgeClass()) {
                                'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                'primary' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
                                'danger' => 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                default => 'bg-slate-200/70 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300',
                            };
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $m->created_at?->format('d M Y H:i') }}</td>
                            <td class="text-xs font-semibold">{{ $m->tank?->tank_number ?? '—' }}</td>
                            <td class="text-xs">{{ $m->fuelProduct?->name ?? '—' }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $chip }}">{{ $m->type }}</span>
                            </td>
                            <td class="tabular font-black
                                {{ \App\Support\Quantity::isNegative($m->quantity) ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ number_format((float) $m->quantity, 3) }}
                            </td>
                            <td class="tabular text-xs">{{ number_format((float) $m->before_quantity, 3) }}</td>
                            <td class="tabular text-xs">{{ number_format((float) $m->after_quantity, 3) }}</td>
                            <td class="text-xs text-slate-400">
                                @if ($m->reference_type)
                                    {{ class_basename($m->reference_type) }}#{{ $m->reference_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-xs text-slate-400">{{ $m->reason ?: '—' }}</td>
                            <td class="text-xs text-slate-400">{{ $m->user?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-slate-400">No movements recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $movements->links() }}</div>
@endsection
