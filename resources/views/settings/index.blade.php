@extends('layouts.app')

@section('title', __('admin.settings_page.title'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.title') }}</li>
@endsection

{{--
    Station Settings — 2026 redesign (shell/cards only).
    SAKHT NOTE: Tab structure aur JS bilkul pehle jaisa hai — .tab-btn /
    .tab-content classes, data-tab attributes, tab ids aur tab script ke
    add/remove karne wale class names tabdeel NAHI kiye gaye. Tamam input
    names, values (old() / $settings), placeholders aur checked/selected
    logic bhi bilkul pehle jaisi hai. Sirf cards/fields ki styling 3D/glass
    design system par le jayi gayi hai.
--}}
@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="page-head">
        <h1>⚙️ {{ __('admin.settings_page.heading') }}</h1>
        <p>{{ __('admin.settings_page.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('settings.bill-designer') }}" class="btn-3d btn-3d-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                {{ __('admin.settings_page.bill_designer') }}
            </a>
            <a href="{{ route('backups.index') }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('admin.settings_page.backups') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mx-auto max-w-3xl rounded-2xl border border-emerald-300/60 bg-emerald-50 px-5 py-3 text-center text-sm font-semibold text-emerald-800 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-300">
            ✅ {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- TAB NAVIGATION --}}
        <div class="glass-card sticky top-0 z-10 mb-6 p-3">
            <div class="flex justify-start gap-2 overflow-x-auto pb-1 lg:justify-center">
                <button type="button" class="tab-btn active px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-red-600 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400" data-tab="station">
                    🏢 {{ __('admin.settings_page.tab_station') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="business">
                    📊 {{ __('admin.settings_page.tab_business') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="notifications">
                    🔔 {{ __('admin.settings_page.tab_notifications') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="reports">
                    📈 {{ __('admin.settings_page.tab_reports') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="email">
                    📧 {{ __('admin.settings_page.tab_email') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="theme">
                    🎨 {{ __('admin.settings_page.tab_theme') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="printing">
                    🖨️ {{ __('admin.settings_page.tab_printing') }}
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="tax">
                    💰 {{ __('admin.settings_page.tab_tax') }}
                </button>
            </div>
        </div>

        {{-- TAB 1: STATION IDENTITY --}}
        <div class="tab-content active space-y-4" id="station">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-vital-darkred dark:text-red-400">🏢 {{ __('admin.settings_page.identity_title') }}</h3>
                <p class="mt-1 text-center text-xs text-slate-500">{{ __('admin.settings_page.identity_sub') }}</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.name_ur') }}</label>
                        <input type="text" name="station_name_ur" value="{{ old('station_name_ur', $settings['station.station_name_ur'] ?? '') }}" class="input-3d text-center text-sm" dir="rtl" placeholder="مہر فلنگ اسٹیشن">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.name_en') }}</label>
                        <input type="text" name="station_name_en" value="{{ old('station_name_en', $settings['station.station_name_en'] ?? '') }}" class="input-3d text-center text-sm" placeholder="Mehar Filling Station">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.owner_name') }}</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name', $settings['station.owner_name'] ?? '') }}" class="input-3d text-center text-sm" placeholder="Muhammad Rizwan Aslam">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.common.phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone', $settings['station.phone'] ?? '') }}" class="input-3d text-center text-sm" placeholder="0300-4342343">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.whatsapp') }}</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $settings['station.whatsapp'] ?? '') }}" class="input-3d text-center text-sm" placeholder="wa.me/923004342343">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.omc_brand') }}</label>
                        <input type="text" name="omc_brand" value="{{ old('omc_brand', $settings['station.omc_brand'] ?? '') }}" class="input-3d text-center text-sm" placeholder="Vital Petroleum">
                    </div>
                    <div class="field-3d sm:col-span-2">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.station_address') }}</label>
                        <input type="text" name="address" value="{{ old('address', $settings['station.address'] ?? '') }}" class="input-3d text-center text-sm" placeholder="G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura">
                    </div>
                </div>
            </div>

            {{-- Tax & Legal Identity --}}
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-vital-darkred dark:text-red-400">📋 {{ __('admin.settings_page.tax_legal_title') }}</h3>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.ntn') }}</label>
                        <input type="text" name="ntn" value="{{ old('ntn', $settings['company.ntn'] ?? '') }}" placeholder="1234567-8" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.strn') }}</label>
                        <input type="text" name="strn" value="{{ old('strn', $settings['company.strn'] ?? '') }}" placeholder="12-34-5678-901-23" class="input-3d text-center text-sm">
                    </div>
                </div>
            </div>

            {{-- Receipt Message --}}
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-vital-darkred dark:text-red-400">📝 {{ __('admin.settings_page.receipt_title') }}</h3>

                <div class="field-3d mt-5">
                    <input type="text" name="thank_you_message" value="{{ old('thank_you_message', $settings['invoice.thank_you_message'] ?? '') }}" placeholder="{{ __('admin.settings_page.receipt_placeholder') }}" class="input-3d text-center text-sm">
                </div>
            </div>
        </div>

        {{-- TAB 2: BUSINESS RULES --}}
        <div class="tab-content space-y-4 hidden" id="business">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-emerald-700 dark:text-emerald-400">📊 {{ __('admin.settings_page.thresholds_title') }}</h3>
                <p class="mt-1 text-center text-xs text-slate-500">{{ __('admin.settings_page.thresholds_sub') }}</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.shift_cash_threshold') }}</label>
                        <input type="number" step="0.01" name="shift_variance_threshold" value="{{ old('shift_variance_threshold', $settings['business.shift_variance_threshold'] ?? '100.00') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.meter_tolerance') }}</label>
                        <input type="number" step="0.001" name="meter_variance_tolerance" value="{{ old('meter_variance_tolerance', $settings['business.meter_variance_tolerance'] ?? '0.500') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.overdue_days') }}</label>
                        <input type="number" name="overdue_credit_days" value="{{ old('overdue_credit_days', $settings['business.credit_overdue_days'] ?? '30') }}" class="input-3d text-center text-sm">
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.auto_close') }}</label>
                        <input type="time" name="auto_shift_close_time" value="{{ old('auto_shift_close_time', $settings['business.auto_shift_close_time'] ?? '23:00') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.low_stock_threshold') }}</label>
                        <input type="number" step="0.01" name="low_stock_threshold_liters" value="{{ old('low_stock_threshold_liters', $settings['business.low_stock_threshold_liters'] ?? '500.00') }}" class="input-3d text-center text-sm">
                    </div>
                </div>

                <div class="field-3d mt-4">
                    <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.payment_methods') }}</label>
                    <input type="text" name="payment_methods_enabled" value="{{ old('payment_methods_enabled', $settings['business.payment_methods_enabled'] ?? 'cash,card,bank,mobile,credit') }}" placeholder="cash,card,bank,mobile,credit" class="input-3d text-center text-sm">
                    <p class="mt-1.5 text-center text-xs text-slate-400">{{ __('admin.settings_page.payment_methods_hint') }}</p>
                </div>
            </div>
        </div>

        {{-- TAB 3: NOTIFICATIONS & SMS --}}
        <div class="tab-content space-y-4 hidden" id="notifications">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-sky-700 dark:text-sky-400">📱 {{ __('admin.settings_page.sms_title') }}</h3>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.sms_provider') }}</label>
                        <select name="sms_provider" class="input-3d text-center text-sm">
                            <option value="jazz" {{ old('sms_provider', $settings['notifications.sms_provider'] ?? 'jazz') === 'jazz' ? 'selected' : '' }}>Jazz (Mobilink)</option>
                            <option value="zong" {{ old('sms_provider', $settings['notifications.sms_provider'] ?? 'jazz') === 'zong' ? 'selected' : '' }}>Zong (PTCL)</option>
                            <option value="telenor" {{ old('sms_provider', $settings['notifications.sms_provider'] ?? 'jazz') === 'telenor' ? 'selected' : '' }}>Telenor</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.api_key') }}</label>
                        <input type="password" name="sms_api_key" value="{{ old('sms_api_key', $settings['notifications.sms_api_key'] ?? '') }}" placeholder="{{ __('admin.settings_page.api_key_ph') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.sender_id') }}</label>
                        <input type="text" name="sms_sender_id" value="{{ old('sms_sender_id', $settings['notifications.sms_sender_id'] ?? 'MEHAR') }}" maxlength="11" class="input-3d text-center text-sm">
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="text-center text-sm font-black text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.alert_types') }}</h4>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="low_stock_alert_enabled" value="1" {{ old('low_stock_alert_enabled', $settings['notifications.low_stock_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">🪣 {{ __('admin.settings_page.alert_low_stock') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="variance_alert_enabled" value="1" {{ old('variance_alert_enabled', $settings['notifications.variance_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">⚠️ {{ __('admin.settings_page.alert_variance') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="overdue_credit_alert_enabled" value="1" {{ old('overdue_credit_alert_enabled', $settings['notifications.overdue_credit_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">💳 {{ __('admin.settings_page.alert_overdue') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="pending_approval_alert_enabled" value="1" {{ old('pending_approval_alert_enabled', $settings['notifications.pending_approval_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">✋ {{ __('admin.settings_page.alert_pending') }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="text-center text-sm font-black text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.delivery_methods') }}</h4>
                    <div class="mt-3 flex flex-wrap justify-center gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="email_enabled" value="1" {{ old('email_enabled', $settings['notifications.email_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📧 {{ __('admin.settings_page.email') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="sms_enabled" value="1" {{ old('sms_enabled', $settings['notifications.sms_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📱 {{ __('admin.settings_page.sms') }}</span>
                        </label>
                    </div>
                    <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">🔒 {{ __('admin.settings_page.otp_email_only_note') }}</p>
                </div>

                <div class="mt-6">
                    <h4 class="text-center text-sm font-black text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.pin_login_otp') }}</h4>
                    <div class="mt-3 flex flex-wrap justify-center gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="hidden" name="pin_login_otp" value="0">
                            <input type="checkbox" name="pin_login_otp" value="1" {{ (float) ($settings['notifications.pin_login_otp'] ?? 0) > 0 ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">🔐 {{ __('admin.settings_page.pin_login_otp_label') }}</span>
                        </label>
                    </div>
                    <p class="mt-2 text-center text-[11px] text-slate-400">{{ __('admin.settings_page.pin_login_otp_help') }}</p>
                </div>
            </div>
        </div>

        {{-- TAB 4: REPORTS --}}
        <div class="tab-content space-y-4 hidden" id="reports">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-purple-700 dark:text-purple-400">📈 {{ __('admin.settings_page.reports_title') }}</h3>

                <div class="mt-5">
                    <h4 class="mb-3 text-center text-sm font-black text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.enabled_periods') }}</h4>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="auto_report_12h" value="1" {{ old('auto_report_12h', $settings['reports.auto_report_12h'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">⏰ {{ __('admin.settings_page.period_12h') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="auto_report_24h" value="1" {{ old('auto_report_24h', $settings['reports.auto_report_24h'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📅 {{ __('admin.settings_page.period_24h') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="auto_report_7d" value="1" {{ old('auto_report_7d', $settings['reports.auto_report_7d'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📆 {{ __('admin.settings_page.period_7d') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="auto_report_15d" value="1" {{ old('auto_report_15d', $settings['reports.auto_report_15d'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📊 {{ __('admin.settings_page.period_15d') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="auto_report_30d" value="1" {{ old('auto_report_30d', $settings['reports.auto_report_30d'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📈 {{ __('admin.settings_page.period_30d') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="daily_closing_enabled" value="1" {{ old('daily_closing_enabled', $settings['reports.daily_closing_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">🌙 {{ __('admin.settings_page.daily_closing') }}</span>
                        </label>
                    </div>
                </div>

                <div class="mx-auto mt-5 max-w-sm">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.report_send_time') }}</label>
                        <input type="time" name="report_send_time" value="{{ old('report_send_time', $settings['reports.report_send_time'] ?? '23:00') }}" class="input-3d text-center text-sm">
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="mb-3 text-center text-sm font-black text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.delivery_methods') }}</h4>
                    <div class="flex flex-wrap justify-center gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="report_send_via_email" value="1" {{ old('report_send_via_email', $settings['reports.report_send_via_email'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📧 {{ __('admin.settings_page.email') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="report_send_via_sms" value="1" {{ old('report_send_via_sms', $settings['reports.report_send_via_sms'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📱 {{ __('admin.settings_page.sms') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 5: EMAIL (SMTP) --}}
        <div class="tab-content space-y-4 hidden" id="email">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-orange-700 dark:text-orange-400">📧 {{ __('admin.settings_page.email_title') }}</h3>
                <p class="mt-1 text-center text-xs text-slate-500">{{ __('admin.settings_page.email_sub') }}</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.mail_driver') }}</label>
                        <select name="mail_driver" class="input-3d text-center text-sm">
                            <option value="smtp" {{ old('mail_driver', $settings['email.mail_driver'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ old('mail_driver', $settings['email.mail_driver'] ?? 'smtp') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            <option value="mailgun" {{ old('mail_driver', $settings['email.mail_driver'] ?? 'smtp') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.smtp_host') }}</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['email.mail_host'] ?? 'smtp.gmail.com') }}" placeholder="smtp.gmail.com" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.smtp_port') }}</label>
                        <input type="number" name="mail_port" value="{{ old('mail_port', $settings['email.mail_port'] ?? '587') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.encryption') }}</label>
                        <select name="mail_encryption" class="input-3d text-center text-sm">
                            <option value="tls" {{ old('mail_encryption', $settings['email.mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('mail_encryption', $settings['email.mail_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.email_address') }}</label>
                        <input type="email" name="mail_username" value="{{ old('mail_username', $settings['email.mail_username'] ?? '') }}" placeholder="your-email@gmail.com" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.password') }}</label>
                        <input type="password" name="mail_password" value="{{ old('mail_password', $settings['email.mail_password'] ?? '') }}" placeholder="{{ __('admin.settings_page.password_ph') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d sm:col-span-2">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.from_address') }}</label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['email.mail_from_address'] ?? '') }}" placeholder="noreply@meharfilling.com" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d sm:col-span-2">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.from_name') }}</label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['email.mail_from_name'] ?? '') }}" placeholder="Mehar Filling Station" class="input-3d text-center text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 6: THEME & UI --}}
        <div class="tab-content space-y-4 hidden" id="theme">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-pink-700 dark:text-pink-400">🌐 {{ __('ui.settings.site_language') }}</h3>
                <p class="mt-1 text-center text-xs text-slate-500">{{ __('ui.settings.site_language_help') }}</p>

                <div class="field-3d mx-auto mt-5 max-w-sm">
                    <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.topbar.language') }}</label>
                    <select name="ui_language" class="input-3d text-center text-sm">
                        <option value="en" {{ old('ui_language', $settings['ui_language'] ?? 'en') === 'en' ? 'selected' : '' }}>{{ __('ui.settings.english') }}</option>
                        <option value="ur" {{ old('ui_language', $settings['ui_language'] ?? 'en') === 'ur' ? 'selected' : '' }}>{{ __('ui.settings.urdu') }}</option>
                    </select>
                </div>
            </div>

            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-pink-700 dark:text-pink-400">🎨 {{ __('admin.settings_page.theme_title') }}</h3>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.primary_color') }}</label>
                        <div class="flex items-center justify-center gap-2">
                            <input type="color" name="theme_primary_color" value="{{ old('theme_primary_color', $settings['theme.theme_primary_color'] ?? '#D71920') }}" class="h-11 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 shadow-sm dark:border-slate-600">
                            <input type="text" value="{{ old('theme_primary_color', $settings['theme.theme_primary_color'] ?? '#D71920') }}" disabled class="input-3d flex-1 text-center text-sm opacity-70">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.secondary_color') }}</label>
                        <div class="flex items-center justify-center gap-2">
                            <input type="color" name="theme_secondary_color" value="{{ old('theme_secondary_color', $settings['theme.theme_secondary_color'] ?? '#27AE60') }}" class="h-11 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 shadow-sm dark:border-slate-600">
                            <input type="text" value="{{ old('theme_secondary_color', $settings['theme.theme_secondary_color'] ?? '#27AE60') }}" disabled class="input-3d flex-1 text-center text-sm opacity-70">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.accent_color') }}</label>
                        <div class="flex items-center justify-center gap-2">
                            <input type="color" name="theme_accent_color" value="{{ old('theme_accent_color', $settings['theme.theme_accent_color'] ?? '#FFFFFF') }}" class="h-11 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 shadow-sm dark:border-slate-600">
                            <input type="text" value="{{ old('theme_accent_color', $settings['theme.theme_accent_color'] ?? '#FFFFFF') }}" disabled class="input-3d flex-1 text-center text-sm opacity-70">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.text_color') }}</label>
                        <div class="flex items-center justify-center gap-2">
                            <input type="color" name="theme_text_color" value="{{ old('theme_text_color', $settings['theme.theme_text_color'] ?? '#1B1B1B') }}" class="h-11 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 shadow-sm dark:border-slate-600">
                            <input type="text" value="{{ old('theme_text_color', $settings['theme.theme_text_color'] ?? '#1B1B1B') }}" disabled class="input-3d flex-1 text-center text-sm opacity-70">
                        </div>
                    </div>
                    <div class="field-3d sm:col-span-2">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.bg_color') }}</label>
                        <div class="flex items-center justify-center gap-2">
                            <input type="color" name="theme_dark_bg" value="{{ old('theme_dark_bg', $settings['theme.theme_dark_bg'] ?? '#F6F6F6') }}" class="h-11 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 shadow-sm dark:border-slate-600">
                            <input type="text" value="{{ old('theme_dark_bg', $settings['theme.theme_dark_bg'] ?? '#F6F6F6') }}" disabled class="input-3d flex-1 text-center text-sm opacity-70">
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="text-center text-sm font-black text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.animation_title') }}</h4>
                    <div class="mt-3 flex flex-wrap justify-center gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="dashboard_animation_enabled" value="1" {{ old('dashboard_animation_enabled', $settings['theme.dashboard_animation_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.dashboard_animations') }}</span>
                        </label>
                    </div>
                    <div class="field-3d mx-auto mt-4 max-w-xs">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.animation_speed') }}</label>
                        <select name="animation_speed" class="input-3d text-center text-sm">
                            <option value="slow" {{ old('animation_speed', $settings['theme.animation_speed'] ?? 'medium') === 'slow' ? 'selected' : '' }}>{{ __('admin.settings_page.speed_slow') }}</option>
                            <option value="medium" {{ old('animation_speed', $settings['theme.animation_speed'] ?? 'medium') === 'medium' ? 'selected' : '' }}>{{ __('admin.settings_page.speed_medium') }}</option>
                            <option value="fast" {{ old('animation_speed', $settings['theme.animation_speed'] ?? 'medium') === 'fast' ? 'selected' : '' }}>{{ __('admin.settings_page.speed_fast') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 7: PRINTING --}}
        <div class="tab-content space-y-4 hidden" id="printing">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-cyan-700 dark:text-cyan-400">🖨️ {{ __('admin.settings_page.printing_title') }}</h3>

                <div class="mt-5">
                    <div class="flex flex-wrap justify-center gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="thermal_printer_enabled" value="1" {{ old('thermal_printer_enabled', $settings['printing.thermal_printer_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.thermal_printer') }}</span>
                        </label>
                    </div>

                    <div class="field-3d mx-auto mt-4 max-w-xs">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.paper_width') }}</label>
                        <select name="thermal_paper_width" class="input-3d text-center text-sm">
                            <option value="58" {{ old('thermal_paper_width', $settings['printing.thermal_paper_width'] ?? '80') == '58' ? 'selected' : '' }}>58 mm</option>
                            <option value="80" {{ old('thermal_paper_width', $settings['printing.thermal_paper_width'] ?? '80') == '80' ? 'selected' : '' }}>{{ __('admin.settings_page.paper_80') }}</option>
                        </select>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="print_logo" value="1" {{ old('print_logo', $settings['printing.print_logo'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.print_logo') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="print_qr_code" value="1" {{ old('print_qr_code', $settings['printing.print_qr_code'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.print_qr') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" name="print_fbr_sms" value="1" {{ old('print_fbr_sms', $settings['printing.print_fbr_sms'] ?? '1') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.print_fbr_sms') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 8: TAX --}}
        <div class="tab-content space-y-4 hidden" id="tax">
            <div class="glass-card p-6">
                <h3 class="text-center text-lg font-black text-indigo-700 dark:text-indigo-400">💰 {{ __('admin.settings_page.tax_title') }}</h3>

                <div class="mt-5 flex justify-center">
                    <label class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                        <input type="hidden" name="fbr_invoicing_enabled" value="0">
                        <input type="checkbox" name="fbr_invoicing_enabled" value="1" {{ old('fbr_invoicing_enabled', $settings['tax.fbr_invoicing_enabled'] ?? '0') == '1' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('admin.settings_page.fbr_enable') }}</span>
                    </label>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.provincial_rate') }}</label>
                        <input type="number" step="0.01" name="provincial_tax_rate" value="{{ old('provincial_tax_rate', $settings['tax.provincial_tax_rate'] ?? '16.00') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.sales_tax_fuel') }}</label>
                        <input type="number" step="0.01" name="sales_tax_rate_fuel" value="{{ old('sales_tax_rate_fuel', $settings['tax.sales_tax_rate_fuel'] ?? '17.00') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.sales_tax_nonfuel') }}</label>
                        <input type="number" step="0.01" name="sales_tax_rate_nonfu" value="{{ old('sales_tax_rate_nonfu', $settings['tax.sales_tax_rate_nonfu'] ?? '17.00') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.withholding') }}</label>
                        <input type="number" step="0.01" name="withholding_tax_rate" value="{{ old('withholding_tax_rate', $settings['tax.withholding_tax_rate'] ?? '2.00') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.settings_page.pos_fee') }}</label>
                        <input type="number" step="0.01" name="pos_service_fee_rate" value="{{ old('pos_service_fee_rate', $settings['tax.pos_service_fee_rate'] ?? '1.00') }}" class="input-3d text-center text-sm">
                        <p class="mt-1.5 text-center text-xs text-slate-400">SRO 1006(I)/2021</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAVE BUTTON --}}
        <div class="glass-card sticky bottom-0 flex flex-wrap justify-center gap-3 p-4">
            <a href="javascript:history.back()" class="btn-3d btn-3d-ghost">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('ui.actions.cancel') }}
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('admin.settings_page.save_all_settings') }}
            </button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const tabName = this.dataset.tab;

        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));

        // Remove active state from all buttons
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('border-red-600', 'bg-red-50', 'text-red-700', 'dark:bg-red-950/30', 'dark:text-red-400');
            b.classList.add('border-slate-300', 'text-slate-700', 'dark:border-slate-700', 'dark:text-slate-400');
        });

        // Show selected tab and mark button active
        document.getElementById(tabName).classList.remove('hidden');
        this.classList.add('border-red-600', 'bg-red-50', 'text-red-700', 'dark:bg-red-950/30', 'dark:text-red-400');
        this.classList.remove('border-slate-300', 'text-slate-700', 'dark:border-slate-700', 'dark:text-slate-400');
    });
});
</script>

@endsection
