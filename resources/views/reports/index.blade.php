@extends('layouts.app')

@section('title', 'Reports / رپورٹس')

@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold">Reports & Analytics</li>
@endsection

@section('content')
<div class="space-y-6">

    <div class="page-head">
        <h1>📊 Reports & Analytics</h1>
        <p>Sales, Stock, Financial, Customer, Supplier aur Accounting reports — sab ek jagah (تمام رپورٹس)</p>
    </div>

    {{-- Report Category Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

        {{-- SALES REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred text-3xl shadow-3d">🧾</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">Sales Reports</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.sales.daily') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📅 Daily Sales
                </a>
                <a href="{{ route('reports.sales.monthly') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📊 Monthly Sales
                </a>
                <a href="{{ route('reports.sales.by-nozzle') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    ⛽ By Nozzle
                </a>
                <a href="{{ route('reports.sales.by-cashier') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    👤 By Cashier
                </a>
            </div>
        </div>

        {{-- FUEL REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-3xl shadow-3d">⛽</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">Fuel & Stock</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.stock.summary') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📦 Stock Summary
                </a>
                <a href="{{ route('reports.stock.variance') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    ⚠️ Stock Variance
                </a>
                <a href="{{ route('reports.stock.movements') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📈 Stock Movements
                </a>
                <a href="{{ route('reports.fuel.prices') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-amber-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    💰 Fuel Prices
                </a>
            </div>
        </div>

        {{-- FINANCIAL REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-3xl shadow-3d">📈</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">Financial</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.profitloss.daily') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    💹 Daily P&L
                </a>
                <a href="{{ route('reports.profitloss.monthly') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📊 Monthly P&L
                </a>
                <a href="{{ route('reports.expenses') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    💸 Expenses
                </a>
                <a href="{{ route('reports.cashflow') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-emerald-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    💳 Cash Flow
                </a>
            </div>
        </div>

        {{-- CUSTOMER REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 text-3xl shadow-3d">👥</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">Customers</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.customers.outstanding') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📋 Outstanding Credit
                </a>
                <a href="{{ route('reports.customers.ageing') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    ⏰ Ageing Analysis
                </a>
                <a href="{{ route('reports.customers.activity') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📊 Activity
                </a>
                <a href="{{ route('reports.customers.statements') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-blue-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    🧾 Statements
                </a>
            </div>
        </div>

        {{-- SUPPLIER REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 text-3xl shadow-3d">🏭</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">Suppliers</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.suppliers.payable') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    💳 Payables
                </a>
                <a href="{{ route('reports.suppliers.purchases') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📦 Purchases
                </a>
                <a href="{{ route('reports.suppliers.activity') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📊 Activity
                </a>
                <a href="{{ route('reports.suppliers.statements') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-indigo-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    🧾 Statements
                </a>
            </div>
        </div>

        {{-- ACCOUNTING REPORTS --}}
        <div class="glass-card card-3d p-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 text-3xl shadow-3d">📚</div>
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-4">Accounting</h3>
            <div class="space-y-2">
                <a href="{{ route('reports.accounting.trial-balance') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    ⚖️ Trial Balance
                </a>
                <a href="{{ route('reports.accounting.general-ledger') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📋 General Ledger
                </a>
                <a href="{{ route('reports.accounting.balance-sheet') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📊 Balance Sheet
                </a>
                <a href="{{ route('reports.accounting.daybook') }}" class="block px-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 hover:bg-purple-500/10 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:-translate-y-0.5">
                    📕 Day Book
                </a>
            </div>
        </div>

    </div>

    {{-- Export Reminders --}}
    <div class="glass-card card-3d p-6 text-center">
        <h4 class="font-extrabold text-slate-800 dark:text-white mb-2">💡 Export Options</h4>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            All reports can be exported to <strong>PDF</strong>, <strong>Excel</strong>, or <strong>CSV</strong> format.
            Use the export buttons on each report screen. Large exports are processed in the background and emailed to you.
        </p>
    </div>

</div>
@endsection
