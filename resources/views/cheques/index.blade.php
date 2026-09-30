@extends('layouts.app')

@section('title', 'Cheque Register / چیک رجسٹر')
@section('breadcrumb')
    <li class="text-slate-500">Cheque Register</li>
@endsection

@section('content')
<div x-data="{ bounceModal: false, depositModal: false, activeChequeId: null, activeChequeNo: '' }" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Cheque Register / چیک رجسٹر</h1>
                <span class="rounded bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300">
                    PDC &amp; Clearing
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Track post-dated &amp; current cheques received from customers and issued to fuel suppliers.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-800 dark:bg-slate-800">
                <a href="{{ route('cheques.index', ['view_mode' => 'list']) }}" class="rounded-md px-3 py-1.5 text-xs font-semibold {{ $view_mode !== 'calendar' ? 'bg-white text-slate-900 shadow-xs dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    List View
                </a>
                <a href="{{ route('cheques.index', ['view_mode' => 'calendar']) }}" class="rounded-md px-3 py-1.5 text-xs font-semibold {{ $view_mode === 'calendar' ? 'bg-white text-slate-900 shadow-xs dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    📅 PDC Calendar
                </a>
            </div>

            <a href="{{ route('cheques.create', ['type' => 'RECEIVED']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Receive Customer Cheque
            </a>
            <a href="{{ route('cheques.create', ['type' => 'ISSUED']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-navy-800 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-navy-900 dark:bg-navy-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Issue Supplier Cheque
            </a>
        </div>
    </div>

    {{-- Top Metrics Bar --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Customer Cheques</span>
            <div class="mt-1 font-mono text-lg font-bold text-emerald-600">
                Rs. {{ number_format((float) ($stats['total_received_amount'] ?? 0), 2) }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Total received to date</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Supplier Cheques</span>
            <div class="mt-1 font-mono text-lg font-bold text-slate-900 dark:text-white">
                Rs. {{ number_format((float) ($stats['total_issued_amount'] ?? 0), 2) }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Issued for fuel indents</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-sky-600">Active PDCs</span>
            <div class="mt-1 font-mono text-lg font-bold text-sky-600">
                {{ $stats['pdc_count'] ?? 0 }} <span class="text-xs font-normal">({{ number_format((float) ($stats['pdc_amount'] ?? 0), 0) }})</span>
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Due in future</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-emerald-600">Cleared Cheques</span>
            <div class="mt-1 font-mono text-lg font-bold text-emerald-700">
                Rs. {{ number_format((float) ($stats['cleared_amount'] ?? 0), 2) }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Settled in bank ledger</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-red-600">Bounced Cheques</span>
            <div class="mt-1 font-mono text-lg font-bold text-red-600">
                {{ $stats['bounced_count'] ?? 0 }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Reversed with penalties</div>
        </div>
    </div>

    @if ($view_mode === 'calendar')
        {{-- PDC Calendar View --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        PDC Schedule for {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                    </h2>
                    <span class="rounded bg-sky-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                        Total Due: Rs. {{ number_format((float) $total_amount, 2) }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    @php
                        $prevMonth = $month == 1 ? 12 : $month - 1;
                        $prevYear = $month == 1 ? $year - 1 : $year;
                        $nextMonth = $month == 12 ? 1 : $month + 1;
                        $nextYear = $month == 12 ? $year + 1 : $year;
                    @endphp
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => $prevMonth, 'year' => $prevYear]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium hover:bg-slate-50 dark:border-slate-700">
                        &larr; Prev Month
                    </a>
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => date('n'), 'year' => date('Y')]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium hover:bg-slate-50 dark:border-slate-700">
                        Current Month
                    </a>
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => $nextMonth, 'year' => $nextYear]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium hover:bg-slate-50 dark:border-slate-700">
                        Next Month &rarr;
                    </a>
                </div>
            </div>

            <div class="space-y-4">
                @if (empty($grouped_by_date))
                    <div class="rounded-lg border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500">
                        No cheques scheduled for clearance in {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}.
                    </div>
                @else
                    @foreach ($grouped_by_date as $date => $dateCheques)
                        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                                    <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                                    @if (\Carbon\Carbon::parse($date)->isToday())
                                        <span class="rounded bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">TODAY</span>
                                    @endif
                                </div>
                                <span class="font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">
                                    {{ count($dateCheques) }} cheque(s) &bull; Rs. {{ number_format(collect($dateCheques)->sum('amount'), 2) }}
                                </span>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($dateCheques as $chq)
                                    <div class="rounded-lg border border-white bg-white p-3.5 shadow-xs dark:border-slate-700 dark:bg-slate-900">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <span class="font-mono text-xs font-bold text-slate-900 dark:text-white">#{{ $chq->cheque_number }}</span>
                                                <div class="text-xs text-slate-500">{{ $chq->bank_name }}</div>
                                            </div>
                                            <span @class([
                                                'rounded px-2 py-0.5 text-[11px] font-semibold',
                                                'bg-amber-100 text-amber-800' => $chq->status === 'RECEIVED',
                                                'bg-sky-100 text-sky-800' => $chq->status === 'DEPOSITED',
                                                'bg-emerald-100 text-emerald-800' => $chq->status === 'CLEARED',
                                                'bg-red-100 text-red-800' => $chq->status === 'BOUNCED',
                                            ])>{{ $chq->status }}</span>
                                        </div>
                                        <div class="mt-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                                            {{ $chq->partyName() }}
                                        </div>
                                        <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-800">
                                            <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">
                                                Rs. {{ number_format((float) $chq->amount, 2) }}
                                            </span>
                                            <a href="{{ route('cheques.show', $chq) }}" class="text-xs font-semibold text-red-600 hover:underline">
                                                View &rarr;
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @else
        {{-- List View with Filter --}}
        <form method="GET" action="{{ route('cheques.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <input type="hidden" name="view_mode" value="list">
            <div class="grid gap-3 sm:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Cheque Type</label>
                    <select name="type" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <option value="">All (Received &amp; Issued)</option>
                        <option value="RECEIVED" @selected(request('type') === 'RECEIVED')>Received (From Customer)</option>
                        <option value="ISSUED" @selected(request('type') === 'ISSUED')>Issued (To Supplier)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Status</label>
                    <select name="status" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <option value="">All Statuses</option>
                        <option value="RECEIVED" @selected(request('status') === 'RECEIVED')>In Hand (Received)</option>
                        <option value="DEPOSITED" @selected(request('status') === 'DEPOSITED')>Deposited in Bank</option>
                        <option value="CLEARED" @selected(request('status') === 'CLEARED')>Cleared / Settled</option>
                        <option value="BOUNCED" @selected(request('status') === 'BOUNCED')>Bounced</option>
                        <option value="CANCELLED" @selected(request('status') === 'CANCELLED')>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Search Cheque # / Party</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. 004921, Bilal Motors" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Due Date Range</label>
                    <input type="date" name="due_from" value="{{ request('due_from') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900">Filter</button>
                    <a href="{{ route('cheques.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Reset</a>
                </div>
            </div>
        </form>

        {{-- Cheques Table --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Cheque #</th>
                            <th class="px-4 py-3">Party (کسٹمر / سپلائر)</th>
                            <th class="px-4 py-3">Bank Name</th>
                            <th class="px-4 py-3 text-right">Amount (رقم)</th>
                            <th class="px-4 py-3">Cheque Date</th>
                            <th class="px-4 py-3">Due Date</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($cheques as $chq)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 text-xs">
                                    @if ($chq->type === 'RECEIVED')
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">RECEIVED</span>
                                    @else
                                        <span class="rounded bg-navy-100 px-2 py-0.5 font-bold text-navy-800 dark:bg-navy-900/30 dark:text-navy-300">ISSUED</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $chq->cheque_number }}
                                    @if ($chq->isPdc())
                                        <span class="ml-1 rounded bg-sky-100 px-1.5 py-0.2 text-[10px] font-semibold text-sky-700">PDC</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $chq->partyName() }}</div>
                                    <div class="text-xs text-slate-400">{{ $chq->type === 'RECEIVED' ? 'Customer' : 'Supplier' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $chq->bank_name }}
                                </td>
                                <td class="tabular px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    Rs. {{ number_format((float) $chq->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $chq->cheque_date?->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-xs font-medium {{ $chq->due_date?->isPast() && in_array($chq->status, ['RECEIVED', 'DEPOSITED']) ? 'text-red-600 font-bold' : 'text-slate-600 dark:text-slate-300' }}">
                                    {{ $chq->due_date?->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'rounded px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $chq->status === 'RECEIVED',
                                        'bg-sky-100 text-sky-800' => $chq->status === 'DEPOSITED',
                                        'bg-emerald-100 text-emerald-800' => $chq->status === 'CLEARED',
                                        'bg-red-100 text-red-800' => $chq->status === 'BOUNCED',
                                        'bg-slate-100 text-slate-600' => $chq->status === 'CANCELLED',
                                    ])>{{ $chq->status }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('cheques.show', $chq) }}" class="rounded bg-slate-100 px-2.5 py-1 font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                            View
                                        </a>

                                        @if ($chq->type === 'RECEIVED' && $chq->status === 'RECEIVED')
                                            <button type="button" @click="activeChequeId = {{ $chq->id }}; activeChequeNo = '{{ $chq->cheque_number }}'; depositModal = true" class="rounded bg-sky-50 px-2 py-1 font-medium text-sky-700 hover:bg-sky-100">
                                                Deposit
                                            </button>
                                        @endif

                                        @if (in_array($chq->status, ['RECEIVED', 'DEPOSITED']))
                                            <form action="{{ route('cheques.clear', $chq) }}" method="POST" onsubmit="return confirm('Confirm clearing cheque #{{ $chq->cheque_number }}?');" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded bg-emerald-50 px-2 py-1 font-medium text-emerald-700 hover:bg-emerald-100">
                                                    Clear
                                                </button>
                                            </form>
                                        @endif

                                        @if (! in_array($chq->status, ['BOUNCED', 'CANCELLED']))
                                            <button type="button" @click="activeChequeId = {{ $chq->id }}; activeChequeNo = '{{ $chq->cheque_number }}'; bounceModal = true" class="rounded bg-red-50 px-2 py-1 font-medium text-red-700 hover:bg-red-100">
                                                Bounce
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                    No cheques found matching criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $cheques->links() }}</div>
        </div>
    @endif

    {{-- Bounce Modal --}}
    <div x-show="bounceModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="bounceModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                    <h3 class="text-base font-bold text-red-600">🚨 Record Cheque Bounce</h3>
                    <button @click="bounceModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/cheques/' + activeChequeId + '/bounce'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <p class="text-xs text-slate-500">
                        Marking cheque <span class="font-mono font-bold" x-text="'#' + activeChequeNo"></span> as bounced will <strong>reverse the customer's ledger balance</strong>, apply bank charges, and alert the manager.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Bounce Reason *</label>
                        <select name="bounce_reason" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="Insufficient Funds / فنڈز ناکافی">Insufficient Funds / فنڈز ناکافی</option>
                            <option value="Signature Mismatch / دستخط کا فرق">Signature Mismatch / دستخط کا فرق</option>
                            <option value="Payment Stopped by Drawer / ادائیگی روک دی گئی">Payment Stopped by Drawer / ادائیگی روک دی گئی</option>
                            <option value="Account Closed / اکاؤنٹ بند ہے">Account Closed / اکاؤنٹ بند ہے</option>
                            <option value="Post-Dated Cheque / پیشگی تاریخ">Post-Dated Cheque / پیشگی تاریخ</option>
                            <option value="Stale Cheque / معیاد ختم">Stale Cheque / معیاد ختم</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Bank Penalty Charges (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="bank_charges" value="500.00" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                        <span class="mt-1 block text-[11px] text-slate-400">Standard bounce fee recovered from customer and charged to bank.</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Bounce Date</label>
                        <input type="date" name="bounced_date" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="bounceModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm Bounce</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Deposit Modal --}}
    <div x-show="depositModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="depositModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Deposit Cheque into Station Bank</h3>
                    <button @click="depositModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/cheques/' + activeChequeId + '/deposit'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Deposit into Station Bank Account *</label>
                        <select name="bank_account_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Deposit Date</label>
                        <input type="date" name="deposit_date" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="depositModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Mark Deposited</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
