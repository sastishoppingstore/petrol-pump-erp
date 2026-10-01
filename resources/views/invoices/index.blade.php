@extends('layouts.app')

@section('title', {{ __('sales.invoices.title') }})

@section('breadcrumb')
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <span class="font-medium text-slate-800 dark:text-slate-200">{{ __('sales.invoices.breadcrumb') }}</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>🧾 {{ __('sales.invoices.heading') }} <span class="font-urdu text-base font-normal text-slate-500">{{ __('sales.invoices.heading_ur') }}</span></h1>
        <p>{{ __('sales.invoices.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('settings.bill-designer') }}" class="btn-3d btn-3d-ghost">
                <span>🎨</span>
                <span>{{ __('sales.invoices.bill_designer') }}</span>
            </a>
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-primary">
                <span>⛽</span>
                <span>{{ __('sales.invoices.open_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('sales.invoices.total_invoices') }} <span class="normal-case">• {{ __('sales.invoices.total_invoices_ur') }}</span></div>
            <div class="stat-value tabular kpi-num">{{ number_format($totalCount) }}</div>
            <div class="stat-sub">{{ __('sales.invoices.bills') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.invoices.total_invoiced') }} <span class="normal-case">• {{ __('sales.invoices.total_invoiced_ur') }}</span></div>
            <div class="stat-value tabular">{{ \App\Support\AmountInWords::formatLakh($totalInvoiced, true, 2) }}</div>
            <div class="stat-sub">{{ __('sales.invoices.gross') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('sales.invoices.paid_received') }} <span class="normal-case">• {{ __('sales.invoices.paid_received_ur') }}</span></div>
            <div class="stat-value tabular">{{ \App\Support\AmountInWords::formatLakh($totalPaid, true, 2) }}</div>
            <div class="stat-sub">{{ __('sales.invoices.settled') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('sales.invoices.balance_due_label') }} <span class="normal-case">• {{ __('sales.invoices.balance_due_ur') }}</span></div>
            <div class="stat-value tabular">{{ \App\Support\AmountInWords::formatLakh($totalBalanceDue, true, 2) }}</div>
            <div class="stat-sub">{{ __('sales.invoices.credit') }}</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 md:grid-cols-6">
            {{-- Search Input --}}
            <div class="field-3d md:col-span-2">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">{{ __('sales.invoices.search_label') }}</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="{{ __('sales.invoices.search_placeholder') }}"
                           class="input-3d w-full pl-8 text-center text-xs">
                    <span class="absolute left-2.5 top-3 text-xs text-slate-400">🔍</span>
                </div>
            </div>

            {{-- Status Filter --}}
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">{{ __('sales.invoices.status_label') }}</label>
                <select name="status" class="input-3d w-full text-center text-xs">
                    <option value="">{{ __('sales.invoices.all_statuses') }}</option>
                    <option value="paid" @selected($status === 'paid')>{{ __('sales.invoices.paid') }}</option>
                    <option value="issued" @selected($status === 'issued')>{{ __('sales.invoices.issued_credit') }}</option>
                    <option value="partially_paid" @selected($status === 'partially_paid')>{{ __('sales.invoices.partially_paid') }}</option>
                    <option value="void" @selected($status === 'void')>{{ __('sales.invoices.void') }}</option>
                </select>
            </div>

            {{-- Payment Method --}}
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">{{ __('sales.invoices.payment_method') }}</label>
                <select name="payment_method" class="input-3d w-full text-center text-xs">
                    <option value="">{{ __('sales.invoices.all_methods') }}</option>
                    <option value="cash" @selected($paymentMethod === 'cash')>{{ __('sales.invoices.cash') }}</option>
                    <option value="credit" @selected($paymentMethod === 'credit')>{{ __('sales.invoices.credit_option') }}</option>
                    <option value="card" @selected($paymentMethod === 'card')>{{ __('sales.invoices.card') }}</option>
                    <option value="bank_transfer" @selected($paymentMethod === 'bank_transfer')>{{ __('sales.invoices.bank_transfer') }}</option>
                    <option value="split" @selected($paymentMethod === 'split')>{{ __('sales.invoices.split_payment') }}</option>
                </select>
            </div>

            {{-- From Date --}}
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">{{ __('sales.invoices.from_date') }}</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                       class="input-3d w-full text-center text-xs">
            </div>

            {{-- Filter & Reset Buttons --}}
            <div class="flex items-center justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm flex-1">
                    {{ __('ui.actions.filter') }}
                </button>
                @if ($search || $status || $paymentMethod || $fromDate || $toDate)
                    <a href="{{ route('invoices.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        {{ __('sales.invoices.reset') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Invoices Table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>{{ __('sales.invoices.invoice_no') }}</th>
                        <th>{{ __('sales.invoices.date_time') }}</th>
                        <th>{{ __('sales.invoices.customer_vehicle') }}</th>
                        <th>{{ __('sales.invoices.payment') }}</th>
                        <th>{{ __('sales.invoices.total_amount') }}</th>
                        <th>{{ __('sales.invoices.paid_balance') }}</th>
                        <th>{{ __('sales.invoices.status_label') }}</th>
                        <th>{{ __('sales.invoices.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="font-medium">
                    @forelse ($invoices as $inv)
                        <tr>
                            {{-- Invoice Number --}}
                            <td class="whitespace-nowrap">
                                <a href="{{ route('invoices.show', $inv) }}" class="tabular font-bold text-vital-primary hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                                @if ($inv->hash)
                                    <div class="font-mono text-[10px] text-slate-400">
                                        #{{ substr($inv->hash, 0, 8) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                                <div>{{ $inv->invoice_date->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $inv->invoice_date->format('h:i A') }}</div>
                            </td>

                            {{-- Customer & Vehicle --}}
                            <td>
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $inv->customer?->name ?? 'Walk-in Customer / کیش کسٹمر' }}
                                </div>
                                <div class="flex items-center justify-center gap-2 text-[11px] text-slate-500">
                                    @if ($inv->vehicle)
                                        <span class="inline-flex items-center gap-0.5 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            🚗 {{ $inv->vehicle->registration_number }}
                                        </span>
                                    @endif
                                    @if ($inv->customer?->phone)
                                        <span>{{ $inv->customer->phone }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Payment Method --}}
                            <td class="whitespace-nowrap">
                                <span class="font-semibold capitalize text-slate-700 dark:text-slate-300">
                                    {{ $inv->payment_method ?? 'Cash' }}
                                </span>
                            </td>

                            {{-- Total Amount --}}
                            <td class="tabular whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\AmountInWords::formatLakh($inv->total_amount, true, 2) }}
                            </td>

                            {{-- Paid / Balance --}}
                            <td class="tabular whitespace-nowrap">
                                <div class="font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ \App\Support\AmountInWords::formatLakh($inv->paid_amount, true, 2) }}
                                </div>
                                @if ((float) $inv->balance_due > 0)
                                    <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                        {{ __('sales.invoices.due') }}: {{ \App\Support\AmountInWords::formatLakh($inv->balance_due, true, 2) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="whitespace-nowrap">
                                @if ($inv->status === 'paid')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        ✓ {{ __('sales.invoices.paid') }}
                                    </span>
                                @elseif ($inv->status === 'issued')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        {{ __('sales.invoices.issued_badge') }}
                                    </span>
                                @elseif ($inv->status === 'void')
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        {{ __('sales.invoices.void') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('invoices.show', $inv) }}"
                                       title="{{ __('sales.invoices.view_details') }}"
                                       class="rounded-lg p-1.5 text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        👁️
                                    </a>
                                    <a href="{{ route('invoices.a4', $inv) }}" target="_blank"
                                       title="{{ __('sales.invoices.print_a4_title') }}"
                                       class="rounded-lg p-1.5 text-vital-primary transition hover:bg-red-50">
                                        📄
                                    </a>
                                    <a href="{{ route('invoices.thermal', $inv) }}" target="_blank"
                                       title="{{ __('sales.invoices.print_thermal_title') }}"
                                       class="rounded-lg p-1.5 text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        🧾
                                    </a>
                                    <a href="{{ route('invoices.pdf', $inv) }}"
                                       title="{{ __('sales.invoices.download_pdf_title') }}"
                                       class="rounded-lg p-1.5 text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        ⬇️
                                    </a>
                                    @if ($inv->hash)
                                        <a href="{{ route('invoice.verify', $inv->hash) }}" target="_blank"
                                           title="{{ __('sales.invoices.qr_verify_title') }}"
                                           class="rounded-lg p-1.5 text-emerald-600 transition hover:bg-emerald-50">
                                            🔍
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="mb-2 text-3xl">🧾</div>
                                <div class="font-bold text-slate-700 dark:text-slate-300">{{ __('sales.invoices.no_invoices') }}</div>
                                <p class="mt-1 text-xs text-slate-400">{{ __('sales.invoices.empty_hint') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <a href="{{ route('pos.index') }}" class="fab-3d" title="{{ __('sales.invoices.fab_title') }}">
        <span aria-hidden="true">⛽</span> {{ __('sales.invoices.new_sale') }}
    </a>
</div>
@endsection
