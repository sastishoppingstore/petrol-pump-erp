@extends('layouts.app')

@section('title', $customer->name . ' — Customer Profile')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Customers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $customer->code }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $customer->name }}</h1>
                <span class="rounded bg-red-100 px-2.5 py-0.5 font-mono text-xs font-bold text-red-800 dark:bg-red-950/60 dark:text-red-300">{{ $customer->code }}</span>
                <span @class([
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-emerald-100 text-emerald-800' => $customer->status === 'ACTIVE',
                    'bg-slate-100 text-slate-800' => $customer->status === 'INACTIVE',
                ])>{{ $customer->status }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Registered account at Mehar Filling Station (Vital Petroleum franchise), Sheikhupura.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $whatsappLink }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100 transition shadow-sm">
                <span>💬</span> WhatsApp Reminder
            </a>
            <a href="{{ route('customers.statement', $customer) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                <span>📄</span> Statement
            </a>
            <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                <span>✏️</span> Edit
            </a>
        </div>
    </div>

    {{-- Info & Balances Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Card 1: Balance & Credit Limit --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Outstanding Balance</div>
            <div class="mt-2 text-3xl font-extrabold {{ \App\Support\Money::compare($customer->current_balance, '0.00') > 0 ? 'text-red-600 dark:text-red-500' : 'text-slate-900 dark:text-white' }}">
                {{ \App\Support\PakistaniCurrency::format($customer->current_balance) }}
            </div>
            <div class="mt-1 text-xs text-slate-500 font-medium">
                {{ \App\Support\PakistaniCurrency::toUrduWords($customer->current_balance) }}
            </div>

            <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                <div class="flex justify-between text-xs text-slate-500 mb-1">
                    <span>Approved Credit Limit</span>
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ $customer->creditLimitIsUnlimited() ? 'Unlimited' : \App\Support\PakistaniCurrency::format($customer->credit_limit, true, 0) }}
                    </span>
                </div>
                @if(! $customer->creditLimitIsUnlimited())
                    @php
                        $limit = (float) $customer->credit_limit;
                        $bal = (float) $customer->current_balance;
                        $pct = $limit > 0 ? min(100, round(($bal / $limit) * 100)) : 0;
                    @endphp
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden dark:bg-slate-800">
                        <div class="h-full {{ $pct > 90 ? 'bg-red-600' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400 mt-1">
                        <span>Used: {{ $pct }}%</span>
                        <span>Remaining: {{ \App\Support\PakistaniCurrency::format($customer->availableCredit(), true, 0) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Ageing Breakdown --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Udhaar Ageing Breakdown (FIFO)</div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-lg bg-emerald-50 p-2.5 dark:bg-emerald-950/30">
                    <span class="text-slate-500">0 - 30 Days</span>
                    <div class="font-bold text-emerald-800 dark:text-emerald-300 mt-0.5">
                        {{ \App\Support\PakistaniCurrency::format($ageing['0_30'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-lg bg-blue-50 p-2.5 dark:bg-blue-950/30">
                    <span class="text-slate-500">31 - 60 Days</span>
                    <div class="font-bold text-blue-800 dark:text-blue-300 mt-0.5">
                        {{ \App\Support\PakistaniCurrency::format($ageing['31_60'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-lg bg-amber-50 p-2.5 dark:bg-amber-950/30">
                    <span class="text-slate-500">61 - 90 Days</span>
                    <div class="font-bold text-amber-800 dark:text-amber-300 mt-0.5">
                        {{ \App\Support\PakistaniCurrency::format($ageing['61_90'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-lg bg-red-50 p-2.5 dark:bg-red-950/30">
                    <span class="text-slate-500">90+ Days (Overdue)</span>
                    <div class="font-bold text-red-800 dark:text-red-300 mt-0.5">
                        {{ \App\Support\PakistaniCurrency::format($ageing['over_90'], true, 0) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Identification & Contact --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer Identification</div>
            <dl class="mt-3 space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Mobile Phone:</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ $customer->phone ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">CNIC:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $customer->cnic ?? 'Not provided' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">NTN Number:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $customer->ntn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tax Liable:</dt>
                    <dd class="text-slate-900 dark:text-white">{{ $customer->is_tax_liable ? 'Yes (FBR Invoicing)' : 'No' }}</dd>
                </div>
                <div class="pt-1">
                    <dt class="text-slate-500">Address:</dt>
                    <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $customer->address ?? 'Sheikhupura' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Payment Collection Form --}}
    @if(auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_PAYMENT))
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" x-data="{ method: 'CASH' }">
            <div class="border-b border-slate-200 pb-3 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💵</span> Receive Customer Payment (Udhaar Wusooli)
                </h2>
                <p class="text-xs text-slate-500">Record payments received via Cash, Bank Transfer, or Cheque to credit customer ledger.</p>
            </div>

            <form method="POST" action="{{ route('customers.payments.store', $customer) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
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
                        <option value="CASH">Cash (Cash Desk Entry)</option>
                        <option value="BANK_TRANSFER">Bank Online Transfer</option>
                        <option value="CHEQUE">Cheque</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Amount (Rs.) *</label>
                    <input type="number" step="0.01" name="amount" required placeholder="e.g. 50000"
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
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Notes / Remarks</label>
                    <input type="text" name="notes" placeholder="e.g. Received from Manager Tariq for Truck fleet"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition shadow-sm">
                        Confirm & Credit Ledger
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Vehicles Section --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🚛</span> Registered Customer Vehicles (Fleet)
                </h2>
                <p class="text-xs text-slate-500">Vehicles authorized to fill fuel against this credit line.</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Vehicle List --}}
            <div class="lg:col-span-2 space-y-3">
                @forelse($customer->vehicles as $v)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="rounded bg-slate-100 p-2 text-xl dark:bg-slate-800">
                                @if($v->type === 'TRUCK') 🚚 @elseif($v->type === 'BUS') 🚌 @elseif($v->type === 'TRACTOR') 🚜 @else 🚗 @endif
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white font-mono text-sm">
                                    {{ $v->registration_number }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    <span>Driver: <strong class="text-slate-700 dark:text-slate-300">{{ $v->driver_name ?? 'N/A' }}</strong></span>
                                    • <span>{{ $v->make }} {{ $v->model }}</span>
                                    @if($v->tank_capacity) • <span>Capacity: {{ $v->tank_capacity }} L</span> @endif
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('customers.vehicles.destroy', [$customer, $v]) }}" onsubmit="return confirm('Remove vehicle {{ $v->registration_number }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Remove</button>
                        </form>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-400 border border-dashed rounded-lg">
                        No vehicles registered yet. Register vehicles below for pump attendant verification.
                    </div>
                @endforelse
            </div>

            {{-- Quick Add Vehicle Form --}}
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/50">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3">Add Vehicle</h3>
                <form method="POST" action="{{ route('customers.vehicles.store', $customer) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Reg Plate (e.g. LEA-1234) *</label>
                        <input type="text" name="registration_number" required placeholder="LEA-1234"
                               class="mt-0.5 w-full rounded border-slate-300 text-xs font-mono uppercase focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Driver Name</label>
                        <input type="text" name="driver_name" placeholder="e.g. Muhammad Tariq"
                               class="mt-0.5 w-full rounded border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Vehicle Type</label>
                            <select name="type" class="mt-0.5 w-full rounded border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                <option value="TRUCK">Truck</option>
                                <option value="CAR">Car</option>
                                <option value="BUS">Bus</option>
                                <option value="PICKUP">Pickup</option>
                                <option value="TRACTOR">Tractor</option>
                                <option value="MOTORCYCLE">Motorcycle</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Tank Cap (L)</label>
                            <input type="number" step="0.001" name="tank_capacity" placeholder="e.g. 300"
                                   class="mt-0.5 w-full rounded border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Make & Model</label>
                        <input type="text" name="make" placeholder="e.g. Bedford Rocket / Hino 500"
                               class="mt-0.5 w-full rounded border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <input type="hidden" name="status" value="ACTIVE">
                    <button type="submit" class="w-full rounded bg-slate-800 py-1.5 text-xs font-semibold text-white hover:bg-slate-900 transition dark:bg-slate-700">
                        + Add to Fleet
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Append-Only Customer Ledger --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📖</span> Append-Only Customer Ledger
                </h2>
                <p class="text-xs text-slate-500">Immutable chronological transaction log with running balance.</p>
            </div>
            <a href="{{ route('customers.statement', $customer) }}" class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">
                View Full Printable Statement →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-right">Debit (Udhaar)</th>
                        <th class="px-5 py-3 text-right">Credit (Wusooli)</th>
                        <th class="px-5 py-3 text-right">Running Balance</th>
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
                            <td class="px-5 py-3 text-right font-medium text-red-600 dark:text-red-400">
                                {{ \App\Support\Money::compare($entry->debit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->debit, false) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
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
