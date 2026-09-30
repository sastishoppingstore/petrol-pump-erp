@extends('layouts.app')

@section('title', 'Reports / رپورٹس')

@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold">Reports & Analytics</li>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Report Category Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- SALES REPORTS --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-vital-primary/30 dark:border-vital-primary/50 p-5 hover:shadow-lg transition">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">🧾</span>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Sales Reports</h3>
            </div>
            <div class="space-y-2">
                <a href="{{ route('reports.sales.daily') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📅 Daily Sales
                </a>
                <a href="{{ route('reports.sales.monthly') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📊 Monthly Sales
                </a>
                <a href="{{ route('reports.sales.by-nozzle') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    ⛽ By Nozzle
                </a>
                <a href="{{ route('reports.sales.by-cashier') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-vital-primary/10 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    👤 By Cashier
                </a>
            </div>
        </div>

        {{-- FUEL REPORTS --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-amber-400/30 dark:border-amber-500/50 p-5 hover:shadow-lg transition">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">⛽</span>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Fuel & Stock</h3>
            </div>
            <div class="space-y-2">
                <a href="{{ route('reports.stock.summary') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-amber-100/20 dark:hover:bg-amber-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📦 Stock Summary
                </a>
                <a href="{{ route('reports.stock.variance') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-amber-100/20 dark:hover:bg-amber-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    ⚠️ Stock Variance
                </a>
                <a href="{{ route('reports.stock.movements') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-amber-100/20 dark:hover:bg-amber-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📈 Stock Movements
                </a>
                <a href="{{ route('reports.fuel.prices') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-amber-100/20 dark:hover:bg-amber-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    💰 Fuel Prices
                </a>
            </div>
        </div>

        {{-- FINANCIAL REPORTS --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-green-400/30 dark:border-green-500/50 p-5 hover:shadow-lg transition">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">📈</span>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Financial</h3>
            </div>
            <div class="space-y-2">
                <a href="{{ route('reports.profitloss.daily') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-green-100/20 dark:hover:bg-green-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    💹 Daily P&L
                </a>
                <a href="{{ route('reports.profitloss.monthly') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-green-100/20 dark:hover:bg-green-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📊 Monthly P&L
                </a>
                <a href="{{ route('reports.expenses') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-green-100/20 dark:hover:bg-green-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    💸 Expenses
                </a>
                <a href="{{ route('reports.cashflow') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-green-100/20 dark:hover:bg-green-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    💳 Cash Flow
                </a>
            </div>
        </div>

        {{-- CUSTOMER REPORTS --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-blue-400/30 dark:border-blue-500/50 p-5 hover:shadow-lg transition">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">👥</span>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Customers</h3>
            </div>
            <div class="space-y-2">
                <a href="{{ route('reports.customers.outstanding') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-blue-100/20 dark:hover:bg-blue-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📋 Outstanding Credit
                </a>
                <a href="{{ route('reports.customers.ageing') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-blue-100/20 dark:hover:bg-blue-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    ⏰ Ageing Analysis
                </a>
                <a href="{{ route('reports.customers.activity') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-blue-100/20 dark:hover:bg-blue-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📊 Activity
                </a>
                <a href="{{ route('reports.customers.statements') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-blue-100/20 dark:hover:bg-blue-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    🧾 Statements
                </a>
            </div>
        </div>

        {{-- SUPPLIER REPORTS --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-indigo-400/30 dark:border-indigo-500/50 p-5 hover:shadow-lg transition">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">🏭</span>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Suppliers</h3>
            </div>
            <div class="space-y-2">
                <a href="{{ route('reports.suppliers.payable') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100/20 dark:hover:bg-indigo-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    💳 Payables
                </a>
                <a href="{{ route('reports.suppliers.purchases') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100/20 dark:hover:bg-indigo-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📦 Purchases
                </a>
                <a href="{{ route('reports.suppliers.activity') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100/20 dark:hover:bg-indigo-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📊 Activity
                </a>
                <a href="{{ route('reports.suppliers.statements') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100/20 dark:hover:bg-indigo-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    🧾 Statements
                </a>
            </div>
        </div>

        {{-- ACCOUNTING REPORTS --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-purple-400/30 dark:border-purple-500/50 p-5 hover:shadow-lg transition">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">📚</span>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Accounting</h3>
            </div>
            <div class="space-y-2">
                <a href="{{ route('reports.accounting.trial-balance') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-purple-100/20 dark:hover:bg-purple-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    ⚖️ Trial Balance
                </a>
                <a href="{{ route('reports.accounting.general-ledger') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-purple-100/20 dark:hover:bg-purple-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📋 General Ledger
                </a>
                <a href="{{ route('reports.accounting.balance-sheet') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-purple-100/20 dark:hover:bg-purple-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📊 Balance Sheet
                </a>
                <a href="{{ route('reports.accounting.daybook') }}" class="block px-3 py-2 rounded bg-slate-100 dark:bg-slate-800 hover:bg-purple-100/20 dark:hover:bg-purple-900/20 text-sm font-semibold text-slate-700 dark:text-slate-300 transition">
                    📕 Day Book
                </a>
            </div>
        </div>

    </div>

    {{-- Export Reminders --}}
    <div class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-2">💡 Export Options</h4>
        <p class="text-sm text-blue-800 dark:text-blue-200">
            All reports can be exported to <strong>PDF</strong>, <strong>Excel</strong>, or <strong>CSV</strong> format. 
            Use the export buttons on each report screen. Large exports are processed in the background and emailed to you.
        </p>
    </div>

</div>
@endsection
