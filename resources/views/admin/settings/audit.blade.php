@extends('layouts.app')

@section('content')
{{--
    Settings Change History (admin audit) — 2026 redesign.
    Page head centered; filter inputs glass box me; table .table-3d;
    stats .stat-tile-3d. SAKHT NOTE: neeche wala filter script table ke
    column order par depend karta hai (cells[0] = date, cells[1] = user) —
    column order aur script bilkul pehle jaisa rakha gaya hai. Filter
    input ids (searchUser / filterDate) bhi waisay hi hain.
--}}
<div class="space-y-6">
    <div class="page-head">
        <h1>📋 {{ __('admin.settings_audit.title') }}</h1>
        <p>{{ __('admin.settings_audit.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('admin.settings.index') }}" class="btn-3d btn-3d-primary">
                ← {{ __('admin.settings_audit.back') }}
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-card mx-auto grid max-w-3xl gap-4 p-4 sm:grid-cols-2">
        <div class="field-3d">
            <input type="text" class="input-3d text-center text-sm" id="searchUser" placeholder="{{ __('admin.settings_audit.search_user_ph') }}">
        </div>
        <div class="field-3d">
            <input type="date" class="input-3d text-center text-sm" id="filterDate" placeholder="{{ __('admin.settings_audit.filter_date_ph') }}">
        </div>
    </div>

    <!-- Audit Table -->
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.settings_audit.th_datetime') }}</th>
                        <th>{{ __('admin.audit_logs.user_label') }}</th>
                        <th>{{ __('admin.settings_audit.th_key') }}</th>
                        <th>{{ __('admin.settings_audit.th_old') }}</th>
                        <th>{{ __('admin.settings_audit.th_new') }}</th>
                        <th>{{ __('admin.audit_logs.th_ip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($changes as $change)
                        @php
                            $oldData = json_decode($change->old_data, true) ?? [];
                            $newData = json_decode($change->new_data, true) ?? [];
                            $key = $newData['key'] ?? 'N/A';
                            $oldValue = $oldData['value'] ?? '—';
                            $newValue = $newData['value'] ?? '—';
                        @endphp
                        <tr>
                            <td class="text-sm">
                                <strong>{{ $change->created_at->format('d M Y H:i') }}</strong>
                            </td>
                            <td>
                                <span class="rounded-full bg-vital-primary/15 px-2.5 py-0.5 text-xs font-bold text-vital-darkred dark:text-red-300">
                                    {{ $change->user->name ?? __('admin.audit_logs.system') }}
                                </span>
                            </td>
                            <td>
                                <code class="rounded bg-slate-900/5 px-1.5 py-0.5 text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $key }}</code>
                            </td>
                            <td>
                                <span class="font-semibold text-red-500">{{ $oldValue }}</span>
                            </td>
                            <td>
                                <span class="font-semibold text-emerald-600">{{ $newValue }}</span>
                            </td>
                            <td class="text-sm text-slate-400">
                                {{ $change->ip }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-slate-400">
                                {{ __('admin.settings_audit.none') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
            {{ $changes->links() }}
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('admin.settings_audit.stat_total') }}</div>
            <div class="stat-value kpi-num">{{ $changes->total() }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">{{ __('admin.settings_audit.stat_month') }}</div>
            <div class="stat-value kpi-num">{{ $changes->where('created_at', '>=', now()->startOfMonth())->count() }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('admin.settings_audit.stat_today') }}</div>
            <div class="stat-value kpi-num">{{ $changes->where('created_at', '>=', now()->startOfDay())->count() }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('admin.settings_audit.stat_week') }}</div>
            <div class="stat-value kpi-num">{{ $changes->where('created_at', '>=', now()->subDays(7))->count() }}</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchUser = document.getElementById('searchUser');
    const filterDate = document.getElementById('filterDate');

    // Simple client-side filtering
    const rows = document.querySelectorAll('tbody tr');

    function filterTable() {
        const userFilter = searchUser.value.toLowerCase();
        const dateFilter = filterDate.value;

        rows.forEach(row => {
            const user = row.cells[1].textContent.toLowerCase();
            const dateCell = row.cells[0].textContent;

            let show = true;
            if (userFilter && !user.includes(userFilter)) show = false;
            if (dateFilter && !dateCell.includes(dateFilter)) show = false;

            row.style.display = show ? '' : 'none';
        });
    }

    searchUser.addEventListener('keyup', filterTable);
    filterDate.addEventListener('change', filterTable);
});
</script>
@endsection
