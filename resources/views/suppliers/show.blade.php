@extends('layouts.app')

@section('title', $supplier->name . ' — Supplier Ledger')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('suppliers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Suppliers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $supplier->code }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $supplier->name }}</h1>
                <span class="rounded bg-red-100 px-2.5 py-0.5 font-mono text-xs font-bold text-red-800 dark:bg-red-950/60 dark:text-red-300">{{ $supplier->code }}</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{{ $supplier->status }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Oil Marketing Company / Fuel & Lubricant Vendor Account.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.create') }}?supplier_id={{ $supplier->id }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-red-700 transition shadow-sm">
                <span>📥</span> New Decantation
            </a>
            <a href="{{ route('suppliers.statement', $supplier) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                <span>📄</span> Statement
            </a>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                <span>✏️</span> Edit
            </a>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Balance Payable</div>
            <div class="mt-2 text-3xl font-extrabold text-red-600 dark:text-red-500">
                {{ \App\Support\PakistaniCurrency::format($supplier->current_balance) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($supplier->current_balance) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tax & Regulatory Identity</div>
            <dl class="mt-3 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">NTN Number:</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ $supplier->ntn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">STRN Number:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $supplier->strn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Contact Person:</dt>
                    <dd class="text-slate-900 dark:text-white">{{ $supplier->contact_person ?? 'Direct' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Terminal & Contact</div>
            <div class="mt-2 text-xs text-slate-700 dark:text-slate-300">
                <div><strong>Phone:</strong> {{ $supplier->phone ?? 'N/A' }}</div>
                <div class="mt-1"><strong>Email:</strong> {{ $supplier->email ?? 'N/A' }}</div>
                <div class="mt-1"><strong>Address:</strong> {{ $supplier->address ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    {{-- Supplier Payment Form --}}
    @if(auth()->user()->hasPermission(\App\Support\PermissionList::SUPPLIER_PAYMENT))
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" x-data="{ method: 'BANK_TRANSFER' }">
            <div class="border-b border-slate-200 pb-3 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💳</span> Record Supplier Payment / OMC Remittance
                </h2>
                <p class="text-xs text-slate-500">Record payments made to OMC / supplier to debit ledger and reduce payable balance.</p>
            </div>

            <form method="POST" action="{{ route('suppliers.payments.store', $supplier) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Payment Date *</label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Payment Method *</label>
                    <select name="payment_method" x-model="method" required
                            class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="BANK_TRANSFER">Bank Online Transfer (OMC Account)</option>
                        <option value="CHEQUE">Cheque / Demand Draft</option>
                        <option value="CASH">Cash (Cash Desk Entry)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Amount (Rs.) *</label>
                    <input type="number" step="0.01" name="amount" required placeholder="e.g. 500000"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div x-show="method === 'BANK_TRANSFER'">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Station Bank Account *</label>
                    <select name="bank_account_id" class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">Select Bank Account</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name ?? 'Bank' }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="method === 'CHEQUE'">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Cheque Number *</label>
                    <input type="text" name="cheque_number" placeholder="Cheque #"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Payment Notes / Ref</label>
                    <input type="text" name="notes" placeholder="e.g. Tanker decantation prepayment / invoice settlement"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-sm">
                        Confirm & Record Payment
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Supplier Ledger Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📖</span> Supplier Chronological Ledger
                </h2>
                <p class="text-xs text-slate-500">Purchases increase payable (Credit); Payments reduce payable (Debit).</p>
            </div>
            <a href="{{ route('suppliers.statement', $supplier) }}" class="text-xs font-semibold text-red-600 hover:text-red-700">
                View Full Printable Statement →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-right">Debit (Payments)</th>
                        <th class="px-5 py-3 text-right">Credit (Purchases)</th>
                        <th class="px-5 py-3 text-right">Running Payable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($ledgerEntries as $entry)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-3 font-mono">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $entry->description }}</div>
                                @if($entry->reference_type)
                                    <div class="text-[10px] text-slate-400">Ref: {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                {{ \App\Support\Money::compare($entry->debit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->debit, false) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-red-600 dark:text-red-400">
                                {{ \App\Support\Money::compare($entry->credit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->credit, false) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($entry->running_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                No ledger transactions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ledgerEntries->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                {{ $ledgerEntries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
