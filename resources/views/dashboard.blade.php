@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Dashboard</li>
@endsection

@section('content')
    <h1 class="mb-4 text-xl font-bold">Dashboard</h1>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Branches', 'value' => $branchCount],
            ['label' => 'Active Users', 'value' => $userCount],
            ['label' => 'Roles', 'value' => $roleCount],
            ['label' => 'Active Branch', 'value' => $activeBranchName ?: 'All', 'text' => true],
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
        <p class="mb-0 text-slate-600 dark:text-slate-400">
            Foundation is in place: authentication, roles and permissions, branch scoping,
            fuel master data, tanks with locking stock movements, shifts, and the sale
            transaction. Remaining modules build on this.
        </p>
    </div>
@endsection
