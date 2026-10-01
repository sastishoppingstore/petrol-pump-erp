@extends('layouts.app')

@section('title', 'Cheque Register / چیک رجسٹر')
@section('breadcrumb')
    <li class="text-slate-500">Cheque Register</li>
@endsection

@section('content')
<div x-data="{ bounceModal: false, depositModal: false, activeChequeId: null, activeChequeNo: '' }" class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>Cheque Register / چیک رجسٹر
            <span class="ml-2 align-middle rounded bg-vital-primary/10 px-2.5 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
                PDC &amp; Clearing
            </span>
        </h1>
        <p>Track post-dated &amp; current cheques received from customers and issued to fuel suppliers.</p>
        <div class="page-actions">
            <div class="flex rounded-xl border border-slate-200 bg-slate-100 p-0.5 shadow-inner dark:border-slate-800 dark:bg-slate-800">
                <a href="{{ route('cheques.index', ['view_mode' => 'list']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $view_mode !== 'calendar' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    List View
                </a>
                <a href="{{ route('cheques.index', ['view_mode' => 'calendar']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $view_mode === 'calendar' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    📅 PDC Calendar
                </a>
            </div>

            <a href="{{ route('cheques.create', ['type' => 'RECEIVED']) }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Receive Customer Cheque
            </a>
            <a href="{{ route('cheques.create', ['type' => 'ISSUED']) }}" class="btn-3d btn-3d-navy">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Issue Supplier Cheque
            </a>
        </div>
    </div>

    {{-- Top Metrics Bar --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">Customer Cheques</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['total_received_amount'] ?? 0), 2) }}</div>
            <div class="stat-sub">Total received to date</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">Supplier Cheques</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['total_issued_amount'] ?? 0), 2) }}</div>
            <div class="stat-sub">Issued for fuel indents</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">Active PDCs</div>
            <div class="stat-value tabular">{{ $stats['pdc_count'] ?? 0 }} <span class="text-xs font-normal opacity-80">({{ number_format((float) ($stats['pdc_amount'] ?? 0), 0) }})</span></div>
            <div class="stat-sub">Due in future</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">Cleared Cheques</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['cleared_amount'] ?? 0), 2) }}</div>
            <div class="stat-sub">Settled in bank ledger</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">Bounced Cheques</div>
            <div class="stat-value tabular">{{ $stats['bounced_count'] ?? 0 }}</div>
            <div class="stat-sub">Reversed with penalties</div>
        </div>
    </div>

    @if ($view_mode === 'calendar')
        {{-- PDC Calendar View --}}
        <div class="glass-card p-6">
            <div class="mb-6 text-center">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    PDC Schedule for {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                </h2>
                <span class="mt-1 inline-block rounded bg-sky-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                    Total Due: Rs. {{ number_format((float) $total_amount, 2) }}
                </span>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @php
                        $prevMonth = $month == 1 ? 12 : $month - 1;
                        $prevYear = $month == 1 ? $year - 1 : $year;
                        $nextMonth = $month == 12 ? 1 : $month + 1;
                        $nextYear = $month == 12 ? $year + 1 : $year;
                    @endphp
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => $prevMonth, 'year' => $prevYear]) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        &larr; Prev Month
                    </a>
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => date('n'), 'year' => date('Y')]) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        Current Month
                    </a>
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => $nextMonth, 'year' => $nextYear]) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        Next Month &rarr;
                    </a>
                </div>
            </div>

            <div class="space-y-4">
                @if (empty($grouped_by_date))
                    <div class="rounded-2xl border border-dashed border-slate-300 py-12 text-center text-sm text-slate-500 dark:border-slate-700">
                        No cheques scheduled for clearance in {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}.
                    </div>
                @else
                    @foreach ($grouped_by_date as $date => $dateCheques)
                        <div class="rounded-2xl border border-white/60 bg-white/50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-800/40">
                            <div class="mb-3 flex flex-wrap items-center justify-center gap-2 text-center font-bold text-slate-900 dark:text-white">
                                <svg class="h-4 w-4 text-vital-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                                @if (\Carbon\Carbon::parse($date)->isToday())
                                    <span class="rounded bg-vital-primary px-2 py-0.5 text-[11px] font-bold text-white">TODAY</span>
                                @endif
                                <span class="tabular font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">
                                    {{ count($dateCheques) }} cheque(s) &bull; Rs. {{ number_format(collect($dateCheques)->sum('amount'), 2) }}
                                </span>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($dateCheques as $chq)
                                    <div class="glass-card card-3d p-3.5 text-center">
                                        <div class="flex items-start justify-between">
                                            <div class="text-left">
                                                <span class="tabular font-mono text-xs font-bold text-slate-900 dark:text-white">#{{ $chq->cheque_number }}</span>
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
                                            <span class="tabular font-mono text-sm font-bold text-slate-900 dark:text-white">
                                                Rs. {{ number_format((float) $chq->amount, 2) }}
                                            </span>
                                            <a href="{{ route('cheques.show', $chq) }}" class="text-xs font-semibold text-vital-primary hover:underline">
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
        <form method="GET" action="{{ route('cheques.index') }}" class="glass-card p-4">
            <input type="hidden" name="view_mode" value="list">
            <div class="grid gap-3 sm:grid-cols-5">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Cheque Type</label>
                    <select name="type" class="input-3d w-full text-center text-sm">
                        <option value="">All (Received &amp; Issued)</option>
                        <option value="RECEIVED" @selected(request('type') === 'RECEIVED')>Received (From Customer)</option>
                        <option value="ISSUED" @selected(request('type') === 'ISSUED')>Issued (To Supplier)</option>
                    </select>
                </div>
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Status</label>
                    <select name="status" class="input-3d w-full text-center text-sm">
                        <option value="">All Statuses</option>
                        <option value="RECEIVED" @selected(request('status') === 'RECEIVED')>In Hand (Received)</option>
                        <option value="DEPOSITED" @selected(request('status') === 'DEPOSITED')>Deposited in Bank</option>
                        <option value="CLEARED" @selected(request('status') === 'CLEARED')>Cleared / Settled</option>
                        <option value="BOUNCED" @selected(request('status') === 'BOUNCED')>Bounced</option>
                        <option value="CANCELLED" @selected(request('status') === 'CANCELLED')>Cancelled</option>
                    </select>
                </div>
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Search Cheque # / Party</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. 004921, Bilal Motors" class="input-3d w-full text-center text-sm">
                </div>
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Due Date Range</label>
                    <input type="date" name="due_from" value="{{ request('due_from') }}" class="input-3d w-full text-center text-sm">
                </div>
                <div class="flex items-end justify-center gap-2">
                    <button type="submit" class="btn-3d btn-3d-navy w-full">Filter</button>
                    <a href="{{ route('cheques.index') }}" class="btn-3d btn-3d-ghost">Reset</a>
                </div>
            </div>
        </form>

        {{-- Cheques Table --}}
        <div class="glass-card overflow-hidden">
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Cheque #</th>
                            <th>Party (کسٹمر / سپلائر)</th>
                            <th>Bank Name</th>
                            <th>Amount (رقم)</th>
                            <th>Cheque Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cheques as $chq)
                            <tr>
                                <td class="text-xs">
                                    @if ($chq->type === 'RECEIVED')
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">RECEIVED</span>
                                    @else
                                        <span class="rounded bg-sky-100 px-2 py-0.5 font-bold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">ISSUED</span>
                                    @endif
                                </td>
                                <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $chq->cheque_number }}
                                    @if ($chq->isPdc())
                                        <span class="ml-1 rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700">PDC</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $chq->partyName() }}</div>
                                    <div class="text-xs text-slate-400">{{ $chq->type === 'RECEIVED' ? 'Customer' : 'Supplier' }}</div>
                                </td>
                                <td class="text-xs text-slate-600 dark:text-slate-300">
                                    {{ $chq->bank_name }}
                                </td>
                                <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                    Rs. {{ number_format((float) $chq->amount, 2) }}
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $chq->cheque_date?->format('d M Y') }}
                                </td>
                                <td class="text-xs font-medium {{ $chq->due_date?->isPast() && in_array($chq->status, ['RECEIVED', 'DEPOSITED']) ? 'text-vital-primary font-bold' : 'text-slate-600 dark:text-slate-300' }}">
                                    {{ $chq->due_date?->format('d M Y') }}
                                </td>
                                <td>
                                    <span @class([
                                        'rounded px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $chq->status === 'RECEIVED',
                                        'bg-sky-100 text-sky-800' => $chq->status === 'DEPOSITED',
                                        'bg-emerald-100 text-emerald-800' => $chq->status === 'CLEARED',
                                        'bg-red-100 text-red-800' => $chq->status === 'BOUNCED',
                                        'bg-slate-100 text-slate-600' => $chq->status === 'CANCELLED',
                                    ])>{{ $chq->status }}</span>
                                </td>
                                <td class="whitespace-nowrap text-xs">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('cheques.show', $chq) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                            View
                                        </a>

                                        @if ($chq->type === 'RECEIVED' && $chq->status === 'RECEIVED')
                                            <button type="button" @click="activeChequeId = {{ $chq->id }}; activeChequeNo = '{{ $chq->cheque_number }}'; depositModal = true" class="btn-3d btn-3d-navy btn-3d-sm">
                                                Deposit
                                            </button>
                                        @endif

                                        @if (in_array($chq->status, ['RECEIVED', 'DEPOSITED']))
                                            <form action="{{ route('cheques.clear', $chq) }}" method="POST" onsubmit="return confirm('Confirm clearing cheque #{{ $chq->cheque_number }}?');" class="inline">
                                                @csrf
                                                <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">
                                                    Clear
                                                </button>
                                            </form>
                                        @endif

                                        @if (! in_array($chq->status, ['BOUNCED', 'CANCELLED']))
                                            <button type="button" @click="activeChequeId = {{ $chq->id }}; activeChequeNo = '{{ $chq->cheque_number }}'; bounceModal = true" class="btn-3d btn-3d-primary btn-3d-sm">
                                                Bounce
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">
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

    <a href="{{ route('cheques.create', ['type' => 'RECEIVED']) }}" class="fab-3d" title="Receive a customer cheque">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>

    {{-- Bounce Modal --}}
    <div x-show="bounceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="bounceModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h3 class="text-base font-bold text-vital-primary">🚨 Record Cheque Bounce</h3>
                    <button @click="bounceModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/cheques/' + activeChequeId + '/bounce'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <p class="text-center text-xs text-slate-500">
                        Marking cheque <span class="font-mono font-bold" x-text="'#' + activeChequeNo"></span> as bounced will <strong>reverse the customer's ledger balance</strong>, apply bank charges, and alert the manager.
                    </p>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Bounce Reason *</label>
                        <select name="bounce_reason" required class="input-3d w-full text-center text-sm">
                            <option value="Insufficient Funds / فنڈز ناکافی">Insufficient Funds / فنڈز ناکافی</option>
                            <option value="Signature Mismatch / دستخط کا فرق">Signature Mismatch / دستخط کا فرق</option>
                            <option value="Payment Stopped by Drawer / ادائیگی روک دی گئی">Payment Stopped by Drawer / ادائیگی روک دی گئی</option>
                            <option value="Account Closed / اکاؤنٹ بند ہے">Account Closed / اکاؤنٹ بند ہے</option>
                            <option value="Post-Dated Cheque / پیشگی تاریخ">Post-Dated Cheque / پیشگی تاریخ</option>
                            <option value="Stale Cheque / معیاد ختم">Stale Cheque / معیاد ختم</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Bank Penalty Charges (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="bank_charges" value="500.00" class="input-3d tabular w-full text-center font-mono text-sm">
                        <span class="mt-1 block text-center text-[11px] text-slate-400">Standard bounce fee recovered from customer and charged to bank.</span>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Bounce Date</label>
                        <input type="date" name="bounced_date" value="{{ date('Y-m-d') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="bounceModal = false" class="btn-3d btn-3d-ghost">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-primary">Confirm Bounce</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Deposit Modal --}}
    <div x-show="depositModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="depositModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Deposit Cheque into Station Bank</h3>
                    <button @click="depositModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/cheques/' + activeChequeId + '/deposit'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Deposit into Station Bank Account *</label>
                        <select name="bank_account_id" required class="input-3d w-full text-center text-sm">
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Deposit Date</label>
                        <input type="date" name="deposit_date" value="{{ date('Y-m-d') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="depositModal = false" class="btn-3d btn-3d-ghost">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-navy">Mark Deposited</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
