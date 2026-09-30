@if (request('mode') === 'desktop')
    @extends('layouts.app')

    @section('title', 'Dashboard')
    @section('breadcrumb')
        <li>/</li>
        <li class="font-semibold text-slate-700 dark:text-slate-300">Dashboard</li>
    @endsection

    @section('content')
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-bold">Dashboard (Desktop Mode)</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-primary">
                📱 Switch to Mobile App Launcher
            </a>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Branches', 'value' => $branchCount ?? 1],
                ['label' => 'Active Users', 'value' => $userCount ?? 1],
                ['label' => 'Roles', 'value' => $roleCount ?? 6],
                ['label' => 'Active Branch', 'value' => $activeBranchName ?? 'Mehar Filling Station', 'text' => true],
            ] as $tile)
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $tile['label'] }}</div>
                    <div @class([
                        'mt-1 text-2xl font-bold tabular text-navy-800 dark:text-white',
                        'text-lg' => $tile['text'] ?? false,
                    ])>{{ $tile['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-2">Forecourt Live Status</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Mehar Filling Station (Vital Petroleum Franchise) — Sheikhupura forecourt ERP system is live and operating.
            </p>
        </div>
        
        <!-- Auto-Generated Reports -->
        @include('dashboard.report-widgets')
    @endsection
@else
    @extends('layouts.app', ['hideChrome' => true])

    @section('title', 'مہر فلنگ اسٹیشن — موبائل ایپ لانچر (وائٹل پیٹرولیم)')

    @section('content')
        <livewire:dashboard.app-launcher />
    @endsection
@endif
