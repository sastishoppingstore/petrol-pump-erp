@extends('layouts.app')

@section('title', $customer->name . ' — Customer Profile')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Customers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $customer->code }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ $customer->name }}</h1>
        <p>
            <span class="inline-block rounded-md bg-red-100 px-2 py-0.5 align-middle font-mono text-xs font-bold text-red-800 dark:bg-red-950/60 dark:text-red-300">{{ $customer->code }}</span>
            <span class="pill-status {{ $customer->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }} align-middle">
                <span class="dot" aria-hidden="true"></span>{{ $customer->status }}
            </span>
        </p>
        <p>Registered account at Mehar Filling Station (Vital Petroleum franchise), Sheikhupura.</p>
        <div class="page-actions">
            <a href="{{ $whatsappLink }}" target="_blank" class="btn-3d btn-3d-success">
                <span aria-hidden="true">💬</span> WhatsApp Reminder
            </a>
            <a href="{{ route('customers.statement', $customer) }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">📄</span> Statement
            </a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">✏️</span> Edit
            </a>
        </div>
    </div>

    {{-- ================= Info & Balances Grid ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Card 1: Balance & Credit Limit --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Outstanding Balance</div>
            <div class="tabular mt-2 text-3xl font-extrabold {{ \App\Support\Money::compare($customer->current_balance, '0.00') > 0 ? 'text-red-600 dark:text-red-500' : 'text-slate-900 dark:text-white' }}">
                {{ \App\Support\PakistaniCurrency::format($customer->current_balance) }}
            </div>
            <div class="mt-1 text-xs font-medium text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($customer->current_balance) }}
            </div>

            <div class="mt-5 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                <div class="mb-1 flex justify-between text-xs text-slate-500">
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
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full {{ $pct > 90 ? 'bg-red-600' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="mt-1 flex justify-between text-[11px] text-slate-400">
                        <span>Used: {{ $pct }}%</span>
                        <span>Remaining: {{ \App\Support\PakistaniCurrency::format($customer->availableCredit(), true, 0) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Ageing Breakdown --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Udhaar Ageing Breakdown (FIFO)</div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-950/30">
                    <span class="text-slate-500">0 - 30 Days</span>
                    <div class="tabular mt-0.5 font-bold text-emerald-800 dark:text-emerald-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['0_30'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-xl bg-blue-50 p-2.5 dark:bg-blue-950/30">
                    <span class="text-slate-500">31 - 60 Days</span>
                    <div class="tabular mt-0.5 font-bold text-blue-800 dark:text-blue-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['31_60'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-xl bg-amber-50 p-2.5 dark:bg-amber-950/30">
                    <span class="text-slate-500">61 - 90 Days</span>
                    <div class="tabular mt-0.5 font-bold text-amber-800 dark:text-amber-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['61_90'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-xl bg-red-50 p-2.5 dark:bg-red-950/30">
                    <span class="text-slate-500">90+ Days (Overdue)</span>
                    <div class="tabular mt-0.5 font-bold text-red-800 dark:text-red-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['over_90'], true, 0) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Identification & Contact --}}
        <div class="glass-card card-3d p-5">
            <div class="text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Customer Identification</div>
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
                <div class="pt-1 text-center">
                    <dt class="text-slate-500">Address:</dt>
                    <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $customer->address ?? 'Sheikhupura' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ================= Payment Collection Form ================= --}}
    @if(auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_PAYMENT))
        <div class="glass-card p-5" x-data="{ method: 'CASH' }">
            <div class="border-b border-slate-200/70 pb-3 text-center dark:border-slate-700/60">
                <h2 class="flex items-center justify-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                    <span aria-hidden="true">💵</span> Receive Customer Payment (Udhaar Wusooli)
                </h2>
                <p class="text-xs text-slate-500">Record payments received via Cash, Bank Transfer, or Cheque to credit customer ledger.</p>
            </div>

            <form method="POST" action="{{ route('customers.payments.store', $customer) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                @csrf
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Payment Date *</label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required
                           class="input-3d text-center text-xs">
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Payment Method *</label>
                    <select name="payment_method" x-model="method" required
                            class="input-3d text-center text-xs">
                        <option value="CASH">Cash (Cash Desk Entry)</option>
                        <option value="BANK_TRANSFER">Bank Online Transfer</option>
                        <option value="CHEQUE">Cheque</option>
                    </select>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Amount (Rs.) *</label>
                    <input type="number" step="0.01" name="amount" required placeholder="e.g. 50000"
                           class="input-3d text-center text-xs font-bold text-slate-900 dark:text-white">
                </div>

                <div class="field-3d" x-show="method === 'BANK_TRANSFER'">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Station Bank Account *</label>
                    <select name="bank_account_id" class="input-3d text-center text-xs">
                        <option value="">Select Bank Account</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name ?? 'Bank' }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-3d" x-show="method === 'CHEQUE'">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Cheque Number *</label>
                    <input type="text" name="cheque_number" placeholder="Cheque #"
                           class="input-3d text-center font-mono text-xs">
                </div>

                <div class="field-3d sm:col-span-3">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Notes / Remarks</label>
                    <input type="text" name="notes" placeholder="e.g. Received from Manager Tariq for Truck fleet"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="btn-3d btn-3d-success w-full text-xs">
                        Confirm &amp; Credit Ledger
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ================= Vehicles Section ================= --}}
    <div class="glass-card p-5">
        <div class="border-b border-slate-200/70 pb-3 text-center dark:border-slate-700/60">
            <h2 class="flex items-center justify-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                <span aria-hidden="true">🚛</span> Registered Customer Vehicles (Fleet)
            </h2>
            <p class="text-xs text-slate-500">Vehicles authorized to fill fuel against this credit line.</p>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Vehicle List --}}
            <div class="space-y-3 lg:col-span-2">
                @forelse($customer->vehicles as $v)
                    <div class="glass-card flex flex-col items-center justify-between gap-3 p-3 text-center sm:flex-row sm:text-left">
                        <div class="flex flex-col items-center gap-3 sm:flex-row">
                            <div class="rounded-xl bg-slate-100 p-2 text-xl dark:bg-slate-800" aria-hidden="true">
                                @if($v->type === 'TRUCK') 🚚 @elseif($v->type === 'BUS') 🚌 @elseif($v->type === 'TRACTOR') 🚜 @else 🚗 @endif
                            </div>
                            <div>
                                <div class="font-mono text-sm font-bold text-slate-900 dark:text-white">
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
                            <button type="submit" class="btn-3d btn-3d-ghost btn-3d-sm text-red-500 hover:text-red-700">Remove</button>
                        </form>
                    </div>
                @empty
                    <div class="glass-card p-6 text-center text-xs text-slate-400">
                        No vehicles registered yet. Register vehicles below for pump attendant verification.
                    </div>
                @endforelse
            </div>

            {{-- Quick Add Vehicle Form --}}
            <div class="glass-card p-4">
                <h3 class="mb-3 text-center text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Add Vehicle</h3>
                <form method="POST" action="{{ route('customers.vehicles.store', $customer) }}" class="space-y-3">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">Reg Plate (e.g. LEA-1234) *</label>
                        <input type="text" name="registration_number" required placeholder="LEA-1234"
                               class="input-3d text-center font-mono text-xs uppercase">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">Driver Name</label>
                        <input type="text" name="driver_name" placeholder="e.g. Muhammad Tariq"
                               class="input-3d text-center text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">Vehicle Type</label>
                            <select name="type" class="input-3d text-center text-xs">
                                <option value="TRUCK">Truck</option>
                                <option value="CAR">Car</option>
                                <option value="BUS">Bus</option>
                                <option value="PICKUP">Pickup</option>
                                <option value="TRACTOR">Tractor</option>
                                <option value="MOTORCYCLE">Motorcycle</option>
                            </select>
                        </div>
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">Tank Cap (L)</label>
                            <input type="number" step="0.001" name="tank_capacity" placeholder="e.g. 300"
                                   class="input-3d text-center text-xs">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">Make &amp; Model</label>
                        <input type="text" name="make" placeholder="e.g. Bedford Rocket / Hino 500"
                               class="input-3d text-center text-xs">
                    </div>
                    <input type="hidden" name="status" value="ACTIVE">
                    <button type="submit" class="btn-3d btn-3d-navy w-full text-xs">
                        + Add to Fleet
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================= Append-Only Customer Ledger ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="flex flex-col items-center justify-between gap-2 border-b border-slate-200/70 p-5 text-center dark:border-slate-700/60 sm:flex-row sm:text-left">
            <div>
                <h2 class="flex items-center justify-center gap-2 text-base font-bold text-slate-900 dark:text-white sm:justify-start">
                    <span aria-hidden="true">📖</span> Append-Only Customer Ledger
                </h2>
                <p class="text-xs text-slate-500">Immutable chronological transaction log with running balance.</p>
            </div>
            <a href="{{ route('customers.statement', $customer) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                View Full Printable Statement →
            </a>
        </div>

        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Debit (Udhaar)</th>
                        <th>Credit (Wusooli)</th>
                        <th>Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerEntries as $entry)
                        <tr>
                            <td class="tabular font-mono">{{ $entry->date->format('d/m/Y') }}</td>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $entry->description }}</div>
                                @if($entry->reference_type)
                                    <div class="text-[10px] text-slate-400">Ref: {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</div>
                                @endif
                            </td>
                            <td class="tabular font-medium text-red-600 dark:text-red-400">
                                {{ \App\Support\Money::compare($entry->debit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->debit, false) : '-' }}
                            </td>
                            <td class="tabular font-medium text-emerald-600 dark:text-emerald-400">
                                {{ \App\Support\Money::compare($entry->credit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->credit, false) : '-' }}
                            </td>
                            <td class="tabular font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($entry->running_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-slate-500">
                                No ledger transactions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ledgerEntries->hasPages())
            <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
                {{ $ledgerEntries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
