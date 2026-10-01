@extends('layouts.app')

@section('title', __('forecourt.closing.page_title'))

@section('content')
<div class="mx-auto max-w-7xl">
    <!-- Header Banner -->
    <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-vital-primary via-[#c11119] to-vital-darkred p-6 text-center text-white shadow-glow sm:p-8">
        <span class="inline-block rounded-full bg-white/20 px-3 py-1 font-mono text-[11px] font-black uppercase tracking-widest">{{ __('forecourt.closing.badge') }}</span>
        <h1 class="mt-3 text-3xl font-black">{{ __('forecourt.closing.heading') }}</h1>
        <div class="mt-1 font-urdu text-lg text-red-100">
            {{ __('forecourt.closing.sub') }}
        </div>
        <form method="GET" action="{{ route('closing.index') }}" class="mt-5 flex flex-wrap items-center justify-center gap-2">
            <div class="field-3d w-52">
                <input type="date" name="date" class="input-3d text-center" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <button type="submit" class="btn-3d !bg-white !text-vital-darkred">{{ __('forecourt.closing.load_date') }}</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="list-inside list-disc text-left">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Lock Status Card if already closed -->
    @if($existingClosing && $existingClosing->isClosed())
        <div class="glass-card mb-6 flex flex-col items-center justify-between gap-4 border-l-8 !border-l-red-500 p-5 text-center sm:flex-row sm:text-left">
            <div>
                <h2 class="text-lg font-black text-red-600">{{ __('forecourt.closing.locked_banner_date', ['date' => $date]) }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ __('forecourt.closing.closing_no') }}<strong>{{ $existingClosing->closing_number }}</strong> {{ __('forecourt.closing.approved_by_on', ['name' => $existingClosing->approvedByUser?->name ?? 'Manager', 'date' => $existingClosing->updated_at->format('d M Y, h:i A')]) }}
                    @if($existingClosing->email_sent_at)
                        {{ __('forecourt.closing.emailed', ['email' => $existingClosing->email_recipient]) }}
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('closing.unlock', $existingClosing->id) }}" onsubmit="return confirm('Unlock day for corrections? All edits will be logged.')">
                @csrf
                <input type="hidden" name="reason" value="Manager manual unlock for reconciliation adjustments">
                <button type="submit" class="btn-3d !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">
                    {{ __('forecourt.closing.unlock') }}
                </button>
            </form>
        </div>
    @endif

    <!-- 4 Verification Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- 1. Shifts Verification -->
        <div class="glass-card card-3d p-5 text-center">
            <div class="flex items-center justify-between">
                <span class="pill-status {{ $checklist['shifts']['is_verified'] ? 'pill-active' : 'pill-pending' }}"><span class="dot"></span>{{ $checklist['shifts']['is_verified'] ? '✓ Step 1 Verified' : '⚠ Step 1 Pending' }}</span>
                <span class="text-3xl">🕐</span>
            </div>
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">{{ __('forecourt.closing.shifts') }}</h2>
            <div class="font-urdu text-sm text-slate-400">{{ __('forecourt.closing.shifts_sub') }}</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">{{ __('forecourt.closing.total_shifts') }}</span><strong>{{ $checklist['shifts']['total'] }}</strong></div>
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('forecourt.closing.open_pending') }}</span>
                    <strong class="{{ $checklist['shifts']['open_or_pending'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $checklist['shifts']['open_or_pending'] }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- 2. Tank Dips Verification -->
        <div class="glass-card card-3d p-5 text-center">
            <div class="flex items-center justify-between">
                <span class="pill-status {{ $checklist['tank_dips']['is_verified'] ? 'pill-active' : 'pill-pending' }}"><span class="dot"></span>{{ $checklist['tank_dips']['is_verified'] ? '✓ Step 2 Verified' : '⚠ Step 2 Pending' }}</span>
                <span class="text-3xl">📐</span>
            </div>
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">{{ __('forecourt.closing.tanks') }}</h2>
            <div class="font-urdu text-sm text-slate-400">{{ __('forecourt.closing.tanks_sub') }}</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">{{ __('forecourt.closing.active_tanks') }}</span><strong>{{ $checklist['tank_dips']['active_tanks'] }}</strong></div>
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('forecourt.closing.dips_today') }}</span>
                    <strong class="{{ $checklist['tank_dips']['dips_recorded'] < $checklist['tank_dips']['active_tanks'] ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $checklist['tank_dips']['dips_recorded'] }} / {{ $checklist['tank_dips']['active_tanks'] }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- 3. Cash Reconciliation -->
        <div class="glass-card card-3d p-5 text-center">
            <div class="flex items-center justify-between">
                <span class="pill-status {{ $checklist['cash']['is_verified'] ? 'pill-active' : 'pill-pending' }}"><span class="dot"></span>{{ $checklist['cash']['is_verified'] ? '✓ Step 3 Verified' : '⚠ Step 3 Pending' }}</span>
                <span class="text-3xl">💵</span>
            </div>
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">{{ __('forecourt.closing.cash') }}</h2>
            <div class="font-urdu text-sm text-slate-400">{{ __('forecourt.closing.cash_sub') }}</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">{{ __('forecourt.closing.expected_cash') }}</span><strong>{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expected_cash']) }}</strong></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('forecourt.closing.actual_counted') }}</span><strong>{{ \App\Support\PakistaniCurrency::format($checklist['cash']['actual_cash_counted']) }}</strong></div>
            </div>
        </div>

        <!-- 4. Bank Deposits -->
        <div class="glass-card card-3d p-5 text-center">
            <div class="flex items-center justify-between">
                <span class="pill-status pill-active"><span class="dot"></span>{{ __('forecourt.closing.verified') }}</span>
                <span class="text-3xl">🏦</span>
            </div>
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">{{ __('forecourt.closing.deposits') }}</h2>
            <div class="font-urdu text-sm text-slate-400">{{ __('forecourt.closing.deposits_sub') }}</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">{{ __('forecourt.closing.deposits_count') }}</span><strong>{{ $checklist['bank_deposits']['count'] }}</strong></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('forecourt.closing.total_deposited') }}</span><strong class="text-emerald-600">{{ \App\Support\PakistaniCurrency::format($checklist['bank_deposits']['total_amount']) }}</strong></div>
            </div>
        </div>
    </div>

    <!-- Summary Details & Closing Execution Form -->
    <div class="grid items-start gap-5 xl:grid-cols-5">
        <div class="glass-card overflow-hidden xl:col-span-3">
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">{{ __('forecourt.closing.fin_heading_date', ['date' => $date]) }}</h2>
            <div class="table-3d">
                <table>
                    <tbody>
                        <tr>
                            <td class="font-bold">{{ __('forecourt.closing.total_litres') }}</td>
                            <td class="tabular font-black">{{ number_format((float) $checklist['sales_totals']['total_litres'], 3) }} L</td>
                        </tr>
                        <tr>
                            <td class="font-bold">{{ __('forecourt.closing.total_turnover') }}</td>
                            <td class="tabular font-black">{{ \App\Support\PakistaniCurrency::format($checklist['sales_totals']['total_amount']) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('forecourt.closing.cash_sales') }}</td>
                            <td class="tabular">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_sales']) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('forecourt.closing.credit_sales') }}</td>
                            <td class="tabular">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['credit_sales']) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('forecourt.closing.omc') }}</td>
                            <td class="tabular">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['card_sales']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">{{ __('forecourt.closing.udhaar_receipts') }}</td>
                            <td class="tabular font-bold text-emerald-600">+{{ \App\Support\PakistaniCurrency::format($checklist['cash']['customer_receipts']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">{{ __('forecourt.closing.cash_expenses') }}</td>
                            <td class="tabular font-bold text-red-600">-{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expenses_paid']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">{{ __('forecourt.closing.bank_deposits_made') }}</td>
                            <td class="tabular font-bold text-sky-600">-{{ \App\Support\PakistaniCurrency::format($checklist['cash']['bank_deposits']) }}</td>
                        </tr>
                        <tr class="bg-slate-100/70 dark:bg-slate-800/60">
                            <td class="font-black">{{ __('forecourt.closing.calc_expected') }}</td>
                            <td class="tabular font-black">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expected_cash']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">{{ __('forecourt.closing.actual_handed') }}</td>
                            <td class="tabular font-black">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['actual_cash_counted']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-black">{{ __('forecourt.closing.short_over') }}</td>
                            <td class="tabular text-base font-black">
                                @if((float) $checklist['cash']['cash_variance'] == 0)
                                    <span class="pill-status pill-active"><span class="dot"></span>Rs. 0.00 {{ __('forecourt.closing.balanced') }}</span>
                                @elseif((float) $checklist['cash']['cash_variance'] > 0)
                                    <span class="text-emerald-600">+{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_variance']) }} {{ __('forecourt.closing.over') }}</span>
                                @else
                                    <span class="text-red-600">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_variance']) }} {{ __('forecourt.closing.short') }}</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass-card overflow-hidden xl:col-span-2">
            <div class="h-1.5 bg-gradient-to-r from-vital-primary to-vital-darkred"></div>
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">{{ __('forecourt.closing.execute') }}</h2>
            <form method="POST" action="{{ route('closing.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="closing_date" value="{{ $date }}">

                <div class="field-3d mb-4">
                    <label for="actual_cash_counted">{{ __('forecourt.closing.actual_cash_label') }}</label>
                    <input type="number" step="0.01" id="actual_cash_counted" name="actual_cash_counted" class="input-3d text-center font-mono text-lg font-black"
                           value="{{ old('actual_cash_counted', $checklist['cash']['actual_cash_counted']) }}" required>
                    <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.closing.safe_note') }}</p>
                </div>

                <div class="field-3d mb-4">
                    <label for="closing_notes">{{ __('forecourt.closing.closing_notes') }}</label>
                    <textarea id="closing_notes" name="notes" class="input-3d" rows="3" placeholder="{{ __('forecourt.closing.notes_placeholder') }}"></textarea>
                </div>

                @if(! $checklist['ready_to_close'])
                    <div class="alert alert-warning">
                        <strong>{{ __('forecourt.closing.checklist') }}</strong>
                        @if(! $checklist['shifts']['is_verified'])
                            <div>{{ __('forecourt.closing.open_shifts_warn', ['count' => $checklist['shifts']['open_or_pending']]) }}</div>
                        @endif
                        @if(! $checklist['tank_dips']['is_verified'])
                            <div>{{ __('forecourt.closing.dips_missing_warn', ['count' => $checklist['tank_dips']['active_tanks'] - $checklist['tank_dips']['dips_recorded']]) }}</div>
                        @endif
                    </div>

                    <label class="mb-4 flex cursor-pointer items-center justify-center gap-2 text-sm font-bold text-red-600" for="overrideCheck">
                        <input class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" type="checkbox" name="force_override" value="1" id="overrideCheck">
                        {{ __('forecourt.closing.override') }}
                    </label>
                @endif

                <button type="submit" class="btn-3d btn-3d-primary w-full py-3 text-base">
                    {{ __('forecourt.closing.complete') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
