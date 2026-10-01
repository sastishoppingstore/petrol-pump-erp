@extends('layouts.app')

@section('title', 'Bank Book — ' . $account->account_title)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Bank Book</li>
@endsection

@section('content')
<div class="page-head">
    <h1>Bank Book / لیجر
        <span class="ml-2 align-middle rounded-full bg-vital-primary/10 px-3 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
            {{ $account->bank?->short_name ?? 'BANK' }}
        </span>
    </h1>
    <p>
        {{ $account->bank?->name }} &bull; {{ $account->account_title }} &bull;
        <span class="font-mono text-xs">{{ $account->account_number }}</span>
        @if($account->iban)
            &bull; <span class="font-mono text-xs">IBAN: {{ $account->iban }}</span>
        @endif
    </p>
    <div class="page-actions">
        <a href="{{ route('bank-deposits.index', ['bank_account_id' => $account->id]) }}" class="btn-3d btn-3d-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Deposits
        </a>
        <a href="{{ route('banks.reconciliation', $account) }}" class="btn-3d btn-3d-navy">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Reconcile
        </a>
        <button onclick="window.print()" class="btn-3d btn-3d-ghost">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Bank Book
        </button>
    </div>
</div>

{{-- Filter Card --}}
<div class="glass-card mb-6 p-4">
    <form method="GET" action="{{ route('banks.book', $account) }}" class="grid gap-4 sm:grid-cols-4">
        <div class="field-3d">
            <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Switch Account</label>
            <select onchange="window.location.href=this.value" class="input-3d w-full text-center text-sm">
                @foreach ($allAccounts as $acc)
                    <option value="{{ route('banks.book', $acc) }}" @selected($acc->id === $account->id)>
                        {{ $acc->bank?->short_name }} — {{ $acc->account_title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field-3d">
            <label for="from" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">From Date</label>
            <input type="date" id="from" name="from" value="{{ $start_date }}" class="input-3d w-full text-center text-sm">
        </div>
        <div class="field-3d">
            <label for="to" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">To Date</label>
            <input type="date" id="to" name="to" value="{{ $end_date }}" class="input-3d w-full text-center text-sm">
        </div>
        <div class="flex items-end justify-center gap-2">
            <button type="submit" class="btn-3d btn-3d-primary w-full">Filter Ledger</button>
            <a href="{{ route('banks.book', $account) }}" class="btn-3d btn-3d-ghost">Reset</a>
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="mb-6 grid gap-4 sm:grid-cols-4">
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">Opening Balance (ابتداء)</div>
        <div class="stat-value tabular">Rs. {{ number_format((float) $opening_balance, 2) }}</div>
        <div class="stat-sub">As of {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }}</div>
    </div>
    <div class="stat-tile-3d stat-green">
        <div class="stat-label">Total Deposits / Credits (جمع)</div>
        <div class="stat-value tabular">+ Rs. {{ number_format((float) $total_credits, 2) }}</div>
        <div class="stat-sub">Inward cash, transfers &amp; cheques</div>
    </div>
    <div class="stat-tile-3d stat-red">
        <div class="stat-label">Total Withdrawals / Debits (بنام)</div>
        <div class="stat-value tabular">- Rs. {{ number_format((float) $total_debits, 2) }}</div>
        <div class="stat-sub">Outward cash, charges &amp; payments</div>
    </div>
    <div class="stat-tile-3d stat-slate">
        <div class="stat-label">Closing Balance (بقایا بیلنس)</div>
        <div class="stat-value tabular">Rs. {{ number_format((float) $closing_balance, 2) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $closing_balance) }}</div>
    </div>
</div>

{{-- Transactions Ledger Table --}}
<div class="glass-card overflow-hidden">
    <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Transaction Statement</h2>
        <span class="text-xs text-slate-500">{{ count($items) }} entries</span>
    </div>
    <div class="table-3d">
        <table>
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Type</th>
                    <th>Reference #</th>
                    <th>Description</th>
                    <th class="text-emerald-600">Credit (جمع)</th>
                    <th class="text-vital-primary">Debit (بنام)</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-slate-50/60 font-medium italic text-slate-500 dark:bg-slate-800/30">
                    <td>{{ \Carbon\Carbon::parse($start_date)->format('Y-m-d') }}</td>
                    <td>OPENING</td>
                    <td>—</td>
                    <td>Opening Balance as of {{ $start_date }}</td>
                    <td class="tabular font-mono">—</td>
                    <td class="tabular font-mono">—</td>
                    <td class="tabular font-mono font-bold text-slate-800 dark:text-slate-200">
                        {{ number_format((float) $opening_balance, 2) }}
                    </td>
                    <td>—</td>
                </tr>

                @forelse ($items as $item)
                    <tr>
                        <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ $item['date'] }}
                        </td>
                        <td>
                            <span @class([
                                'inline-block rounded px-2 py-0.5 text-xs font-semibold',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => in_array($item['type'], ['DEPOSIT', 'TRANSFER_IN', 'MARKUP', 'CHEQUE_DEPOSIT']),
                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => in_array($item['type'], ['WITHDRAWAL', 'TRANSFER_OUT', 'CHARGES', 'CHEQUE_BOUNCE']),
                            ])>
                                {{ str_replace('_', ' ', $item['type']) }}
                            </span>
                        </td>
                        <td class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</td>
                        <td class="text-slate-800 dark:text-slate-200">{{ $item['description'] }}</td>
                        <td class="tabular font-mono font-medium text-emerald-600">
                            {{ (float) $item['credit'] > 0 ? number_format((float) $item['credit'], 2) : '—' }}
                        </td>
                        <td class="tabular font-mono font-medium text-vital-primary">
                            {{ (float) $item['debit'] > 0 ? number_format((float) $item['debit'], 2) : '—' }}
                        </td>
                        <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                            {{ number_format((float) $item['running_balance'], 2) }}
                        </td>
                        <td>
                            @if ($item['reconciled'])
                                <span class="rounded bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">Reconciled</span>
                            @else
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-800">Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            No transactions in the selected date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                <tr>
                    <td colspan="4" class="uppercase">Period Totals:</td>
                    <td class="tabular font-mono text-emerald-700 dark:text-emerald-300">
                        {{ number_format((float) $total_credits, 2) }}
                    </td>
                    <td class="tabular font-mono text-vital-primary dark:text-red-300">
                        {{ number_format((float) $total_debits, 2) }}
                    </td>
                    <td class="tabular font-mono text-slate-900 dark:text-white">
                        {{ number_format((float) $closing_balance, 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
