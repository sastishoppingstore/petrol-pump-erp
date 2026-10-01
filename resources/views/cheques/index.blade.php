@extends('layouts.app')

@section('title', __('finance.cheques.title'))
@section('breadcrumb')
    <li class="text-slate-500">{{ __('finance.cheques.title') }}</li>
@endsection

@section('content')
<div x-data="{ bounceModal: false, depositModal: false, activeChequeId: null, activeChequeNo: '' }" class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ __('finance.cheques.title') }}
            <span class="ml-2 align-middle rounded bg-vital-primary/10 px-2.5 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
                {{ __('finance.cheques.badge') }}
            </span>
        </h1>
        <p>{{ __('finance.cheques.subheading') }}</p>
        <div class="page-actions">
            <div class="flex rounded-xl border border-slate-200 bg-slate-100 p-0.5 shadow-inner dark:border-slate-800 dark:bg-slate-800">
                <a href="{{ route('cheques.index', ['view_mode' => 'list']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $view_mode !== 'calendar' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    {{ __('finance.cheques.list_view') }}
                </a>
                <a href="{{ route('cheques.index', ['view_mode' => 'calendar']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $view_mode === 'calendar' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    {{ __('finance.cheques.pdc_calendar') }}
                </a>
            </div>

            <a href="{{ route('cheques.create', ['type' => 'RECEIVED']) }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('finance.cheques.receive_btn') }}
            </a>
            <a href="{{ route('cheques.create', ['type' => 'ISSUED']) }}" class="btn-3d btn-3d-navy">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('finance.cheques.issue_btn') }}
            </a>
        </div>
    </div>

    {{-- Top Metrics Bar --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('finance.cheques.customer_cheques') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['total_received_amount'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ __('finance.cheques.total_received_sub') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('finance.cheques.supplier_cheques') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['total_issued_amount'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ __('finance.cheques.issued_sub') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">{{ __('finance.cheques.active_pdcs') }}</div>
            <div class="stat-value tabular">{{ $stats['pdc_count'] ?? 0 }} <span class="text-xs font-normal opacity-80">({{ number_format((float) ($stats['pdc_amount'] ?? 0), 0) }})</span></div>
            <div class="stat-sub">{{ __('finance.cheques.due_future_sub') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('finance.cheques.cleared_cheques') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($stats['cleared_amount'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ __('finance.cheques.cleared_sub') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('finance.cheques.bounced_cheques') }}</div>
            <div class="stat-value tabular">{{ $stats['bounced_count'] ?? 0 }}</div>
            <div class="stat-sub">{{ __('finance.cheques.bounced_sub') }}</div>
        </div>
    </div>

    @if ($view_mode === 'calendar')
        {{-- PDC Calendar View --}}
        <div class="glass-card p-6">
            <div class="mb-6 text-center">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ __('finance.cheques.pdc_schedule_for') }} {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                </h2>
                <span class="mt-1 inline-block rounded bg-sky-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                    {{ __('finance.cheques.total_due') }}: Rs. {{ number_format((float) $total_amount, 2) }}
                </span>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @php
                        $prevMonth = $month == 1 ? 12 : $month - 1;
                        $prevYear = $month == 1 ? $year - 1 : $year;
                        $nextMonth = $month == 12 ? 1 : $month + 1;
                        $nextYear = $month == 12 ? $year + 1 : $year;
                    @endphp
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => $prevMonth, 'year' => $prevYear]) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        {{ __('finance.cheques.prev_month') }}
                    </a>
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => date('n'), 'year' => date('Y')]) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        {{ __('finance.cheques.current_month') }}
                    </a>
                    <a href="{{ route('cheques.index', ['view_mode' => 'calendar', 'month' => $nextMonth, 'year' => $nextYear]) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                        {{ __('finance.cheques.next_month') }}
                    </a>
                </div>
            </div>

            <div class="space-y-4">
                @if (empty($grouped_by_date))
                    <div class="rounded-2xl border border-dashed border-slate-300 py-12 text-center text-sm text-slate-500 dark:border-slate-700">
                        {{ __('finance.cheques.no_scheduled', ['month' => \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y')]) }}
                    </div>
                @else
                    @foreach ($grouped_by_date as $date => $dateCheques)
                        <div class="rounded-2xl border border-white/60 bg-white/50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-800/40">
                            <div class="mb-3 flex flex-wrap items-center justify-center gap-2 text-center font-bold text-slate-900 dark:text-white">
                                <svg class="h-4 w-4 text-vital-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                                @if (\Carbon\Carbon::parse($date)->isToday())
                                    <span class="rounded bg-vital-primary px-2 py-0.5 text-[11px] font-bold text-white">{{ __('finance.cheques.today') }}</span>
                                @endif
                                <span class="tabular font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">
                                    {{ count($dateCheques) }} {{ __('finance.cheques.cheques_word_count') }} &bull; Rs. {{ number_format(collect($dateCheques)->sum('amount'), 2) }}
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
                                                {{ __('finance.cheques.view_arrow') }}
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
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.cheque_type') }}</label>
                    <select name="type" class="input-3d w-full text-center text-sm">
                        <option value="">{{ __('finance.cheques.all_types') }}</option>
                        <option value="RECEIVED" @selected(request('type') === 'RECEIVED')>{{ __('finance.cheques.received_from_customer_opt') }}</option>
                        <option value="ISSUED" @selected(request('type') === 'ISSUED')>{{ __('finance.cheques.issued_to_supplier_opt') }}</option>
                    </select>
                </div>
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.common.status') }}</label>
                    <select name="status" class="input-3d w-full text-center text-sm">
                        <option value="">{{ __('finance.cheques.all_statuses') }}</option>
                        <option value="RECEIVED" @selected(request('status') === 'RECEIVED')>{{ __('finance.cheques.in_hand') }}</option>
                        <option value="DEPOSITED" @selected(request('status') === 'DEPOSITED')>{{ __('finance.cheques.deposited_in_bank') }}</option>
                        <option value="CLEARED" @selected(request('status') === 'CLEARED')>{{ __('finance.cheques.cleared_settled') }}</option>
                        <option value="BOUNCED" @selected(request('status') === 'BOUNCED')>{{ __('finance.cheques.bounced_word') }}</option>
                        <option value="CANCELLED" @selected(request('status') === 'CANCELLED')>{{ __('finance.cheques.cancelled_word') }}</option>
                    </select>
                </div>
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.search_label') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('finance.cheques.ph_search') }}" class="input-3d w-full text-center text-sm">
                </div>
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.due_range') }}</label>
                    <input type="date" name="due_from" value="{{ request('due_from') }}" class="input-3d w-full text-center text-sm">
                </div>
                <div class="flex items-end justify-center gap-2">
                    <button type="submit" class="btn-3d btn-3d-navy w-full">{{ __('ui.actions.filter') }}</button>
                    <a href="{{ route('cheques.index') }}" class="btn-3d btn-3d-ghost">{{ __('finance.common.reset') }}</a>
                </div>
            </div>
        </form>

        {{-- Cheques Table --}}
        <div class="glass-card overflow-hidden">
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('finance.common.type') }}</th>
                            <th>{{ __('finance.cheques.cheque_no') }}</th>
                            <th>{{ __('finance.cheques.party') }}</th>
                            <th>{{ __('finance.banks.bank_name') }}</th>
                            <th>{{ __('finance.common.amount') }}</th>
                            <th>{{ __('finance.cheques.cheque_date') }}</th>
                            <th>{{ __('finance.cheques.due_date') }}</th>
                            <th>{{ __('finance.common.status') }}</th>
                            <th>{{ __('finance.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cheques as $chq)
                            <tr>
                                <td class="text-xs">
                                    @if ($chq->type === 'RECEIVED')
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('finance.cheques.received_badge') }}</span>
                                    @else
                                        <span class="rounded bg-sky-100 px-2 py-0.5 font-bold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">{{ __('finance.cheques.issued_badge') }}</span>
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
                                    <div class="text-xs text-slate-400">{{ $chq->type === 'RECEIVED' ? __('finance.cheques.customer_word') : __('finance.cheques.supplier_word') }}</div>
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
                                            {{ __('ui.actions.view') }}
                                        </a>

                                        @if ($chq->type === 'RECEIVED' && $chq->status === 'RECEIVED')
                                            <button type="button" @click="activeChequeId = {{ $chq->id }}; activeChequeNo = '{{ $chq->cheque_number }}'; depositModal = true" class="btn-3d btn-3d-navy btn-3d-sm">
                                                {{ __('finance.cheques.deposit_btn') }}
                                            </button>
                                        @endif

                                        @if (in_array($chq->status, ['RECEIVED', 'DEPOSITED']))
                                            <form action="{{ route('cheques.clear', $chq) }}" method="POST" onsubmit="return confirm('Confirm clearing cheque #{{ $chq->cheque_number }}?');" class="inline">
                                                @csrf
                                                <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">
                                                    {{ __('finance.cheques.clear_btn') }}
                                                </button>
                                            </form>
                                        @endif

                                        @if (! in_array($chq->status, ['BOUNCED', 'CANCELLED']))
                                            <button type="button" @click="activeChequeId = {{ $chq->id }}; activeChequeNo = '{{ $chq->cheque_number }}'; bounceModal = true" class="btn-3d btn-3d-primary btn-3d-sm">
                                                {{ __('finance.cheques.bounce_btn') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">
                                    {{ __('finance.cheques.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $cheques->links() }}</div>
        </div>
    @endif

    <a href="{{ route('cheques.create', ['type' => 'RECEIVED']) }}" class="fab-3d" title="{{ __('finance.cheques.fab_title') }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>

    {{-- Bounce Modal --}}
    <div x-show="bounceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="bounceModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h3 class="text-base font-bold text-vital-primary">{{ __('finance.cheques.bounce_heading') }}</h3>
                    <button @click="bounceModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/cheques/' + activeChequeId + '/bounce'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <p class="text-center text-xs text-slate-500">
                        {{ __('finance.cheques.bounce_marking') }} <span class="font-mono font-bold" x-text="'#' + activeChequeNo"></span> {{ __('finance.cheques.bounce_as_will') }} <strong>{{ __('finance.cheques.bounce_reverse_customer') }}</strong>{{ __('finance.cheques.bounce_tail') }}
                    </p>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.bounce_reason') }} *</label>
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
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.penalty_charges') }}</label>
                        <input type="number" step="0.01" min="0" name="bank_charges" value="500.00" class="input-3d tabular w-full text-center font-mono text-sm">
                        <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.cheques.penalty_hint') }}</span>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.bounce_date') }}</label>
                        <input type="date" name="bounced_date" value="{{ date('Y-m-d') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="bounceModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-primary">{{ __('finance.cheques.confirm_bounce') }}</button>
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
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('finance.cheques.deposit_heading') }}</h3>
                    <button @click="depositModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/cheques/' + activeChequeId + '/deposit'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.deposit_into_account') }} *</label>
                        <select name="bank_account_id" required class="input-3d w-full text-center text-sm">
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.deposit_date') }}</label>
                        <input type="date" name="deposit_date" value="{{ date('Y-m-d') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="depositModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-navy">{{ __('finance.cheques.mark_deposited') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
