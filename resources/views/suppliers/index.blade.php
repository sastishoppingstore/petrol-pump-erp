@extends('layouts.app')

@section('title', __('sales.suppliers.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.suppliers.breadcrumb') }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>🏭 {{ __('sales.suppliers.heading') }}</h1>
        <p>{{ __('sales.suppliers.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('purchases.create') }}" class="btn-3d btn-3d-amber">
                <span aria-hidden="true">📥</span> {{ __('sales.suppliers.new_decantation') }}
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::SUPPLIER_CREATE))
                <a href="{{ route('suppliers.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('sales.suppliers.new_supplier') }}
                </a>
            @endif
        </div>
    </div>

    {{-- ================= Stats Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.suppliers.total_payable') }}</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($totalPayable) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($totalPayable) }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('sales.suppliers.active_partners') }}</div>
            <div class="stat-value kpi-num">{{ number_format($activeSuppliersCount) }}</div>
            <div class="stat-sub">{{ __('sales.suppliers.active_sub') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('sales.suppliers.primary_partner') }}</div>
            <div class="stat-value text-xl">Vital Petroleum (Pvt) Ltd</div>
            <div class="stat-sub">{{ __('sales.suppliers.primary_sub') }}</div>
        </div>
    </div>

    {{-- ================= 3D Supplier Cards ================= --}}
    @if($suppliers->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach($suppliers as $s)
                <article class="glass-card card-3d relative overflow-hidden p-5 text-center">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-vital-primary to-vital-darkred" aria-hidden="true"></div>

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-vital-primary/10 text-2xl shadow-inner" aria-hidden="true">🏭</div>
                    <h2 class="mt-3 truncate text-base font-black text-slate-900 dark:text-white">{{ $s->name }}</h2>
                    <span class="mt-1 inline-block rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono text-[11px] font-bold text-vital-primary dark:bg-white/10 dark:text-red-300">{{ $s->code }}</span>

                    <div class="mt-3 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                        <div>👤 {{ $s->contact_person ?? 'Direct' }}</div>
                        <div class="font-mono">📱 {{ $s->phone ?? 'N/A' }}</div>
                        <div class="font-mono">NTN: {{ $s->ntn_number ?? 'N/A' }}</div>
                        @if($s->strn_number)
                            <div class="font-mono">STRN: {{ $s->strn_number }}</div>
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('sales.suppliers.decantations') }}</dt>
                            <dd class="tabular mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">{{ $s->purchases_count }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('sales.suppliers.payable') }}</dt>
                            <dd class="tabular mt-0.5 text-sm font-black text-slate-800 dark:text-slate-100">{{ \App\Support\PakistaniCurrency::format($s->current_balance) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3">
                        <span class="pill-status {{ $s->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                            <span class="dot" aria-hidden="true"></span>{{ $s->status }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('suppliers.show', $s) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('sales.suppliers.ledger_payments') }}</a>
                        <a href="{{ route('suppliers.statement', $s) }}" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('sales.suppliers.statement') }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ================= Suppliers Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('sales.suppliers.th_code_name') }}</th>
                        <th>{{ __('sales.suppliers.th_contact') }}</th>
                        <th>{{ __('sales.suppliers.th_ntn_strn') }}</th>
                        <th>{{ __('sales.suppliers.th_decantations') }}</th>
                        <th>{{ __('sales.suppliers.th_payable') }}</th>
                        <th>{{ __('sales.suppliers.th_status') }}</th>
                        <th>{{ __('sales.suppliers.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $s)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900 dark:text-white">{{ $s->name }}</div>
                                <div class="font-mono text-xs font-semibold text-vital-primary dark:text-red-400">{{ $s->code }}</div>
                            </td>
                            <td>
                                <div class="text-xs font-medium text-slate-900 dark:text-white">{{ $s->contact_person ?? 'Direct' }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $s->phone ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="font-mono text-xs text-slate-700 dark:text-slate-300">NTN: {{ $s->ntn_number ?? 'N/A' }}</div>
                                @if($s->strn_number)
                                    <div class="font-mono text-[11px] text-slate-400">STRN: {{ $s->strn_number }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $s->purchases_count }}
                                </span>
                            </td>
                            <td class="tabular font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($s->current_balance) }}
                            </td>
                            <td>
                                <span class="pill-status {{ $s->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                                    <span class="dot" aria-hidden="true"></span>{{ $s->status }}
                                </span>
                            </td>
                            <td>
                                <div class="inline-flex items-center justify-center gap-2">
                                    <a href="{{ route('suppliers.show', $s) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        {{ __('sales.suppliers.ledger_payments') }}
                                    </a>
                                    <a href="{{ route('suppliers.statement', $s) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                                        {{ __('sales.suppliers.statement') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10">
                                <div class="text-4xl" aria-hidden="true">🏭</div>
                                <p class="mt-3 font-semibold text-slate-500">{{ __('sales.suppliers.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="border-t border-slate-200/70 px-5 py-3 dark:border-slate-700/60">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ================= Floating Action Button ================= --}}
@if(auth()->user()->hasPermission(\App\Support\PermissionList::SUPPLIER_CREATE))
    <a href="{{ route('suppliers.create') }}" class="fab-3d" title="{{ __('sales.suppliers.register_title') }}">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('sales.suppliers.new_supplier') }}
    </a>
@endif
@endsection
