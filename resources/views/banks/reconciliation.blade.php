@extends('layouts.app')

@section('title', 'Bank Reconciliation — ' . $account->account_title)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Reconciliation</li>
@endsection

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Bank Reconciliation / بینک مطابقت</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $account->bank?->name }} &bull; {{ $account->account_title }} &bull; <span class="font-mono text-xs">{{ $account->account_number }}</span>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('banks.book', $account) }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            View Bank Book
        </a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Reconciliation Form --}}
    <div class="lg:col-span-1">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-4 text-base font-bold text-slate-900 dark:text-white">New Statement Reconciliation</h2>

            <form method="POST" action="{{ route('banks.reconciliation.store', $account) }}" id="reconciliationForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="statement_date" class="mb-1 block text-xs font-semibold uppercase text-slate-500">Statement Date</label>
                        <input type="date" id="statement_date" name="statement_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>

                    <div>
                        <label for="statement_balance" class="mb-1 block text-xs font-semibold uppercase text-slate-500">Statement Ending Balance (Rs.)</label>
                        <input type="number" step="0.01" id="statement_balance" name="statement_balance" required
                               placeholder="e.g. 500000.00"
                               class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>

                    <div class="rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-800">
                        <div class="flex justify-between py-1 text-slate-600 dark:text-slate-400">
                            <span>System Ledger Balance:</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">Rs. {{ number_format((float) $currentBalance, 2) }}</span>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-xs font-semibold uppercase text-slate-500">Notes / Audit Remarks</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800" placeholder="e.g. Monthly reconciliation for Meezan current account"></textarea>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 font-semibold text-white hover:bg-red-700">
                        Complete Reconciliation
                    </button>
                </div>
            </form>
        </div>

        {{-- CSV Import Helper --}}
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-white">Import Bank Statement CSV</h3>
            <p class="mb-3 text-xs text-slate-500">Upload CSV exported from HBL, Meezan, Bank Alfalah or UBL online portal.</p>
            <form method="POST" action="{{ route('banks.import-csv', $account) }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="csv_file" accept=".csv,.txt" required class="mb-3 block w-full text-xs text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                <button type="submit" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Parse Statement CSV
                </button>
            </form>
        </div>
    </div>

    {{-- Unreconciled Transactions --}}
    <div class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Unreconciled System Transactions</h2>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $unreconciled->count() }} Pending
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Match</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Ref #</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($unreconciled as $txn)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-2.5">
                                    <input type="checkbox" name="matched_ids[]" value="{{ $txn->id }}" form="reconciliationForm" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $txn->transaction_date?->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="rounded px-2 py-0.5 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $txn->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $txn->reference_number }}</td>
                                <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300">{{ $txn->description }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-semibold {{ $txn->isCredit() ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $txn->isCredit() ? '+' : '-' }} Rs. {{ number_format((float) $txn->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    All bank transactions for this account are reconciled! 🎉
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Reconciliation History --}}
        @if ($history->isNotEmpty())
            <div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Past Reconciliation Runs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-2">Statement Date</th>
                                <th class="px-4 py-2 text-right">Statement Balance</th>
                                <th class="px-4 py-2 text-right">Ledger Balance</th>
                                <th class="px-4 py-2 text-right">Difference</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($history as $h)
                                <tr>
                                    <td class="px-4 py-2 text-xs">{{ $h->statement_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-xs">Rs. {{ number_format((float) $h->statement_balance, 2) }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-xs">Rs. {{ number_format((float) $h->ledger_balance, 2) }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-xs {{ (float) $h->difference == 0 ? 'text-emerald-600' : 'text-red-600 font-bold' }}">
                                        Rs. {{ number_format((float) $h->difference, 2) }}
                                    </td>
                                    <td class="px-4 py-2 text-xs">
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">{{ $h->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
