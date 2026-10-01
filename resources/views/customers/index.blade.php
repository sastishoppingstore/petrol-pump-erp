@extends('layouts.app')

@section('title', 'Customers & Udhaar (Tile 3)')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Customers</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>🧑 Customers &amp; Udhaar Management</h1>
        <p>Customer credit accounts, vehicle fleet registration, credit limits, and running balance ledgers.</p>
        <div class="page-actions">
            <a href="{{ route('customers.ageing') }}" class="btn-3d btn-3d-amber">
                <span aria-hidden="true">⏱️</span> Udhaar Ageing Report
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_CREATE))
                <a href="{{ route('customers.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> New Customer
                </a>
            @endif
        </div>
    </div>

    {{-- ================= Stats Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">Total Udhaar Outstanding</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($totalOutstanding) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($totalOutstanding) }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">Total Approved Credit Limit</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($totalCreditLimit) }}</div>
            <div class="stat-sub">Across all registered credit clients</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">Active Customer Accounts</div>
            <div class="stat-value kpi-num">{{ number_format($activeCustomersCount) }}</div>
            <div class="stat-sub">Verified CNIC &amp; Phone records</div>
        </div>
    </div>

    {{-- ================= Filter and Search ================= --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="field-3d relative flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by customer name, code, phone (03XX), or CNIC..."
                       class="input-3d pr-10">
                @if($search)
                    <a href="{{ route('customers.index') }}" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600">✕</a>
                @endif
            </div>

            <div class="field-3d w-full sm:w-44">
                <select name="status" onchange="this.form.submit()" class="input-3d">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn-3d btn-3d-navy">
                Filter
            </button>
        </form>
    </div>

    {{-- ================= 3D Customer Cards ================= --}}
    @if($customers->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach($customers as $c)
                <article class="glass-card card-3d relative overflow-hidden p-5 text-center">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-vital-primary to-vital-darkred" aria-hidden="true"></div>

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-vital-primary/10 text-2xl shadow-inner" aria-hidden="true">🧑</div>
                    <h2 class="mt-3 truncate text-base font-black text-slate-900 dark:text-white">{{ $c->name }}</h2>
                    <span class="mt-1 inline-block rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono text-[11px] font-bold text-vital-primary dark:bg-white/10 dark:text-red-300">{{ $c->code }}</span>

                    <div class="mt-3 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                        <div class="font-mono">📱 {{ $c->phone ?? 'N/A' }}</div>
                        <div class="font-mono">🆔 {{ $c->cnic ?? 'No CNIC' }}</div>
                        <div>🚗 {{ $c->vehicles_count }} vehicle{{ $c->vehicles_count == 1 ? '' : 's' }} registered</div>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Credit Limit</dt>
                            <dd class="tabular mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">
                                @if($c->creditLimitIsUnlimited())
                                    Unlimited
                                @else
                                    {{ \App\Support\PakistaniCurrency::format($c->credit_limit, true, 0) }}
                                @endif
                            </dd>
                        </div>
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Outstanding</dt>
                            <dd class="tabular mt-0.5 text-sm font-black {{ \App\Support\Money::compare($c->current_balance, '0.00') > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-200' }}">
                                {{ \App\Support\PakistaniCurrency::format($c->current_balance) }}
                            </dd>
                        </div>
                    </dl>

                    @if(! $c->creditLimitIsUnlimited())
                        @php
                            $limit = (float) $c->credit_limit;
                            $bal = (float) $c->current_balance;
                            $pct = $limit > 0 ? min(100, round(($bal / $limit) * 100)) : 0;
                        @endphp
                        <div class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-slate-400">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full {{ $pct > 90 ? 'bg-red-600' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span>{{ $pct }}% used</span>
                        </div>
                    @endif

                    <div class="mt-3">
                        <span class="pill-status {{ $c->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                            <span class="dot" aria-hidden="true"></span>{{ $c->status }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('customers.show', $c) }}" class="btn-3d btn-3d-ghost btn-3d-sm">Ledger &amp; Profile</a>
                        <a href="{{ route('customers.statement', $c) }}" class="btn-3d btn-3d-primary btn-3d-sm" title="Print Statement">Statement</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ================= Customers Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Code / Name</th>
                        <th>Phone &amp; CNIC</th>
                        <th>Vehicles</th>
                        <th>Credit Limit</th>
                        <th>Outstanding Udhaar</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900 dark:text-white">{{ $c->name }}</div>
                                <div class="font-mono text-xs font-semibold text-vital-primary dark:text-red-400">{{ $c->code }}</div>
                            </td>
                            <td>
                                <div class="font-mono text-xs text-slate-900 dark:text-white">{{ $c->phone ?? 'N/A' }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $c->cnic ?? 'No CNIC' }}</div>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                    🚗 {{ $c->vehicles_count }}
                                </span>
                            </td>
                            <td class="tabular font-medium">
                                @if($c->creditLimitIsUnlimited())
                                    <span class="text-slate-400">Unlimited</span>
                                @else
                                    {{ \App\Support\PakistaniCurrency::format($c->credit_limit, true, 0) }}
                                @endif
                            </td>
                            <td>
                                <div class="tabular font-bold {{ \App\Support\Money::compare($c->current_balance, '0.00') > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ \App\Support\PakistaniCurrency::format($c->current_balance) }}
                                </div>
                                @if(! $c->creditLimitIsUnlimited())
                                    @php
                                        $limit = (float) $c->credit_limit;
                                        $bal = (float) $c->current_balance;
                                        $pct = $limit > 0 ? min(100, round(($bal / $limit) * 100)) : 0;
                                    @endphp
                                    <div class="mt-1 flex items-center justify-center gap-1.5 text-[11px] text-slate-400">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                            <div class="h-full {{ $pct > 90 ? 'bg-red-600' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span>{{ $pct }}%</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="pill-status {{ $c->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                                    <span class="dot" aria-hidden="true"></span>{{ $c->status }}
                                </span>
                            </td>
                            <td>
                                <div class="inline-flex items-center justify-center gap-2">
                                    <a href="{{ route('customers.show', $c) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        Ledger &amp; Profile
                                    </a>
                                    <a href="{{ route('customers.statement', $c) }}" class="btn-3d btn-3d-primary btn-3d-sm" title="Print Statement">
                                        Statement
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10">
                                <div class="text-4xl" aria-hidden="true">🧑</div>
                                <p class="mt-3 font-semibold text-slate-500">No customer records found matching the criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="border-t border-slate-200/70 px-5 py-3 dark:border-slate-700/60">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ================= Floating Action Button ================= --}}
@if(auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_CREATE))
    <a href="{{ route('customers.create') }}" class="fab-3d" title="Register a new customer">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> New Customer
    </a>
@endif
@endsection
