@extends('layouts.app')

@section('title', $customer->name . ' — ' . __('sales.customer_show.profile_suffix'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.customer_show.breadcrumb_customers') }}</a></li>
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
        <p>{{ __('sales.customer_show.registered_text') }}</p>
        <div class="page-actions">
            <a href="{{ $whatsappLink }}" target="_blank" class="btn-3d btn-3d-success">
                <span aria-hidden="true">💬</span> {{ __('sales.customer_show.whatsapp_reminder') }}
            </a>
            <a href="{{ route('customers.statement', $customer) }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">📄</span> {{ __('sales.customer_show.statement') }}
            </a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">✏️</span> {{ __('ui.actions.edit') }}
            </a>
        </div>
    </div>

    {{-- ================= Info & Balances Grid ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Card 1: Balance & Credit Limit --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('sales.customer_show.current_balance') }}</div>
            <div class="tabular mt-2 text-3xl font-extrabold {{ \App\Support\Money::compare($customer->current_balance, '0.00') > 0 ? 'text-red-600 dark:text-red-500' : 'text-slate-900 dark:text-white' }}">
                {{ \App\Support\PakistaniCurrency::format($customer->current_balance) }}
            </div>
            <div class="mt-1 text-xs font-medium text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($customer->current_balance) }}
            </div>

            <div class="mt-5 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                <div class="mb-1 flex justify-between text-xs text-slate-500">
                    <span>{{ __('sales.customer_show.approved_credit_limit') }}</span>
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ $customer->creditLimitIsUnlimited() ? __('sales.customer_show.unlimited') : \App\Support\PakistaniCurrency::format($customer->credit_limit, true, 0) }}
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
                        <span>{{ __('sales.customer_show.used_label') }} {{ $pct }}%</span>
                        <span>{{ __('sales.customer_show.remaining_label') }} {{ \App\Support\PakistaniCurrency::format($customer->availableCredit(), true, 0) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Ageing Breakdown --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('sales.customer_show.ageing_breakdown') }}</div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-950/30">
                    <span class="text-slate-500">{{ __('sales.customer_show.days_0_30') }}</span>
                    <div class="tabular mt-0.5 font-bold text-emerald-800 dark:text-emerald-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['0_30'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-xl bg-blue-50 p-2.5 dark:bg-blue-950/30">
                    <span class="text-slate-500">{{ __('sales.customer_show.days_31_60') }}</span>
                    <div class="tabular mt-0.5 font-bold text-blue-800 dark:text-blue-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['31_60'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-xl bg-amber-50 p-2.5 dark:bg-amber-950/30">
                    <span class="text-slate-500">{{ __('sales.customer_show.days_61_90') }}</span>
                    <div class="tabular mt-0.5 font-bold text-amber-800 dark:text-amber-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['61_90'], true, 0) }}
                    </div>
                </div>
                <div class="rounded-xl bg-red-50 p-2.5 dark:bg-red-950/30">
                    <span class="text-slate-500">{{ __('sales.customer_show.days_90_plus') }}</span>
                    <div class="tabular mt-0.5 font-bold text-red-800 dark:text-red-300">
                        {{ \App\Support\PakistaniCurrency::format($ageing['over_90'], true, 0) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Identification & Contact --}}
        <div class="glass-card card-3d p-5">
            <div class="text-center text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('sales.customer_show.identification') }}</div>
            <dl class="mt-3 space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.customer_show.mobile_phone') }}</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ $customer->phone ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.customer_show.cnic') }}</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $customer->cnic ?? __('sales.customer_show.not_provided') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.customer_show.ntn') }}</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $customer->ntn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.customer_show.tax_liable') }}</dt>
                    <dd class="text-slate-900 dark:text-white">{{ $customer->is_tax_liable ? __('sales.customer_show.tax_yes') : __('sales.customer_show.tax_no') }}</dd>
                </div>
                <div class="pt-1 text-center">
                    <dt class="text-slate-500">{{ __('sales.customer_show.address_label') }}</dt>
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
                    <span aria-hidden="true">💵</span> {{ __('sales.customer_show.payment_heading') }}
                </h2>
                <p class="text-xs text-slate-500">{{ __('sales.customer_show.payment_subtitle') }}</p>
            </div>

            <form method="POST" action="{{ route('customers.payments.store', $customer) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                @csrf
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.payment_date') }}</label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required
                           class="input-3d text-center text-xs">
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.payment_method') }}</label>
                    <select name="payment_method" x-model="method" required
                            class="input-3d text-center text-xs">
                        <option value="CASH">{{ __('sales.customer_show.method_cash') }}</option>
                        <option value="BANK_TRANSFER">{{ __('sales.customer_show.method_bank') }}</option>
                        <option value="CHEQUE">{{ __('sales.customer_show.method_cheque') }}</option>
                    </select>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.amount_label') }}</label>
                    <input type="number" step="0.01" name="amount" required placeholder="{{ __('sales.customer_show.amount_placeholder') }}"
                           class="input-3d text-center text-xs font-bold text-slate-900 dark:text-white">
                </div>

                <div class="field-3d" x-show="method === 'BANK_TRANSFER'">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.bank_account_label') }}</label>
                    <select name="bank_account_id" class="input-3d text-center text-xs">
                        <option value="">{{ __('sales.customer_show.select_bank') }}</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name ?? 'Bank' }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-3d" x-show="method === 'CHEQUE'">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.cheque_label') }}</label>
                    <input type="text" name="cheque_number" placeholder="{{ __('sales.customer_show.cheque_placeholder') }}"
                           class="input-3d text-center font-mono text-xs">
                </div>

                <div class="field-3d sm:col-span-3">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.notes_label') }}</label>
                    <input type="text" name="notes" placeholder="{{ __('sales.customer_show.notes_placeholder') }}"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="btn-3d btn-3d-success w-full text-xs">
                        {{ __('sales.customer_show.confirm_credit') }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ================= Vehicles Section ================= --}}
    <div class="glass-card p-5">
        <div class="border-b border-slate-200/70 pb-3 text-center dark:border-slate-700/60">
            <h2 class="flex items-center justify-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                <span aria-hidden="true">🚛</span> {{ __('sales.customer_show.vehicles_heading') }}
            </h2>
            <p class="text-xs text-slate-500">{{ __('sales.customer_show.vehicles_subtitle') }}</p>
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
                                    <span>{{ __('sales.customer_show.driver_label') }} <strong class="text-slate-700 dark:text-slate-300">{{ $v->driver_name ?? 'N/A' }}</strong></span>
                                    • <span>{{ $v->make }} {{ $v->model }}</span>
                                    @if($v->tank_capacity) • <span>{{ __('sales.customer_show.capacity_label') }} {{ $v->tank_capacity }} L</span> @endif
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('customers.vehicles.destroy', [$customer, $v]) }}" onsubmit="return confirm('Remove vehicle {{ $v->registration_number }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-3d btn-3d-ghost btn-3d-sm text-red-500 hover:text-red-700">{{ __('sales.customer_show.remove') }}</button>
                        </form>
                    </div>
                @empty
                    <div class="glass-card p-6 text-center text-xs text-slate-400">
                        {{ __('sales.customer_show.no_vehicles') }}
                    </div>
                @endforelse
            </div>

            {{-- Quick Add Vehicle Form --}}
            <div class="glass-card p-4">
                <h3 class="mb-3 text-center text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ __('sales.customer_show.add_vehicle') }}</h3>
                <form method="POST" action="{{ route('customers.vehicles.store', $customer) }}" class="space-y-3">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.reg_plate_label') }}</label>
                        <input type="text" name="registration_number" required placeholder="LEA-1234"
                               class="input-3d text-center font-mono text-xs uppercase">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.driver_name_label') }}</label>
                        <input type="text" name="driver_name" placeholder="{{ __('sales.customer_show.driver_placeholder') }}"
                               class="input-3d text-center text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.vehicle_type_label') }}</label>
                            <select name="type" class="input-3d text-center text-xs">
                                <option value="TRUCK">{{ __('sales.customer_show.type_truck') }}</option>
                                <option value="CAR">{{ __('sales.customer_show.type_car') }}</option>
                                <option value="BUS">{{ __('sales.customer_show.type_bus') }}</option>
                                <option value="PICKUP">{{ __('sales.customer_show.type_pickup') }}</option>
                                <option value="TRACTOR">{{ __('sales.customer_show.type_tractor') }}</option>
                                <option value="MOTORCYCLE">{{ __('sales.customer_show.type_motorcycle') }}</option>
                            </select>
                        </div>
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.tank_cap_label') }}</label>
                            <input type="number" step="0.001" name="tank_capacity" placeholder="{{ __('sales.customer_show.tank_cap_placeholder') }}"
                                   class="input-3d text-center text-xs">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-[11px] font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.customer_show.make_model_label') }}</label>
                        <input type="text" name="make" placeholder="{{ __('sales.customer_show.make_placeholder') }}"
                               class="input-3d text-center text-xs">
                    </div>
                    <input type="hidden" name="status" value="ACTIVE">
                    <button type="submit" class="btn-3d btn-3d-navy w-full text-xs">
                        + {{ __('sales.customer_show.add_to_fleet') }}
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
                    <span aria-hidden="true">📖</span> {{ __('sales.customer_show.ledger_heading') }}
                </h2>
                <p class="text-xs text-slate-500">{{ __('sales.customer_show.ledger_subtitle') }}</p>
            </div>
            <a href="{{ route('customers.statement', $customer) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                {{ __('sales.customer_show.view_statement') }} →
            </a>
        </div>

        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>{{ __('sales.customer_show.th_date') }}</th>
                        <th>{{ __('sales.customer_show.th_description') }}</th>
                        <th>{{ __('sales.customer_show.th_debit') }}</th>
                        <th>{{ __('sales.customer_show.th_credit') }}</th>
                        <th>{{ __('sales.customer_show.th_balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerEntries as $entry)
                        <tr>
                            <td class="tabular font-mono">{{ $entry->date->format('d/m/Y') }}</td>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $entry->description }}</div>
                                @if($entry->reference_type)
                                    <div class="text-[10px] text-slate-400">{{ __('sales.customer_show.ref') }} {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</div>
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
                                {{ __('sales.customer_show.no_ledger') }}
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
