@extends('layouts.app')

@section('title', 'Invoices — Mehar Filling Station')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <span class="font-medium text-slate-800 dark:text-slate-200">Invoices / انوائسز</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>🧾 Customer Invoices <span class="font-urdu text-base font-normal text-slate-500">سیلز انوائسز و رسیدات</span></h1>
        <p>Official Vital Petroleum franchised sales receipts &amp; tax invoices • Mehar Filling Station</p>
        <div class="page-actions">
            <a href="{{ route('settings.bill-designer') }}" class="btn-3d btn-3d-ghost">
                <span>🎨</span>
                <span>Bill Designer</span>
            </a>
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-primary">
                <span>⛽</span>
                <span>Open POS Screen</span>
            </a>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">Total Invoices <span class="normal-case">• کل انوائسز</span></div>
            <div class="stat-value tabular kpi-num">{{ number_format($totalCount) }}</div>
            <div class="stat-sub">Bills</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">Total Invoiced <span class="normal-case">• کل رقم</span></div>
            <div class="stat-value tabular">{{ \App\Support\AmountInWords::formatLakh($totalInvoiced, true, 2) }}</div>
            <div class="stat-sub">Gross</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">Paid Received <span class="normal-case">• موصول شدہ</span></div>
            <div class="stat-value tabular">{{ \App\Support\AmountInWords::formatLakh($totalPaid, true, 2) }}</div>
            <div class="stat-sub">Settled</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">Udhaar / Balance Due <span class="normal-case">• واجب الادا ادھار</span></div>
            <div class="stat-value tabular">{{ \App\Support\AmountInWords::formatLakh($totalBalanceDue, true, 2) }}</div>
            <div class="stat-sub">Credit</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 md:grid-cols-6">
            {{-- Search Input --}}
            <div class="field-3d md:col-span-2">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">Search / تلاش</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Invoice #, Hash, Customer, Vehicle..."
                           class="input-3d w-full pl-8 text-center text-xs">
                    <span class="absolute left-2.5 top-3 text-xs text-slate-400">🔍</span>
                </div>
            </div>

            {{-- Status Filter --}}
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">Status / کیفیت</label>
                <select name="status" class="input-3d w-full text-center text-xs">
                    <option value="">All Statuses</option>
                    <option value="paid" @selected($status === 'paid')>Paid (ادا شدہ)</option>
                    <option value="issued" @selected($status === 'issued')>Issued / Credit (جاری)</option>
                    <option value="partially_paid" @selected($status === 'partially_paid')>Partially Paid</option>
                    <option value="void" @selected($status === 'void')>Void (منسوخ)</option>
                </select>
            </div>

            {{-- Payment Method --}}
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">Payment Method</label>
                <select name="payment_method" class="input-3d w-full text-center text-xs">
                    <option value="">All Methods</option>
                    <option value="cash" @selected($paymentMethod === 'cash')>Cash (نقد)</option>
                    <option value="credit" @selected($paymentMethod === 'credit')>Credit / Udhaar (ادھار)</option>
                    <option value="card" @selected($paymentMethod === 'card')>Card (کارڈ)</option>
                    <option value="bank_transfer" @selected($paymentMethod === 'bank_transfer')>Bank Transfer</option>
                    <option value="split" @selected($paymentMethod === 'split')>Split Payment</option>
                </select>
            </div>

            {{-- From Date --}}
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-medium text-slate-700 dark:text-slate-300">From Date</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                       class="input-3d w-full text-center text-xs">
            </div>

            {{-- Filter & Reset Buttons --}}
            <div class="flex items-center justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm flex-1">
                    Filter
                </button>
                @if ($search || $status || $paymentMethod || $fromDate || $toDate)
                    <a href="{{ route('invoices.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        Reset
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
                        <th>Invoice # / رسید نمبر</th>
                        <th>Date &amp; Time / تاریخ</th>
                        <th>Customer &amp; Vehicle / کسٹمر</th>
                        <th>Payment / ادائیگی</th>
                        <th>Total Amount / کل رقم</th>
                        <th>Paid / Balance</th>
                        <th>Status / کیفیت</th>
                        <th>Actions / کارروائی</th>
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
                                        Due: {{ \App\Support\AmountInWords::formatLakh($inv->balance_due, true, 2) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="whitespace-nowrap">
                                @if ($inv->status === 'paid')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        ✓ Paid
                                    </span>
                                @elseif ($inv->status === 'issued')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Udhaar / Issued
                                    </span>
                                @elseif ($inv->status === 'void')
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        Void
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
                                       title="View Details"
                                       class="rounded-lg p-1.5 text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        👁️
                                    </a>
                                    <a href="{{ route('invoices.a4', $inv) }}" target="_blank"
                                       title="Print A4 Modern Red Band"
                                       class="rounded-lg p-1.5 text-vital-primary transition hover:bg-red-50">
                                        📄
                                    </a>
                                    <a href="{{ route('invoices.thermal', $inv) }}" target="_blank"
                                       title="Print Thermal 80mm Receipt"
                                       class="rounded-lg p-1.5 text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        🧾
                                    </a>
                                    <a href="{{ route('invoices.pdf', $inv) }}"
                                       title="Download PDF"
                                       class="rounded-lg p-1.5 text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        ⬇️
                                    </a>
                                    @if ($inv->hash)
                                        <a href="{{ route('invoice.verify', $inv->hash) }}" target="_blank"
                                           title="Public QR Verification"
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
                                <div class="font-bold text-slate-700 dark:text-slate-300">No invoices found</div>
                                <p class="mt-1 text-xs text-slate-400">Try adjusting your search criteria or date filters.</p>
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

    <a href="{{ route('pos.index') }}" class="fab-3d" title="Open POS screen to create an invoice">
        <span aria-hidden="true">⛽</span> New Sale
    </a>
</div>
@endsection
