@extends('layouts.app')

@section('title', 'Bank Reconciliation — ' . $account->account_title)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Reconciliation</li>
@endsection

@section('content')
<div class="page-head">
    <h1>Bank Reconciliation / بینک مطابقت</h1>
    <p>
        {{ $account->bank?->name }} &bull; {{ $account->account_title }} &bull; <span class="font-mono text-xs">{{ $account->account_number }}</span>
    </p>
    <div class="page-actions">
        <a href="{{ route('banks.book', $account) }}" class="btn-3d btn-3d-navy">
            View Bank Book
        </a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Reconciliation Form --}}
    <div class="lg:col-span-1">
        <div class="glass-card card-3d p-5">
            <h2 class="mb-4 text-center text-base font-bold text-slate-900 dark:text-white">New Statement Reconciliation</h2>

            <form method="POST" action="{{ route('banks.reconciliation.store', $account) }}" id="reconciliationForm">
                @csrf
                <div class="space-y-4">
                    <div class="field-3d">
                        <label for="statement_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Statement Date</label>
                        <input type="date" id="statement_date" name="statement_date" value="{{ date('Y-m-d') }}" required
                               class="input-3d w-full text-center text-sm">
                    </div>

                    <div class="field-3d">
                        <label for="statement_balance" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Statement Ending Balance (Rs.)</label>
                        <input type="number" step="0.01" id="statement_balance" name="statement_balance" required
                               placeholder="e.g. 500000.00"
                               class="input-3d tabular w-full text-center font-mono text-sm">
                    </div>

                    <div class="rounded-xl bg-slate-50/80 p-3 text-center text-xs dark:bg-slate-800/60">
                        <span class="text-slate-600 dark:text-slate-400">System Ledger Balance:</span>
                        <div class="tabular font-mono text-sm font-bold text-slate-900 dark:text-white">Rs. {{ number_format((float) $currentBalance, 2) }}</div>
                    </div>

                    <div class="field-3d">
                        <label for="notes" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Notes / Audit Remarks</label>
                        <textarea id="notes" name="notes" rows="2" class="input-3d w-full text-center text-sm" placeholder="e.g. Monthly reconciliation for Meezan current account"></textarea>
                    </div>

                    <button type="submit" class="btn-3d btn-3d-primary w-full">
                        Complete Reconciliation
                    </button>
                </div>
            </form>
        </div>

        {{-- CSV Import Helper --}}
        <div class="glass-card card-3d mt-6 p-5">
            <h3 class="mb-2 text-center text-sm font-bold text-slate-900 dark:text-white">Import Bank Statement CSV</h3>
            <p class="mb-3 text-center text-xs text-slate-500">Upload CSV exported from HBL, Meezan, Bank Alfalah or UBL online portal.</p>
            <form method="POST" action="{{ route('banks.import-csv', $account) }}" enctype="multipart/form-data">
                @csrf
                <div class="field-3d">
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="input-3d mb-3 block w-full text-xs text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                </div>
                <button type="submit" class="btn-3d btn-3d-ghost w-full">
                    Parse Statement CSV
                </button>
            </form>
        </div>
    </div>

    {{-- Unreconciled Transactions --}}
    <div class="lg:col-span-2">
        <div class="glass-card overflow-hidden">
            <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Unreconciled System Transactions</h2>
                <span class="mt-1 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $unreconciled->count() }} Pending
                </span>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Ref #</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unreconciled as $txn)
                            <tr>
                                <td>
                                    <input type="checkbox" name="matched_ids[]" value="{{ $txn->id }}" form="reconciliationForm" class="rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                                </td>
                                <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                    {{ $txn->transaction_date?->format('Y-m-d') }}
                                </td>
                                <td>
                                    <span class="rounded px-2 py-0.5 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $txn->type }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs text-slate-500">{{ $txn->reference_number }}</td>
                                <td class="text-slate-700 dark:text-slate-300">{{ $txn->description }}</td>
                                <td class="tabular font-mono font-semibold {{ $txn->isCredit() ? 'text-emerald-600' : 'text-vital-primary' }}">
                                    {{ $txn->isCredit() ? '+' : '-' }} Rs. {{ number_format((float) $txn->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">
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
            <div class="glass-card mt-6 overflow-hidden">
                <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Past Reconciliation Runs</h3>
                </div>
                <div class="table-3d">
                    <table>
                        <thead>
                            <tr>
                                <th>Statement Date</th>
                                <th>Statement Balance</th>
                                <th>Ledger Balance</th>
                                <th>Difference</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr>
                                    <td class="text-xs">{{ $h->statement_date?->format('Y-m-d') }}</td>
                                    <td class="tabular font-mono text-xs">Rs. {{ number_format((float) $h->statement_balance, 2) }}</td>
                                    <td class="tabular font-mono text-xs">Rs. {{ number_format((float) $h->ledger_balance, 2) }}</td>
                                    <td class="tabular font-mono text-xs {{ (float) $h->difference == 0 ? 'text-emerald-600' : 'text-vital-primary font-bold' }}">
                                        Rs. {{ number_format((float) $h->difference, 2) }}
                                    </td>
                                    <td class="text-xs">
                                        <span class="pill-status pill-active"><span class="dot"></span>{{ $h->status }}</span>
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
