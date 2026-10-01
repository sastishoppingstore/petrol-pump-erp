@extends('layouts.app')

@section('title', __('finance.reports.title'))

@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold">{{ __('finance.reports.crumb_hub') }}</li>
@endsection

@section('content')
<div class="space-y-6">

    <div class="page-head">
        <h1>{{ __('finance.reports.heading') }}</h1>
        <p>{{ __('finance.reports.subheading') }}</p>
    </div>

    {{-- Report Category Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

        {{-- SALES REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred text-3xl shadow-3d">🧾</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ __('finance.reports.sales_reports') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.sales.daily') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.daily_sales') }}
                </a>
                <a href="{{ route('reports.sales.monthly') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.monthly_sales') }}
                </a>
                <a href="{{ route('reports.sales.by-nozzle') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.by_nozzle') }}
                </a>
                <a href="{{ route('reports.sales.by-cashier') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.by_cashier') }}
                </a>
            </div>
        </div>

        {{-- FUEL REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-3xl shadow-3d">⛽</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ __('finance.reports.fuel_stock') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.stock.summary') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.stock_summary') }}
                </a>
                <a href="{{ route('reports.stock.variance') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.stock_variance') }}
                </a>
                <a href="{{ route('reports.stock.movements') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.stock_movements') }}
                </a>
                <a href="{{ route('reports.fuel.prices') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.fuel_prices') }}
                </a>
            </div>
        </div>

        {{-- FINANCIAL REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-3xl shadow-3d">📈</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ __('finance.reports.financial') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.profitloss.daily') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.daily_pl') }}
                </a>
                <a href="{{ route('reports.profitloss.monthly') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.monthly_pl') }}
                </a>
                <a href="{{ route('reports.expenses') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.expenses_link') }}
                </a>
                <a href="{{ route('reports.cashflow') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.cash_flow') }}
                </a>
            </div>
        </div>

        {{-- CUSTOMER REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 text-3xl shadow-3d">👥</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ __('finance.reports.customers') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.customers.outstanding') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.outstanding_credit') }}
                </a>
                <a href="{{ route('reports.customers.ageing') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.ageing_analysis') }}
                </a>
                <a href="{{ route('reports.customers.activity') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.activity') }}
                </a>
                <a href="{{ route('reports.customers.statements') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.statements') }}
                </a>
            </div>
        </div>

        {{-- SUPPLIER REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 text-3xl shadow-3d">🏭</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ __('finance.reports.suppliers') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.suppliers.payable') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.payables') }}
                </a>
                <a href="{{ route('reports.suppliers.purchases') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.purchases') }}
                </a>
                <a href="{{ route('reports.suppliers.activity') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.activity') }}
                </a>
                <a href="{{ route('reports.suppliers.statements') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.statements') }}
                </a>
            </div>
        </div>

        {{-- ACCOUNTING REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 text-3xl shadow-3d">📚</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">{{ __('finance.reports.accounting') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.accounting.trial-balance') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.trial_balance_link') }}
                </a>
                <a href="{{ route('reports.accounting.general-ledger') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.general_ledger_link') }}
                </a>
                <a href="{{ route('reports.accounting.balance-sheet') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.balance_sheet') }}
                </a>
                <a href="{{ route('reports.accounting.daybook') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    {{ __('finance.reports.day_book') }}
                </a>
            </div>
        </div>

    </div>

    {{-- Export Reminders --}}
    <div class="glass-card card-3d p-6 text-center">
        <h4 class="font-extrabold text-slate-800 dark:text-white mb-2">{{ __('finance.reports.export_heading') }}</h4>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ __('finance.reports.export_text_a') }} <strong>PDF</strong>, <strong>Excel</strong>, or <strong>CSV</strong> {{ __('finance.reports.export_text_b') }}
        </p>
    </div>

</div>
@endsection
