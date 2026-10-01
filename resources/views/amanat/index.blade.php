@extends('layouts.app')

@section('title', __('admin.amanat.title'))
@section('breadcrumb')
    <li class="text-slate-500">{{ __('admin.amanat.title') }}</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ __('admin.amanat.title') }}
            <span class="ml-2 align-middle rounded bg-vital-primary/10 px-2.5 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
                {{ __('admin.amanat.badge') }}
            </span>
        </h1>
        <p>{{ __('admin.amanat.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('amanat.create') }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.amanat.new_entry_btn') }}
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">{{ __('admin.amanat.stat_total_balance') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['total_balance'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ __('admin.amanat.stat_total_balance_sub') }}</div>
        </div>
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">{{ __('admin.amanat.stat_customers') }}</div>
            <div class="stat-value tabular">{{ $stats['customers_with_balance'] ?? 0 }}</div>
            <div class="stat-sub">{{ __('admin.amanat.stat_customers_sub') }}</div>
        </div>
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">{{ __('admin.amanat.stat_deposits_month') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['deposits_this_month'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ now()->format('F Y') }}</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">{{ __('admin.amanat.stat_deductions_month') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['deductions_this_month'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ now()->format('F Y') }}</div>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('amanat.index') }}" class="glass-card p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="field-3d sm:col-span-2">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.amanat.search_label') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.amanat.search_placeholder') }}" class="input-3d w-full text-center text-sm">
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">{{ __('ui.actions.filter') }}</button>
                <a href="{{ route('amanat.index') }}" class="btn-3d btn-3d-ghost">{{ __('admin.employees.reset') }}</a>
            </div>
        </div>
    </form>

    {{-- Customers with balances --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.amanat.customer') }}</th>
                        <th>{{ __('admin.common.code') }}</th>
                        <th>{{ __('admin.common.phone') }}</th>
                        <th>{{ __('admin.amanat.balance') }}</th>
                        <th>{{ __('admin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        @php $balance = (float) ($balances[$customer->id] ?? 0); @endphp
                        <tr>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $customer->name }}</div>
                                <div class="text-xs text-slate-400">{{ $customer->branch?->name }}</div>
                            </td>
                            <td class="tabular font-mono text-xs">{{ $customer->code }}</td>
                            <td class="text-xs text-slate-600 dark:text-slate-300">{{ $customer->phone ?? '—' }}</td>
                            <td class="tabular font-mono font-bold {{ $balance > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' }}">
                                Rs. {{ number_format($balance, 2) }}
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('amanat.statement', $customer) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('admin.amanat.statement') }}</a>
                                    <a href="{{ route('amanat.create', ['customer_id' => $customer->id]) }}" class="btn-3d btn-3d-success btn-3d-sm">{{ __('admin.amanat.new_entry') }}</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                {{ __('admin.amanat.none') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $customers->links() }}</div>
    </div>

    <a href="{{ route('amanat.create') }}" class="fab-3d" title="{{ __('admin.amanat.fab_title') }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>
</div>
@endsection
