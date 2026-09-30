@extends('layouts.app')

@section('title', 'Expenses & Vouchers / اخراجات')
@section('breadcrumb')
    <li class="text-slate-500">Expenses</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Operating Expenses &amp; Petty Cash / اخراجات</h1>
            <p class="mt-1 text-sm text-slate-500">
                Track generator fuel, electricity, staff meals, maintenance &amp; daily vouchers tied to shifts or bank accounts.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Expense Voucher (نیا خرچ)
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Total Filtered Expenses</span>
            <div class="mt-2 font-mono text-2xl font-bold text-slate-900 dark:text-white">
                Rs. {{ number_format((float) $totalAmount, 2) }}
            </div>
            <div class="mt-1 text-xs text-red-600 font-medium">
                {{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalAmount) }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-emerald-600">Paid from Shift Cash Till</span>
            <div class="mt-2 font-mono text-2xl font-bold text-emerald-600">
                Rs. {{ number_format((float) $cashAmount, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-400">Petty cash drawn from drawer</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-sky-600">Paid from Station Bank</span>
            <div class="mt-2 font-mono text-2xl font-bold text-sky-600">
                Rs. {{ number_format((float) $bankAmount, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-400">LESCO, generator fuel, indents</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('expenses.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 sm:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Category</label>
                <select name="category_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                            {{ $cat->name }} ({{ $cat->urdu_name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Payment Method</label>
                <select name="payment_method" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All Methods</option>
                    <option value="CASH" @selected(request('payment_method') === 'CASH')>Cash (Shift Till)</option>
                    <option value="BANK_TRANSFER" @selected(request('payment_method') === 'BANK_TRANSFER')>Bank Transfer</option>
                    <option value="CHEQUE" @selected(request('payment_method') === 'CHEQUE')>Cheque</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Search Payee / Title / Bill #</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. Generator, LESCO, Ali" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900">Filter</button>
                <a href="{{ route('expenses.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Reset</a>
            </div>
        </div>
    </form>

    {{-- Expense Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Voucher #</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Title / Description</th>
                        <th class="px-4 py-3 text-right">Amount (رقم)</th>
                        <th class="px-4 py-3">Method / Source</th>
                        <th class="px-4 py-3">Payee</th>
                        <th class="px-4 py-3 text-center">Voucher Receipt</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($expenses as $exp)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                {{ $exp->date?->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900 dark:text-white">
                                {{ $exp->expense_number }}
                            </td>
                            <td class="px-4 py-3 text-xs font-medium text-slate-700 dark:text-slate-300">
                                {{ $exp->category?->name }}
                                @if ($exp->category?->urdu_name)
                                    <span class="text-slate-400 font-normal">({{ $exp->category->urdu_name }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">
                                {{ $exp->title }}
                                @if ($exp->receipt_number)
                                    <span class="font-mono text-xs text-slate-400">#{{ $exp->receipt_number }}</span>
                                @endif
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $exp->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($exp->payment_method === 'CASH')
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 font-semibold">
                                        CASH {{ $exp->shift ? '(' . $exp->shift->shift_number . ')' : '' }}
                                    </span>
                                @elseif ($exp->payment_method === 'BANK_TRANSFER')
                                    <span class="rounded bg-sky-100 px-2 py-0.5 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300 font-semibold">
                                        {{ $exp->bankAccount?->bank?->short_name ?? 'BANK' }}
                                    </span>
                                @else
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-semibold">
                                        {{ $exp->payment_method }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                {{ $exp->payee ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($exp->attachment_path)
                                    <a href="{{ asset('storage/' . $exp->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50 dark:bg-slate-800 dark:text-red-400">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        Receipt
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <a href="{{ route('expenses.show', $exp) }}" class="rounded bg-slate-100 px-2.5 py-1 font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                    Voucher &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                No expenses found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $expenses->links() }}</div>
    </div>
</div>
@endsection
