@extends('layouts.app')

@section('title', 'Daily Closing Wizard — روزانہ اختتامی وزرڈ')

@section('content')
<div class="mx-auto max-w-7xl">
    <!-- Header Banner -->
    <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-vital-primary via-[#c11119] to-vital-darkred p-6 text-center text-white shadow-glow sm:p-8">
        <span class="inline-block rounded-full bg-white/20 px-3 py-1 font-mono text-[11px] font-black uppercase tracking-widest">Forecourt Reconciliation • Day Lock</span>
        <h1 class="mt-3 text-3xl font-black">🔒 Daily Closing Wizard</h1>
        <div class="mt-1 font-urdu text-lg text-red-100">
            روزانہ اختتامی کارروائی، ڈِپ و کیش پڑتال اور تاریخ کا لاک — مہر فلنگ اسٹیشن
        </div>
        <form method="GET" action="{{ route('closing.index') }}" class="mt-5 flex flex-wrap items-center justify-center gap-2">
            <div class="field-3d w-52">
                <input type="date" name="date" class="input-3d text-center" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <button type="submit" class="btn-3d !bg-white !text-vital-darkred">Load Date</button>
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
                <h2 class="text-lg font-black text-red-600">🔒 Day {{ $date }} is Officially Closed & Locked</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Closing #<strong>{{ $existingClosing->closing_number }}</strong> approved by {{ $existingClosing->approvedByUser?->name ?? 'Manager' }} on {{ $existingClosing->updated_at->format('d M Y, h:i A') }}.
                    @if($existingClosing->email_sent_at)
                        • Summary emailed to owner ({{ $existingClosing->email_recipient }}).
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('closing.unlock', $existingClosing->id) }}" onsubmit="return confirm('Unlock day for corrections? All edits will be logged.')">
                @csrf
                <input type="hidden" name="reason" value="Manager manual unlock for reconciliation adjustments">
                <button type="submit" class="btn-3d !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">
                    🔓 Unlock Day for Adjustments
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
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">Shift Closures</h2>
            <div class="font-urdu text-sm text-slate-400">تمام شفٹوں کی بندش</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Total Shifts Today:</span><strong>{{ $checklist['shifts']['total'] }}</strong></div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Open / Pending Shifts:</span>
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
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">Tank Physical Dips</h2>
            <div class="font-urdu text-sm text-slate-400">ٹینکیوں کی ڈِپ پیمائش</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Active Fuel Tanks:</span><strong>{{ $checklist['tank_dips']['active_tanks'] }}</strong></div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Dips Recorded Today:</span>
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
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">Cash Counted</h2>
            <div class="font-urdu text-sm text-slate-400">گنتی شدہ نقد رقم</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Expected Cash:</span><strong>{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expected_cash']) }}</strong></div>
                <div class="flex justify-between"><span class="text-slate-500">Actual Counted:</span><strong>{{ \App\Support\PakistaniCurrency::format($checklist['cash']['actual_cash_counted']) }}</strong></div>
            </div>
        </div>

        <!-- 4. Bank Deposits -->
        <div class="glass-card card-3d p-5 text-center">
            <div class="flex items-center justify-between">
                <span class="pill-status pill-active"><span class="dot"></span>✓ Step 4 Verified</span>
                <span class="text-3xl">🏦</span>
            </div>
            <h2 class="mt-3 text-lg font-black text-slate-800 dark:text-white">Bank Deposits</h2>
            <div class="font-urdu text-sm text-slate-400">بینک میں جمع شدہ رقوم</div>
            <div class="mt-4 space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Deposits Count:</span><strong>{{ $checklist['bank_deposits']['count'] }}</strong></div>
                <div class="flex justify-between"><span class="text-slate-500">Total Deposited:</span><strong class="text-emerald-600">{{ \App\Support\PakistaniCurrency::format($checklist['bank_deposits']['total_amount']) }}</strong></div>
            </div>
        </div>
    </div>

    <!-- Summary Details & Closing Execution Form -->
    <div class="grid items-start gap-5 xl:grid-cols-5">
        <div class="glass-card overflow-hidden xl:col-span-3">
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">📊 Day {{ $date }} Financial & Stock Reconciliations</h2>
            <div class="table-3d">
                <table>
                    <tbody>
                        <tr>
                            <td class="font-bold">Total Fuel Litres Sold</td>
                            <td class="tabular font-black">{{ number_format((float) $checklist['sales_totals']['total_litres'], 3) }} L</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Total Sales Turnover</td>
                            <td class="tabular font-black">{{ \App\Support\PakistaniCurrency::format($checklist['sales_totals']['total_amount']) }}</td>
                        </tr>
                        <tr>
                            <td>• Cash Sales (نقد)</td>
                            <td class="tabular">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_sales']) }}</td>
                        </tr>
                        <tr>
                            <td>• Credit / Udhaar Sales (ادھار)</td>
                            <td class="tabular">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['credit_sales']) }}</td>
                        </tr>
                        <tr>
                            <td>• OMC / Fleet Cards</td>
                            <td class="tabular">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['card_sales']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Customer Udhaar Receipts</td>
                            <td class="tabular font-bold text-emerald-600">+{{ \App\Support\PakistaniCurrency::format($checklist['cash']['customer_receipts']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Station Cash Expenses Paid</td>
                            <td class="tabular font-bold text-red-600">-{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expenses_paid']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Bank Deposits Made</td>
                            <td class="tabular font-bold text-sky-600">-{{ \App\Support\PakistaniCurrency::format($checklist['cash']['bank_deposits']) }}</td>
                        </tr>
                        <tr class="bg-slate-100/70 dark:bg-slate-800/60">
                            <td class="font-black">Calculated Expected Cash</td>
                            <td class="tabular font-black">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['expected_cash']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Actual Cash Handed In</td>
                            <td class="tabular font-black">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['actual_cash_counted']) }}</td>
                        </tr>
                        <tr>
                            <td class="font-black">Cash Short / Over (کمی یا بیشی)</td>
                            <td class="tabular text-base font-black">
                                @if((float) $checklist['cash']['cash_variance'] == 0)
                                    <span class="pill-status pill-active"><span class="dot"></span>Rs. 0.00 (Balanced)</span>
                                @elseif((float) $checklist['cash']['cash_variance'] > 0)
                                    <span class="text-emerald-600">+{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_variance']) }} (Over)</span>
                                @else
                                    <span class="text-red-600">{{ \App\Support\PakistaniCurrency::format($checklist['cash']['cash_variance']) }} (Short)</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass-card overflow-hidden xl:col-span-2">
            <div class="h-1.5 bg-gradient-to-r from-vital-primary to-vital-darkred"></div>
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">🔒 Execute Daily Closing & Date Lock</h2>
            <form method="POST" action="{{ route('closing.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="closing_date" value="{{ $date }}">

                <div class="field-3d mb-4">
                    <label for="actual_cash_counted">Actual Physical Cash Counted (Rs.)</label>
                    <input type="number" step="0.01" id="actual_cash_counted" name="actual_cash_counted" class="input-3d text-center font-mono text-lg font-black"
                           value="{{ old('actual_cash_counted', $checklist['cash']['actual_cash_counted']) }}" required>
                    <p class="mt-1 text-center text-xs text-slate-400">Total physical cash verified in station safe</p>
                </div>

                <div class="field-3d mb-4">
                    <label for="closing_notes">Closing Notes / Manager Remarks</label>
                    <textarea id="closing_notes" name="notes" class="input-3d" rows="3" placeholder="Enter notes or explanation for any cash/dip variance..."></textarea>
                </div>

                @if(! $checklist['ready_to_close'])
                    <div class="alert alert-warning">
                        <strong>Checklist Notice:</strong>
                        @if(! $checklist['shifts']['is_verified'])
                            <div>• There are still {{ $checklist['shifts']['open_or_pending'] }} open/pending shift(s).</div>
                        @endif
                        @if(! $checklist['tank_dips']['is_verified'])
                            <div>• Dip readings missing for {{ $checklist['tank_dips']['active_tanks'] - $checklist['tank_dips']['dips_recorded'] }} tank(s).</div>
                        @endif
                    </div>

                    <label class="mb-4 flex cursor-pointer items-center justify-center gap-2 text-sm font-bold text-red-600" for="overrideCheck">
                        <input class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" type="checkbox" name="force_override" value="1" id="overrideCheck">
                        Manager Emergency Override (Proceed despite incomplete checklist)
                    </label>
                @endif

                <button type="submit" class="btn-3d btn-3d-primary w-full py-3 text-base">
                    🔒 Complete Daily Closing & Lock Day
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
