@extends('layouts.app')

@section('title', 'Invoices — Mehar Filling Station')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200 font-medium">Invoices / انوائسز</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header with Title & Quick Action --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[#D71920] text-white shadow-sm font-bold text-lg">
                    🧾
                </span>
                <div>
                    <h1 class="text-2xl font-black text-[#1B1B1B] dark:text-white tracking-tight flex items-center gap-2">
                        <span>Customer Invoices</span>
                        <span class="text-base font-normal text-slate-500 font-urdu">سیلز انوائسز و رسیدات</span>
                    </h1>
                    <p class="text-xs text-slate-500">
                        Official Vital Petroleum franchised sales receipts & tax invoices • Mehar Filling Station
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('settings.bill-designer') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 shadow-sm transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700">
                <span>🎨</span>
                <span>Bill Designer</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white rounded-lg bg-[#D71920] hover:bg-[#A30F15] shadow-sm transition">
                <span>⛽</span>
                <span>Open POS Screen</span>
            </a>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-4 bg-white rounded-xl border border-slate-200/80 shadow-sm dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>Total Invoices</span>
                <span class="text-slate-400">کل انوائسز</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalCount) }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">Bills</span>
            </div>
        </div>

        <div class="p-4 bg-white rounded-xl border border-slate-200/80 shadow-sm dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>Total Invoiced</span>
                <span class="text-slate-400 font-urdu">کل رقم</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-[#D71920]">
                    {{ \App\Support\AmountInWords::formatLakh($totalInvoiced, true, 2) }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-[#D71920] font-medium">Gross</span>
            </div>
        </div>

        <div class="p-4 bg-white rounded-xl border border-slate-200/80 shadow-sm dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>Paid Received</span>
                <span class="text-slate-400 font-urdu">موصول شدہ</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                    {{ \App\Support\AmountInWords::formatLakh($totalPaid, true, 2) }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">Settled</span>
            </div>
        </div>

        <div class="p-4 bg-white rounded-xl border border-slate-200/80 shadow-sm dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>Udhaar / Balance Due</span>
                <span class="text-slate-400 font-urdu">واجب الادا ادھار</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-amber-600 dark:text-amber-400">
                    {{ \App\Support\AmountInWords::formatLakh($totalBalanceDue, true, 2) }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Credit</span>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="p-4 bg-white rounded-xl border border-slate-200/80 shadow-sm dark:bg-slate-900 dark:border-slate-800">
        <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-6 items-end">
            {{-- Search Input --}}
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Search / تلاش</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Invoice #, Hash, Customer, Vehicle..."
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-[#D71920] focus:ring-[#D71920] dark:bg-slate-800 dark:border-slate-700 dark:text-white pl-8">
                    <span class="absolute left-2.5 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Status / کیفیت</label>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#D71920] focus:ring-[#D71920] dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="paid" @selected($status === 'paid')>Paid (ادا شدہ)</option>
                    <option value="issued" @selected($status === 'issued')>Issued / Credit (جاری)</option>
                    <option value="partially_paid" @selected($status === 'partially_paid')>Partially Paid</option>
                    <option value="void" @selected($status === 'void')>Void (منسوخ)</option>
                </select>
            </div>

            {{-- Payment Method --}}
            <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#D71920] focus:ring-[#D71920] dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                    <option value="">All Methods</option>
                    <option value="cash" @selected($paymentMethod === 'cash')>Cash (نقد)</option>
                    <option value="credit" @selected($paymentMethod === 'credit')>Credit / Udhaar (ادھار)</option>
                    <option value="card" @selected($paymentMethod === 'card')>Card (کارڈ)</option>
                    <option value="bank_transfer" @selected($paymentMethod === 'bank_transfer')>Bank Transfer</option>
                    <option value="split" @selected($paymentMethod === 'split')>Split Payment</option>
                </select>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-[#D71920] focus:ring-[#D71920] dark:bg-slate-800 dark:border-slate-700 dark:text-white">
            </div>

            {{-- Filter & Reset Buttons --}}
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 px-3 py-2 text-xs font-bold text-white bg-[#D71920] hover:bg-[#A30F15] rounded-lg transition shadow-sm">
                    Filter
                </button>
                @if ($search || $status || $paymentMethod || $fromDate || $toDate)
                    <a href="{{ route('invoices.index') }}"
                       class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition dark:bg-slate-800 dark:text-slate-300">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Invoices Table --}}
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 font-bold dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
                        <th class="py-3 px-4">Invoice # / رسید نمبر</th>
                        <th class="py-3 px-4">Date & Time / تاریخ</th>
                        <th class="py-3 px-4">Customer & Vehicle / کسٹمر</th>
                        <th class="py-3 px-4">Payment / ادائیگی</th>
                        <th class="py-3 px-4 text-right">Total Amount / کل رقم</th>
                        <th class="py-3 px-4 text-right">Paid / Balance</th>
                        <th class="py-3 px-4 text-center">Status / کیفیت</th>
                        <th class="py-3 px-4 text-center">Actions / کارروائی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse ($invoices as $inv)
                        <tr class="hover:bg-slate-50/70 transition dark:hover:bg-slate-800/40">
                            {{-- Invoice Number --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <a href="{{ route('invoices.show', $inv) }}" class="font-bold text-[#D71920] hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                                @if ($inv->hash)
                                    <div class="text-[10px] text-slate-400 font-mono">
                                        #{{ substr($inv->hash, 0, 8) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                <div>{{ $inv->invoice_date->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $inv->invoice_date->format('h:i A') }}</div>
                            </td>

                            {{-- Customer & Vehicle --}}
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $inv->customer?->name ?? 'Walk-in Customer / کیش کسٹمر' }}
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                    @if ($inv->vehicle)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                            🚗 {{ $inv->vehicle->registration_number }}
                                        </span>
                                    @endif
                                    @if ($inv->customer?->phone)
                                        <span>{{ $inv->customer->phone }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Payment Method --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="capitalize font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $inv->payment_method ?? 'Cash' }}
                                </span>
                            </td>

                            {{-- Total Amount --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap font-bold text-[#1B1B1B] dark:text-white">
                                {{ \App\Support\AmountInWords::formatLakh($inv->total_amount, true, 2) }}
                            </td>

                            {{-- Paid / Balance --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="text-emerald-600 dark:text-emerald-400 font-semibold">
                                    {{ \App\Support\AmountInWords::formatLakh($inv->paid_amount, true, 2) }}
                                </div>
                                @if ((float) $inv->balance_due > 0)
                                    <div class="text-amber-600 dark:text-amber-400 text-[11px] font-bold">
                                        Due: {{ \App\Support\AmountInWords::formatLakh($inv->balance_due, true, 2) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if ($inv->status === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        ✓ Paid
                                    </span>
                                @elseif ($inv->status === 'issued')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Udhaar / Issued
                                    </span>
                                @elseif ($inv->status === 'void')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        Void
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('invoices.show', $inv) }}"
                                       title="View Details"
                                       class="p-1.5 rounded-md hover:bg-slate-100 text-slate-600 dark:hover:bg-slate-800 dark:text-slate-300 transition">
                                        👁️
                                    </a>
                                    <a href="{{ route('invoices.a4', $inv) }}" target="_blank"
                                       title="Print A4 Modern Red Band"
                                       class="p-1.5 rounded-md hover:bg-red-50 text-[#D71920] transition">
                                        📄
                                    </a>
                                    <a href="{{ route('invoices.thermal', $inv) }}" target="_blank"
                                       title="Print Thermal 80mm Receipt"
                                       class="p-1.5 rounded-md hover:bg-slate-100 text-slate-700 dark:hover:bg-slate-800 dark:text-slate-300 transition">
                                        🧾
                                    </a>
                                    <a href="{{ route('invoices.pdf', $inv) }}"
                                       title="Download PDF"
                                       class="p-1.5 rounded-md hover:bg-slate-100 text-slate-700 dark:hover:bg-slate-800 dark:text-slate-300 transition">
                                        ⬇️
                                    </a>
                                    @if ($inv->hash)
                                        <a href="{{ route('invoice.verify', $inv->hash) }}" target="_blank"
                                           title="Public QR Verification"
                                           class="p-1.5 rounded-md hover:bg-emerald-50 text-emerald-600 transition">
                                            🔍
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">🧾</div>
                                <div class="font-bold text-slate-700 dark:text-slate-300">No invoices found</div>
                                <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or date filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
