@extends('layouts.app')

@section('title', 'Bank Deposits — Cash to Bank')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Deposits</li>
@endsection

@section('content')
<div x-data="{ openModal: false }" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Cash to Bank Deposits / بینک میں رقم جمع</h1>
            <p class="mt-1 text-sm text-slate-500">
                Record physical cash handed over to bank branch with slip photo attachment.
            </p>
        </div>
        <div class="flex gap-2">
            @can('cash.create')
                <button @click="openModal = true" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Deposit Cash to Bank (سلپ داخل کریں)
                </button>
            @endcan
            <a href="{{ route('banks.index') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                All Bank Accounts
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('bank-deposits.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 sm:grid-cols-4">
            <div>
                <label for="bank_account_id" class="mb-1 block text-xs font-semibold uppercase text-slate-500">Bank Account</label>
                <select id="bank_account_id" name="bank_account_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All bank accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('bank_account_id') === (string) $account->id)>
                            {{ $account->bank?->short_name ?? $account->bank?->name }} — {{ $account->account_title }} ({{ $account->maskedAccountNumber() }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="mb-1 block text-xs font-semibold uppercase text-slate-500">From Date</label>
                <input type="date" id="from" name="from" value="{{ request('from') }}"
                       class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-semibold uppercase text-slate-500">To Date</label>
                <input type="date" id="to" name="to" value="{{ request('to') }}"
                       class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900 dark:bg-navy-700">Filter</button>
                <a href="{{ route('bank-deposits.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Reset</a>
            </div>
        </div>
    </form>

    {{-- Deposit History Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Date / Time</th>
                        <th class="px-4 py-3">Bank &amp; Account</th>
                        <th class="px-4 py-3">Deposit Ref #</th>
                        <th class="px-4 py-3 text-right">Amount (رقم)</th>
                        <th class="px-4 py-3 text-right">Balance After</th>
                        <th class="px-4 py-3">Shift</th>
                        <th class="px-4 py-3">Depositor</th>
                        <th class="px-4 py-3 text-center">Slip Proof</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($deposits as $d)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                {{ $d->deposited_at?->format('d M Y, h:i A') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $d->bank_name }}</div>
                                <div class="text-xs text-slate-400">{{ $d->bankAccount?->account_title }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                                {{ $d->reference_number }}
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono font-bold text-emerald-600">
                                Rs. {{ number_format((float) $d->amount, 2) }}
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono text-xs text-slate-500">
                                Rs. {{ number_format((float) $d->balance_after, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($d->shift)
                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $d->shift->shift_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                {{ $d->depositor?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($d->slip_path)
                                    <a href="{{ asset('storage/' . $d->slip_path) }}" target="_blank" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50 dark:bg-slate-800 dark:text-red-400">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        View Slip
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">No slip</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'rounded px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => $d->status === 'COMPLETED',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => $d->status === 'REVERSED',
                                ])>{{ $d->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                No bank deposits found for this selection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $deposits->links() }}</div>
    </div>

    {{-- Modal: Record Cash Deposit --}}
    <div x-show="openModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="openModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Record Cash Deposit (کیش جمع کروائیں)</h2>
                    <button @click="openModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('bank-deposits.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="modal_bank_account_id" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Destination Bank Account *</label>
                        <select id="modal_bank_account_id" name="bank_account_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select bank account…</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->bank?->name }} — {{ $acc->account_title }} (Bal: Rs. {{ number_format((float) $acc->currentBalance(), 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="modal_amount" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" id="modal_amount" name="amount" required placeholder="e.g. 250000"
                               class="w-full rounded-lg border-slate-300 font-mono text-base font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="modal_reference" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Slip / Deposit Ref #</label>
                            <input type="text" id="modal_reference" name="reference" placeholder="e.g. HBL-DEP-09823"
                                   class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        </div>
                        <div>
                            <label for="modal_shift_id" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Source Active Shift</label>
                            <select id="modal_shift_id" name="shift_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                                <option value="">Outside Shift / Direct Cash</option>
                                @foreach ($shifts as $s)
                                    <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="modal_slip" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Deposit Slip Photo / تصویری ثبوت</label>
                        <input type="file" id="modal_slip" name="slip" accept="image/*,.pdf"
                               class="w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-red-700 hover:file:bg-red-100 dark:file:bg-slate-800 dark:file:text-red-300">
                        <span class="mt-1 block text-[11px] text-slate-400">Supports JPG, PNG or PDF up to 5MB.</span>
                    </div>

                    <div>
                        <label for="modal_notes" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Notes / تفصیل</label>
                        <textarea id="modal_notes" name="notes" rows="2" placeholder="e.g. Evening cash deposit by manager"
                                  class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button type="button" @click="openModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                            Save Cash Deposit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
