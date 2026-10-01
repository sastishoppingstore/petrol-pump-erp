@extends('layouts.app')

@section('title', __('finance.bank_reconciliation.title') . ' — ' . $account->account_title)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">{{ __('finance.common.banks_word') }}</a></li>
    <li class="text-slate-500">{{ __('finance.bank_reconciliation.crumb') }}</li>
@endsection

@section('content')
<div class="page-head">
    <h1>{{ __('finance.bank_reconciliation.title') }}</h1>
    <p>
        {{ $account->bank?->name }} &bull; {{ $account->account_title }} &bull; <span class="font-mono text-xs">{{ $account->account_number }}</span>
    </p>
    <div class="page-actions">
        <a href="{{ route('banks.book', $account) }}" class="btn-3d btn-3d-navy">
            {{ __('finance.bank_reconciliation.view_book') }}
        </a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Reconciliation Form --}}
    <div class="lg:col-span-1">
        <div class="glass-card card-3d p-5">
            <h2 class="mb-4 text-center text-base font-bold text-slate-900 dark:text-white">{{ __('finance.bank_reconciliation.form_heading') }}</h2>

            <form method="POST" action="{{ route('banks.reconciliation.store', $account) }}" id="reconciliationForm">
                @csrf
                <div class="space-y-4">
                    <div class="field-3d">
                        <label for="statement_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.bank_reconciliation.statement_date') }}</label>
                        <input type="date" id="statement_date" name="statement_date" value="{{ date('Y-m-d') }}" required
                               class="input-3d w-full text-center text-sm">
                    </div>

                    <div class="field-3d">
                        <label for="statement_balance" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.bank_reconciliation.statement_balance_label') }}</label>
                        <input type="number" step="0.01" id="statement_balance" name="statement_balance" required
                               placeholder="{{ __('finance.bank_reconciliation.ph_500000') }}"
                               class="input-3d tabular w-full text-center font-mono text-sm">
                    </div>

                    <div class="rounded-xl bg-slate-50/80 p-3 text-center text-xs dark:bg-slate-800/60">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('finance.bank_reconciliation.system_ledger_balance') }}</span>
                        <div class="tabular font-mono text-sm font-bold text-slate-900 dark:text-white">Rs. {{ number_format((float) $currentBalance, 2) }}</div>
                    </div>

                    <div class="field-3d">
                        <label for="notes" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.bank_reconciliation.notes_remarks') }}</label>
                        <textarea id="notes" name="notes" rows="2" class="input-3d w-full text-center text-sm" placeholder="{{ __('finance.bank_reconciliation.ph_notes') }}"></textarea>
                    </div>

                    <button type="submit" class="btn-3d btn-3d-primary w-full">
                        {{ __('finance.bank_reconciliation.complete') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- CSV Import Helper --}}
        <div class="glass-card card-3d mt-6 p-5">
            <h3 class="mb-2 text-center text-sm font-bold text-slate-900 dark:text-white">{{ __('finance.bank_reconciliation.import_heading') }}</h3>
            <p class="mb-3 text-center text-xs text-slate-500">{{ __('finance.bank_reconciliation.import_sub') }}</p>
            <form method="POST" action="{{ route('banks.import-csv', $account) }}" enctype="multipart/form-data">
                @csrf
                <div class="field-3d">
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="input-3d mb-3 block w-full text-xs text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                </div>
                <button type="submit" class="btn-3d btn-3d-ghost w-full">
                    {{ __('finance.bank_reconciliation.parse_csv') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Unreconciled Transactions --}}
    <div class="lg:col-span-2">
        <div class="glass-card overflow-hidden">
            <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('finance.bank_reconciliation.unreconciled_heading') }}</h2>
                <span class="mt-1 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $unreconciled->count() }} {{ __('finance.bank_reconciliation.pending_word') }}
                </span>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('finance.bank_reconciliation.match') }}</th>
                            <th>{{ __('finance.common.date') }}</th>
                            <th>{{ __('finance.common.type') }}</th>
                            <th>{{ __('finance.bank_reconciliation.ref_short') }}</th>
                            <th>{{ __('finance.common.description') }}</th>
                            <th>{{ __('finance.common.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unreconciled as $txn)
                            @php $isCredit = in_array($txn->type, ['DEPOSIT', 'TRANSFER_IN', 'MARKUP', 'CHEQUE_DEPOSIT'], true); @endphp
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
                                <td class="tabular font-mono font-semibold {{ $isCredit ? 'text-emerald-600' : 'text-vital-primary' }}">
                                    {{ $isCredit ? '+' : '-' }} Rs. {{ number_format((float) $txn->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">
                                    {{ __('finance.bank_reconciliation.all_reconciled') }}
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
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('finance.bank_reconciliation.history_heading') }}</h3>
                </div>
                <div class="table-3d">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('finance.bank_reconciliation.statement_date') }}</th>
                                <th>{{ __('finance.bank_reconciliation.statement_balance') }}</th>
                                <th>{{ __('finance.banks.ledger_balance') }}</th>
                                <th>{{ __('finance.bank_reconciliation.difference') }}</th>
                                <th>{{ __('finance.common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr>
                                    <td class="text-xs">{{ $h->statement_date?->format('Y-m-d') }}</td>
                                    <td class="tabular font-mono text-xs">Rs. {{ number_format((float) $h->statement_balance, 2) }}</td>
                                    <td class="tabular font-mono text-xs">Rs. {{ number_format((float) $h->ledger_balance, 2) }}</td>
                                    <td class="tabular font-mono text-xs {{ abs((float) $h->statement_balance - (float) $h->ledger_balance) < 0.01 ? 'text-emerald-600' : 'text-vital-primary font-bold' }}">
                                        Rs. {{ number_format((float) $h->statement_balance - (float) $h->ledger_balance, 2) }}
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
