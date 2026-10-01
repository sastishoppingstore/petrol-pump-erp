@extends('layouts.app')

@section('title', 'Amanat Deposits / امانت')
@section('breadcrumb')
    <li class="text-slate-500">Amanat Deposits</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>Amanat Deposits / امانت
            <span class="ml-2 align-middle rounded bg-vital-primary/10 px-2.5 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
                Customer Prepaid
            </span>
        </h1>
        <p>Customers ke prepaid trust deposits — jama, katauti aur balance ka mukammal ledger.</p>
        <div class="page-actions">
            <a href="{{ route('amanat.create') }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Amanat Entry
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Total Amanat Balance</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['total_balance'] ?? 0), 2) }}</div>
            <div class="stat-sub">Customers ke paas jama kul raqam</div>
        </div>
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Customers With Balance</div>
            <div class="stat-value tabular">{{ $stats['customers_with_balance'] ?? 0 }}</div>
            <div class="stat-sub">Active amanat holders</div>
        </div>
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">Deposits This Month</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['deposits_this_month'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ now()->format('F Y') }}</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Deductions This Month</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['deductions_this_month'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ now()->format('F Y') }}</div>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('amanat.index') }}" class="glass-card p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="field-3d sm:col-span-2">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Search Customer / کسٹمر تلاش کریں</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, code ya phone number" class="input-3d w-full text-center text-sm">
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">Filter</button>
                <a href="{{ route('amanat.index') }}" class="btn-3d btn-3d-ghost">Reset</a>
            </div>
        </div>
    </form>

    {{-- Customers with balances --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Customer / کسٹمر</th>
                        <th>Code</th>
                        <th>Phone</th>
                        <th>Amanat Balance (بیلنس)</th>
                        <th>Actions</th>
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
                                    <a href="{{ route('amanat.statement', $customer) }}" class="btn-3d btn-3d-ghost btn-3d-sm">Statement</a>
                                    <a href="{{ route('amanat.create', ['customer_id' => $customer->id]) }}" class="btn-3d btn-3d-success btn-3d-sm">New Entry</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                No customers found. Pehle customer add karein, phir un ki amanat yahan nazar aayegi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $customers->links() }}</div>
    </div>

    <a href="{{ route('amanat.create') }}" class="fab-3d" title="Record a new amanat entry">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>
</div>
@endsection
