@extends('layouts.app')

@section('title', __('sales.collection.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.customers.breadcrumb') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.collection.heading') }}</span></li>
@endsection

@section('content')
<div class="space-y-6" x-data="collectionPage()">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>💰 {{ __('sales.collection.heading') }}</h1>
        <p>{{ __('sales.collection.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('customers.ageing') }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">⏱️</span> {{ __('sales.customers.ageing_report') }}
            </a>
            <a href="{{ route('customers.index') }}" class="btn-3d btn-3d-ghost">
                🧑 {{ __('sales.customers.breadcrumb') }}
            </a>
        </div>
    </div>

    {{-- ================= Stats Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.collection.total_receivable') }}</div>
            <div class="stat-value kpi-num"><span data-countup="{{ $totals['total'] }}" data-prefix="₨ " data-decimals="0">₨ {{ number_format((float) $totals['total']) }}</span></div>
            <div class="stat-sub">{{ __('sales.collection.total_receivable_sub') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('sales.collection.overdue_customers') }}</div>
            <div class="stat-value kpi-num"><span data-countup="{{ $overdueCount }}">{{ number_format($overdueCount) }}</span></div>
            <div class="stat-sub">{{ __('sales.collection.overdue_sub') }}</div>
        </div>
    </div>

    {{-- ================= Due List ================= --}}
    <form method="POST" action="{{ route('customers.collection.sms') }}" id="bulk-sms-form">
        @csrf
        <div class="glass-card overflow-hidden">
            {{-- Bulk action bar --}}
            <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-200/70 p-4 text-center dark:border-slate-700/60 md:flex-row md:text-left">
                <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <input type="checkbox" id="select-all" class="h-4 w-4 rounded" @change="toggleAll($event)">
                    {{ __('sales.collection.select_all') }}
                    <span class="rounded-full bg-vital-primary/10 px-2 py-0.5 text-[11px] font-black text-vital-primary dark:text-red-300">
                        <span x-text="selectedCount">0</span> {{ __('sales.collection.selected') }}
                    </span>
                </label>
                <div class="flex flex-wrap justify-center gap-2">
                    <button type="button" class="btn-3d btn-3d-success btn-3d-sm" @click="openWhatsapp()">
                        💬 {{ __('sales.collection.bulk_whatsapp_btn') }}
                    </button>
                    <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">
                        📩 {{ __('sales.collection.bulk_sms_btn') }}
                    </button>
                </div>
            </div>

            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>{{ __('sales.collection.th_customer') }}</th>
                            <th>{{ __('sales.collection.th_phone') }}</th>
                            <th>{{ __('sales.collection.th_balance') }}</th>
                            <th>{{ __('sales.collection.th_ageing') }}</th>
                            <th>{{ __('sales.collection.th_last_activity') }}</th>
                            <th>{{ __('sales.collection.th_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $c = $row['customer'];
                                $age = $row['ageing'];
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="customer_ids[]" value="{{ $c->id }}"
                                           class="due-check h-4 w-4 rounded"
                                           data-name="{{ $c->name }}"
                                           data-wa="{{ $row['whatsapp_link'] }}"
                                           @change="refreshCount()">
                                </td>
                                <td>
                                    <a href="{{ route('customers.show', $c) }}" class="font-bold text-slate-900 hover:text-vital-primary dark:text-white">{{ $c->name }}</a>
                                    <div class="font-mono text-xs font-semibold text-vital-primary dark:text-red-400">{{ $c->code }}</div>
                                </td>
                                <td class="font-mono text-xs">{{ $c->phone ?? '—' }}</td>
                                <td>
                                    <span class="tabular text-lg font-black text-red-600 dark:text-red-400">
                                        {{ \App\Support\PakistaniCurrency::format($age['total']) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap justify-center gap-1 text-[10px] font-bold">
                                        @if(\App\Support\Money::compare($age['0_30'], '0.00') > 0)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">{{ __('sales.collection.age_current') }}: {{ \App\Support\PakistaniCurrency::format($age['0_30'], true, 0) }}</span>
                                        @endif
                                        @if(\App\Support\Money::compare($age['31_60'], '0.00') > 0)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">{{ __('sales.collection.age_30') }}: {{ \App\Support\PakistaniCurrency::format($age['31_60'], true, 0) }}</span>
                                        @endif
                                        @if(\App\Support\Money::compare($age['61_90'], '0.00') > 0)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">{{ __('sales.collection.age_60') }}: {{ \App\Support\PakistaniCurrency::format($age['61_90'], true, 0) }}</span>
                                        @endif
                                        @if(\App\Support\Money::compare($age['over_90'], '0.00') > 0)
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-red-800 dark:bg-red-950/50 dark:text-red-300">{{ __('sales.collection.age_90') }}: {{ \App\Support\PakistaniCurrency::format($age['over_90'], true, 0) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="tabular font-mono text-xs">
                                    {{ $row['last_activity'] ? \Carbon\Carbon::parse($row['last_activity'])->format('d/m/Y') : '—' }}
                                </td>
                                <td>
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        @if($c->phone)
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->phone) }}" class="btn-3d btn-3d-ghost btn-3d-sm" title="{{ __('sales.collection.call') }}">📞</a>
                                        @endif
                                        <a href="{{ $row['whatsapp_link'] }}" target="_blank" rel="noopener" class="btn-3d btn-3d-success btn-3d-sm" title="{{ __('sales.collection.whatsapp') }}">💬</a>
                                        <button type="button" class="btn-3d btn-3d-navy btn-3d-sm" title="{{ __('sales.collection.sms') }}"
                                                @click="smsOnly({{ $c->id }})">📩</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10">
                                    <div class="text-4xl" aria-hidden="true">🎉</div>
                                    <p class="mt-3 font-semibold text-slate-500">{{ __('sales.collection.no_due') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- ================= WhatsApp Links Modal ================= --}}
    <div x-show="waOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" @click.self="waOpen = false">
        <div class="glass-card w-full max-w-lg p-6">
            <h2 class="text-center text-base font-black text-slate-900 dark:text-white">💬 {{ __('sales.collection.wa_modal_title') }}</h2>
            <p class="mt-1 text-center text-xs text-slate-500">{{ __('sales.collection.wa_modal_note') }}</p>
            <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">
                <template x-for="link in waLinks" :key="link.name">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-900/[0.03] px-3 py-2 dark:bg-white/5">
                        <span class="truncate text-sm font-bold text-slate-800 dark:text-slate-100" x-text="link.name"></span>
                        <a :href="link.url" target="_blank" rel="noopener" class="btn-3d btn-3d-success btn-3d-sm shrink-0">{{ __('sales.collection.wa_open') }} →</a>
                    </div>
                </template>
                <p x-show="waLinks.length === 0" class="py-4 text-center text-xs text-slate-400">{{ __('sales.collection.selected') }}: 0</p>
            </div>
            <div class="mt-5 text-center">
                <button type="button" class="btn-3d btn-3d-ghost" @click="waOpen = false">{{ __('sales.collection.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function collectionPage() {
    return {
        selectedCount: 0,
        waOpen: false,
        waLinks: [],
        boxes() { return Array.from(document.querySelectorAll('.due-check')); },
        refreshCount() { this.selectedCount = this.boxes().filter(b => b.checked).length; },
        toggleAll(event) {
            this.boxes().forEach(b => { b.checked = event.target.checked; });
            this.refreshCount();
        },
        smsOnly(id) {
            this.boxes().forEach(b => { b.checked = parseInt(b.value, 10) === id; });
            this.refreshCount();
            document.getElementById('bulk-sms-form').submit();
        },
        openWhatsapp() {
            this.waLinks = this.boxes()
                .filter(b => b.checked)
                .map(b => ({ name: b.dataset.name, url: b.dataset.wa }));
            this.waOpen = true;
        },
    };
}
</script>
@endpush
@endsection
