@extends('layouts.app')

@section('title', 'آڈٹ لاگز / Audit Trail')
@section('breadcrumb')
    <li class="text-slate-500">Audit Logs</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">آڈٹ لاگز اور سیکیورٹی ٹریل / Audit Trail</h1>
            <p class="mt-1 text-sm text-slate-500">
                Immutable, append-only tamper-evident record of all system events, sales, voids, meter corrections &amp; ledger adjustments.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('audit-logs.export', request()->query()) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                ایکسپورٹ CSV / Export CSV
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('audit-logs.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 sm:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">ماڈیول / Module</label>
                <select name="module" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <option value="">تمام ماڈیولز / All Modules</option>
                    @foreach ($modules as $mod)
                        <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ strtoupper($mod) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">صارف / User</label>
                <select name="user_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <option value="">تمام صارفین / All Users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">تاریخ سے / From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">تاریخ تک / To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>
        </div>
        <div class="mt-3 flex justify-end gap-2">
            <a href="{{ route('audit-logs.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">ری سیٹ / Reset</a>
            <button type="submit" class="rounded-lg bg-red-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-red-700">فلٹر کریں / Filter</button>
        </div>
    </form>

    {{-- Audit Log Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                <thead class="bg-slate-50 font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">تاریخ و وقت / Timestamp</th>
                        <th class="px-4 py-3">صارف / User</th>
                        <th class="px-4 py-3">ماڈیول / Module</th>
                        <th class="px-4 py-3">ایکشن / Action</th>
                        <th class="px-4 py-3">تفصیل / Details</th>
                        <th class="px-4 py-3">IP پتہ / IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-500">#{{ $log->id }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">
                                {{ $log->created_at?->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $log->module }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-white">
                                {{ $log->action }}
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 font-mono text-[11px] text-slate-500" title="{{ json_encode($log->new_data ?? $log->old_data) }}">
                                @if ($log->new_data)
                                    {{ Str::limit(json_encode($log->new_data), 60) }}
                                @elseif ($log->old_data)
                                    {{ Str::limit(json_encode($log->old_data), 60) }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[11px] text-slate-400">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">کوئی لاگ ریکارڈ موجود نہیں ہے۔</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4 dark:border-slate-800">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
