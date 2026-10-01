@extends('layouts.app')

@section('title', 'Expenses & Vouchers / اخراجات')
@section('breadcrumb')
    <li class="text-slate-500">Expenses</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>🧾 Operating Expenses &amp; Petty Cash / اخراجات</h1>
        <p>Track generator fuel, electricity, staff meals, maintenance &amp; daily vouchers tied to shifts or bank accounts.</p>
        <div class="page-actions">
            <a href="{{ route('expenses.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Expense Voucher (نیا خرچ)
            </a>
        </div>
    </div>

    {{-- ================= Stats Tiles ================= --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Total Filtered Expenses</div>
            <div class="stat-value tabular font-mono">
                Rs. {{ number_format((float) $totalAmount, 2) }}
            </div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalAmount) }}</div>
        </div>
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Paid from Shift Cash Till</div>
            <div class="stat-value tabular font-mono">
                Rs. {{ number_format((float) $cashAmount, 2) }}
            </div>
            <div class="stat-sub">Petty cash drawn from drawer</div>
        </div>
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Paid from Station Bank</div>
            <div class="stat-value tabular font-mono">
                Rs. {{ number_format((float) $bankAmount, 2) }}
            </div>
            <div class="stat-sub">LESCO, generator fuel, indents</div>
        </div>
    </div>

    {{-- ================= Filter Bar ================= --}}
    <form method="GET" action="{{ route('expenses.index') }}" class="glass-card p-4">
        <div class="grid gap-3 sm:grid-cols-5">
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Category</label>
                <select name="category_id" class="input-3d text-center text-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                            {{ $cat->name }} ({{ $cat->urdu_name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Payment Method</label>
                <select name="payment_method" class="input-3d text-center text-sm">
                    <option value="">All Methods</option>
                    <option value="CASH" @selected(request('payment_method') === 'CASH')>Cash (Shift Till)</option>
                    <option value="BANK_TRANSFER" @selected(request('payment_method') === 'BANK_TRANSFER')>Bank Transfer</option>
                    <option value="CHEQUE" @selected(request('payment_method') === 'CHEQUE')>Cheque</option>
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Search Payee / Title / Bill #</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. Generator, LESCO, Ali" class="input-3d text-center text-sm">
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input-3d text-center text-sm">
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">Filter</button>
                <a href="{{ route('expenses.index') }}" class="btn-3d btn-3d-ghost">Reset</a>
            </div>
        </div>
    </form>

    {{-- ================= Expense Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Voucher #</th>
                        <th>Category</th>
                        <th>Title / Description</th>
                        <th>Amount (رقم)</th>
                        <th>Method / Source</th>
                        <th>Payee</th>
                        <th>Voucher Receipt</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $exp)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                {{ $exp->date?->format('d M Y') }}
                            </td>
                            <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">
                                {{ $exp->expense_number }}
                            </td>
                            <td class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                {{ $exp->category?->name }}
                                @if ($exp->category?->urdu_name)
                                    <span class="font-normal text-slate-400">({{ $exp->category->urdu_name }})</span>
                                @endif
                            </td>
                            <td class="font-medium text-slate-900 dark:text-white">
                                {{ $exp->title }}
                                @if ($exp->receipt_number)
                                    <span class="font-mono text-xs text-slate-400">#{{ $exp->receipt_number }}</span>
                                @endif
                            </td>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $exp->amount, 2) }}
                            </td>
                            <td class="text-xs">
                                @if ($exp->payment_method === 'CASH')
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        CASH {{ $exp->shift ? '(' . $exp->shift->shift_number . ')' : '' }}
                                    </span>
                                @elseif ($exp->payment_method === 'BANK_TRANSFER')
                                    <span class="rounded bg-sky-100 px-2 py-0.5 font-semibold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                                        {{ $exp->bankAccount?->bank?->short_name ?? 'BANK' }}
                                    </span>
                                @else
                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $exp->payment_method }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-600 dark:text-slate-400">
                                {{ $exp->payee ?: '—' }}
                            </td>
                            <td>
                                @if ($exp->attachment_path)
                                    <a href="{{ asset('storage/' . $exp->attachment_path) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm text-red-700 dark:text-red-400">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        Receipt
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <a href="{{ route('expenses.show', $exp) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                    Voucher &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-slate-500">
                                <div class="text-4xl" aria-hidden="true">🧾</div>
                                <p class="mt-3 font-semibold">No expenses found for the selected filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 px-4 py-3 dark:border-slate-700/60">{{ $expenses->links() }}</div>
    </div>
</div>

{{-- ================= Floating Action Button ================= --}}
<a href="{{ route('expenses.create') }}" class="fab-3d" title="Record a new expense voucher">
    <span class="text-xl leading-none" aria-hidden="true">＋</span> New Expense
</a>
@endsection
