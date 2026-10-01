@extends('layouts.app')

@section('title', 'آڈٹ لاگز / Audit Trail')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Audit Logs</li>
@endsection

{{--
    Audit Logs — 2026 redesign.
    Page head centered; filter toolbar glass-card me (.field-3d/.input-3d);
    table .table-3d wrapper me — tamam cells center. Filter field names,
    selected logic aur export route (query same) pehle jaisay hi hain.
--}}
@section('content')
    <div class="page-head">
        <h1>🧾 آڈٹ لاگز اور سیکیورٹی ٹریل / Audit Trail</h1>
        <p>Immutable, append-only tamper-evident record of all system events, sales, voids, meter corrections &amp; ledger adjustments.</p>
        <div class="page-actions">
            <a href="{{ route('audit-logs.export', request()->query()) }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                ایکسپورٹ CSV / Export CSV
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('audit-logs.index') }}" class="glass-card mb-6 p-5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="field-3d">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">ماڈیول / Module</label>
                <select name="module" class="input-3d text-center text-sm">
                    <option value="">تمام ماڈیولز / All Modules</option>
                    @foreach ($modules as $mod)
                        <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ strtoupper($mod) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">صارف / User</label>
                <select name="user_id" class="input-3d text-center text-sm">
                    <option value="">تمام صارفین / All Users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">تاریخ سے / From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-3d text-center text-sm">
            </div>
            <div class="field-3d">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">تاریخ تک / To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-3d text-center text-sm">
            </div>
        </div>
        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('audit-logs.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">ری سیٹ / Reset</a>
            <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">فلٹر کریں / Filter</button>
        </div>
    </form>

    {{-- Audit Log Table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>تاریخ و وقت / Timestamp</th>
                        <th>صارف / User</th>
                        <th>ماڈیول / Module</th>
                        <th>ایکشن / Action</th>
                        <th>تفصیل / Details</th>
                        <th>IP پتہ / IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap font-mono text-slate-500">#{{ $log->id }}</td>
                            <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ $log->created_at?->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="rounded-full bg-slate-900/5 px-2.5 py-0.5 font-mono text-[11px] font-bold text-slate-700 dark:bg-white/10 dark:text-slate-200">
                                    {{ $log->module }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap font-semibold text-slate-900 dark:text-white">
                                {{ $log->action }}
                            </td>
                            <td class="max-w-xs truncate font-mono text-[11px] text-slate-500" title="{{ json_encode($log->new_data ?? $log->old_data) }}">
                                @if ($log->new_data)
                                    {{ Str::limit(json_encode($log->new_data), 60) }}
                                @elseif ($log->old_data)
                                    {{ Str::limit(json_encode($log->old_data), 60) }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap font-mono text-[11px] text-slate-400">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-slate-400">کوئی لاگ ریکارڈ موجود نہیں ہے۔</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
