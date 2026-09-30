@extends('layouts.app')

@section('title', 'Bank Book — ' . $account->account_title)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Bank Book</li>
@endsection

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Bank Book / لیجر</h1>
            <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300">
                {{ $account->bank?->short_name ?? 'BANK' }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ $account->bank?->name }} &bull; {{ $account->account_title }} &bull;
            <span class="font-mono text-xs">{{ $account->account_number }}</span>
            @if($account->iban)
                &bull; <span class="font-mono text-xs">IBAN: {{ $account->iban }}</span>
            @endif
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('bank-deposits.index', ['bank_account_id' => $account->id]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Deposits
        </a>
        <a href="{{ route('banks.reconciliation', $account) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Reconcile
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg bg-navy-800 px-3.5 py-2 text-sm font-medium text-white hover:bg-navy-900 dark:bg-navy-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Bank Book
        </button>
    </div>
</div>

{{-- Filter Card --}}
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <form method="GET" action="{{ route('banks.book', $account) }}" class="grid gap-4 sm:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Switch Account</label>
            <select onchange="window.location.href=this.value" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                @foreach ($allAccounts as $acc)
                    <option value="{{ route('banks.book', $acc) }}" @selected($acc->id === $account->id)>
                        {{ $acc->bank?->short_name }} — {{ $acc->account_title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="mb-1 block text-xs font-semibold uppercase text-slate-500">From Date</label>
            <input type="date" id="from" name="from" value="{{ $start_date }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
        </div>
        <div>
            <label for="to" class="mb-1 block text-xs font-semibold uppercase text-slate-500">To Date</label>
            <input type="date" id="to" name="to" value="{{ $end_date }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Filter Ledger</button>
            <a href="{{ route('banks.book', $account) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Reset</a>
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="mb-6 grid gap-4 sm:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <span class="text-xs font-medium text-slate-500">Opening Balance (ابتداء)</span>
        <div class="mt-1 font-mono text-xl font-bold text-slate-800 dark:text-slate-100">
            Rs. {{ number_format((float) $opening_balance, 2) }}
        </div>
        <div class="mt-0.5 text-xs text-slate-400">As of {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }}</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <span class="text-xs font-medium text-emerald-600">Total Deposits / Credits (جمع)</span>
        <div class="mt-1 font-mono text-xl font-bold text-emerald-600">
            + Rs. {{ number_format((float) $total_credits, 2) }}
        </div>
        <div class="mt-0.5 text-xs text-slate-400">Inward cash, transfers & cheques</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <span class="text-xs font-medium text-red-600">Total Withdrawals / Debits (بنام)</span>
        <div class="mt-1 font-mono text-xl font-bold text-red-600">
            - Rs. {{ number_format((float) $total_debits, 2) }}
        </div>
        <div class="mt-0.5 text-xs text-slate-400">Outward cash, charges & payments</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-900 p-4 text-white shadow-sm dark:border-slate-700">
        <span class="text-xs font-medium text-slate-300">Closing Balance (بقایا بیلنس)</span>
        <div class="mt-1 font-mono text-xl font-bold text-white">
            Rs. {{ number_format((float) $closing_balance, 2) }}
        </div>
        <div class="mt-0.5 text-xs text-slate-400">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $closing_balance) }}</div>
    </div>
</div>

{{-- Transactions Ledger Table --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Transaction Statement</h2>
        <span class="text-xs text-slate-500">{{ count($items) }} entries</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">Date / Time</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Reference #</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3 text-right text-emerald-600">Credit (جمع)</th>
                    <th class="px-4 py-3 text-right text-red-600">Debit (بنام)</th>
                    <th class="px-4 py-3 text-right font-bold">Balance</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr class="bg-slate-50/50 font-medium italic text-slate-500 dark:bg-slate-800/30">
                    <td class="px-4 py-2.5">{{ \Carbon\Carbon::parse($start_date)->format('Y-m-d') }}</td>
                    <td class="px-4 py-2.5">OPENING</td>
                    <td class="px-4 py-2.5">—</td>
                    <td class="px-4 py-2.5">Opening Balance as of {{ $start_date }}</td>
                    <td class="px-4 py-2.5 text-right font-mono">—</td>
                    <td class="px-4 py-2.5 text-right font-mono">—</td>
                    <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                        {{ number_format((float) $opening_balance, 2) }}
                    </td>
                    <td class="px-4 py-2.5 text-center">—</td>
                </tr>

                @forelse ($items as $item)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-600 dark:text-slate-300">
                            {{ $item['date'] }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span @class([
                                'inline-block rounded px-2 py-0.5 text-xs font-semibold',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => in_array($item['type'], ['DEPOSIT', 'TRANSFER_IN', 'MARKUP', 'CHEQUE_DEPOSIT']),
                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => in_array($item['type'], ['WITHDRAWAL', 'TRANSFER_OUT', 'CHARGES', 'CHEQUE_BOUNCE']),
                            ])>
                                {{ str_replace('_', ' ', $item['type']) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $item['reference'] }}</td>
                        <td class="px-4 py-2.5 text-slate-800 dark:text-slate-200">{{ $item['description'] }}</td>
                        <td class="px-4 py-2.5 text-right font-mono font-medium text-emerald-600">
                            {{ (float) $item['credit'] > 0 ? number_format((float) $item['credit'], 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono font-medium text-red-600">
                            {{ (float) $item['debit'] > 0 ? number_format((float) $item['debit'], 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                            {{ number_format((float) $item['running_balance'], 2) }}
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @if ($item['reconciled'])
                                <span class="rounded bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">Reconciled</span>
                            @else
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-800">Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            No transactions in the selected date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right uppercase">Period Totals:</td>
                    <td class="px-4 py-3 text-right font-mono text-emerald-700 dark:text-emerald-300">
                        {{ number_format((float) $total_credits, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-red-700 dark:text-red-300">
                        {{ number_format((float) $total_debits, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-slate-900 dark:text-white">
                        {{ number_format((float) $closing_balance, 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
