@extends('layouts.app')

@section('title', 'Udhaar Ageing Report')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Customers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Udhaar Ageing</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span>⏱️</span> Customer Udhaar Ageing Report
            </h1>
            <p class="text-sm text-slate-500">
                Overdue receivables analysed using First-In-First-Out (FIFO) allocation with click-to-send WhatsApp payment reminders.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                🖨️ Print Report
            </button>
            <a href="{{ route('customers.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                ← Back to Customers
            </a>
        </div>
    </div>

    {{-- Bucket KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">0 - 30 Days</div>
            <div class="mt-2 text-xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ \App\Support\PakistaniCurrency::format($totals['0_30'], true, 0) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Current cycle</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">31 - 60 Days</div>
            <div class="mt-2 text-xl font-bold text-blue-600 dark:text-blue-400">
                {{ \App\Support\PakistaniCurrency::format($totals['31_60'], true, 0) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Follow up required</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">61 - 90 Days</div>
            <div class="mt-2 text-xl font-bold text-amber-600 dark:text-amber-400">
                {{ \App\Support\PakistaniCurrency::format($totals['61_90'], true, 0) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Credit warning</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">90+ Days</div>
            <div class="mt-2 text-xl font-bold text-red-600 dark:text-red-500">
                {{ \App\Support\PakistaniCurrency::format($totals['over_90'], true, 0) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Overdue / Hold</div>
        </div>

        <div class="rounded-xl border-2 border-red-500 bg-red-50/40 p-4 shadow-sm dark:border-red-600 dark:bg-red-950/20">
            <div class="text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-400">Total Outstanding</div>
            <div class="mt-2 text-xl font-extrabold text-red-700 dark:text-red-400">
                {{ \App\Support\PakistaniCurrency::format($totals['total'], true, 0) }}
            </div>
            <div class="text-[10px] text-red-600 dark:text-red-400 mt-0.5">{{ count($rows) }} debtors</div>
        </div>
    </div>

    {{-- Ageing Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Customer Code & Name</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3 text-right text-emerald-700 dark:text-emerald-400">0 - 30 D</th>
                        <th class="px-5 py-3 text-right text-blue-700 dark:text-blue-400">31 - 60 D</th>
                        <th class="px-5 py-3 text-right text-amber-700 dark:text-amber-400">61 - 90 D</th>
                        <th class="px-5 py-3 text-right text-red-700 dark:text-red-400">90+ D</th>
                        <th class="px-5 py-3 text-right font-black">Total Udhaar</th>
                        <th class="px-5 py-3 text-center no-print">Reminder</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($rows as $row)
                        @php $c = $row['customer']; $a = $row['ageing']; @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3">
                                <a href="{{ route('customers.show', $c) }}" class="font-bold text-slate-900 hover:text-red-600 dark:text-white dark:hover:text-red-400">
                                    {{ $c->name }}
                                </a>
                                <div class="text-[11px] font-mono text-slate-400">{{ $c->code }}</div>
                            </td>
                            <td class="px-5 py-3 font-mono">
                                {{ $c->phone ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                {{ \App\Support\Money::compare($a['0_30'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['0_30'], false, 0) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-blue-600 dark:text-blue-400">
                                {{ \App\Support\Money::compare($a['31_60'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['31_60'], false, 0) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-amber-600 dark:text-amber-400">
                                {{ \App\Support\Money::compare($a['61_90'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['61_90'], false, 0) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-red-600 dark:text-red-400">
                                {{ \App\Support\Money::compare($a['over_90'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['over_90'], false, 0) : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($a['total'], true, 0) }}
                            </td>
                            <td class="px-5 py-3 text-center no-print">
                                <a href="{{ $row['whatsapp_link'] }}" target="_blank"
                                   class="inline-flex items-center gap-1 rounded bg-emerald-500 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-600 transition shadow-sm"
                                   title="Send WhatsApp payment reminder to {{ $c->phone }}">
                                    <span>💬</span> wa.me
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                No outstanding customer balances found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-slate-300 bg-slate-100/70 font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <td class="px-5 py-3 uppercase" colspan="2">Consolidated Totals</td>
                        <td class="px-5 py-3 text-right font-mono text-emerald-600">{{ \App\Support\PakistaniCurrency::format($totals['0_30'], false, 0) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-blue-600">{{ \App\Support\PakistaniCurrency::format($totals['31_60'], false, 0) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-amber-600">{{ \App\Support\PakistaniCurrency::format($totals['61_90'], false, 0) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-red-600">{{ \App\Support\PakistaniCurrency::format($totals['over_90'], false, 0) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-900 dark:text-white">{{ \App\Support\PakistaniCurrency::format($totals['total'], true, 0) }}</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
