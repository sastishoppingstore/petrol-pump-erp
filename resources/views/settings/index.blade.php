@extends('layouts.app')

@section('title', 'ترتیبات / Settings')
@section('breadcrumb')
    <li class="text-slate-500">Settings</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-red-600 dark:text-red-400">⚙️ اسٹیشن ترتیبات / Station Settings</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Mehar Filling Station (مہر فلنگ اسٹیشن) - Complete admin control: identity, pricing, alerts, reports, theme & more.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('settings.bill-designer') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-red-600 to-red-700 px-4 py-2 text-sm font-semibold text-white shadow-md hover:shadow-lg hover:from-red-700 hover:to-red-800 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                بل ڈیزائنر / Bill Designer
            </a>
            <a href="{{ route('backups.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-green-600 to-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-md hover:shadow-lg transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                بیک اپ / Backups
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300 shadow-md animate-bounce-in">
            ✅ {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- TAB NAVIGATION --}}
        <div class="sticky top-0 z-10 -mx-6 mb-6 border-b border-slate-200 bg-white/95 backdrop-blur px-6 py-4 dark:border-slate-700 dark:bg-slate-900/95">
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button type="button" class="tab-btn active px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-red-600 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400" data-tab="station">
                    🏢 Station Identity
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="business">
                    📊 Business Rules
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="notifications">
                    🔔 Alerts & SMS
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="reports">
                    📈 Reports
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="email">
                    📧 Email (SMTP)
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="theme">
                    🎨 Theme & UI
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="printing">
                    🖨️ Printing
                </button>
                <button type="button" class="tab-btn px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap border-2 border-slate-300 text-slate-700 hover:border-red-400 dark:border-slate-700 dark:text-slate-400" data-tab="tax">
                    💰 Tax
                </button>
            </div>
        </div>

        {{-- TAB 1: STATION IDENTITY --}}
        <div class="tab-content active space-y-4" id="station">
            <div class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-6 shadow-sm dark:border-red-900/30 dark:from-red-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-red-700 dark:text-red-400">🏢 Station Identity (اسٹیشن کی شناخت)</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">یہ معلومات تمام بلوں، رسیدوں اور رپورٹوں پر پرنٹ ہوتی ہے۔</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">اسٹیشن کا نام (اردو) / Urdu Name</label>
                        <input type="text" name="station_name_ur" value="{{ old('station_name_ur', $settings['station.station_name_ur'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" dir="rtl" placeholder="مہر فلنگ اسٹیشن">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Station Name (English)</label>
                        <input type="text" name="station_name_en" value="{{ old('station_name_en', $settings['station.station_name_en'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Mehar Filling Station">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">مالک کا نام / Owner Name</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name', $settings['station.owner_name'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Muhammad Rizwan Aslam">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">فون نمبر / Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $settings['station.phone'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="0300-4342343">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">واٹس ایپ / WhatsApp</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $settings['station.whatsapp'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="wa.me/923004342343">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">او ایم سی برانڈ / OMC Brand</label>
                        <input type="text" name="omc_brand" value="{{ old('omc_brand', $settings['station.omc_brand'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Vital Petroleum">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">پتہ / Station Address</label>
                        <input type="text" name="address" value="{{ old('address', $settings['station.address'] ?? '') }}" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura">
                    </div>
                </div>
            </div>

            {{-- Tax & Legal Identity --}}
            <div class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-6 shadow-sm dark:border-red-900/30 dark:from-red-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-red-700 dark:text-red-400">📋 ٹیکس اور قانونی تفصیلات / Tax & Legal</h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">NTN نمبر</label>
                        <input type="text" name="ntn" value="{{ old('ntn', $settings['company.ntn'] ?? '') }}" placeholder="1234567-8" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">STRN / Sales Tax نمبر</label>
                        <input type="text" name="strn" value="{{ old('strn', $settings['company.strn'] ?? '') }}" placeholder="12-34-5678-901-23" class="mt-1 block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Receipt Message --}}
            <div class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-6 shadow-sm dark:border-red-900/30 dark:from-red-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-red-700 dark:text-red-400">📝 رسید کا الوداعی پیغام / Receipt Message</h3>

                <div class="mt-4">
                    <input type="text" name="thank_you_message" value="{{ old('thank_you_message', $settings['invoice.thank_you_message'] ?? '') }}" placeholder="Thank you for your patronage. Drive safely." class="block w-full rounded-lg border border-red-200 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
        </div>

        {{-- TAB 2: BUSINESS RULES --}}
        <div class="tab-content space-y-4 hidden" id="business">
            <div class="rounded-xl border border-green-200 bg-gradient-to-br from-green-50 to-white p-6 shadow-sm dark:border-green-900/30 dark:from-green-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-green-700 dark:text-green-400">📊 آپریشنل حدیں / Operational Thresholds</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">ان حدود سے زیادہ کا فرق ہونے پر مینیجر کی منظوری درکار ہوگی۔</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">شفٹ کیش فرق کی حد (روپے) / Shift Cash Threshold</label>
                        <input type="number" step="0.01" name="shift_variance_threshold" value="{{ old('shift_variance_threshold', $settings['business.shift_variance_threshold'] ?? '100.00') }}" class="mt-1 block w-full rounded-lg border border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">میٹر ریڈنگ فرق (لیٹر) / Meter Tolerance</label>
                        <input type="number" step="0.001" name="meter_variance_tolerance" value="{{ old('meter_variance_tolerance', $settings['business.meter_variance_tolerance'] ?? '0.500') }}" class="mt-1 block w-full rounded-lg border border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">ادھار زائد المیعاد (دن) / Overdue Credit Days</label>
                        <input type="number" name="overdue_credit_days" value="{{ old('overdue_credit_days', $settings['business.credit_overdue_days'] ?? '30') }}" class="mt-1 block w-full rounded-lg border border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">شفٹ خودکار بند وقت / Auto Shift Close Time</label>
                        <input type="time" name="auto_shift_close_time" value="{{ old('auto_shift_close_time', $settings['business.auto_shift_close_time'] ?? '23:00') }}" class="mt-1 block w-full rounded-lg border border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">کم سٹاک حد (لیٹر) / Low Stock Threshold</label>
                        <input type="number" step="0.01" name="low_stock_threshold_liters" value="{{ old('low_stock_threshold_liters', $settings['business.low_stock_threshold_liters'] ?? '500.00') }}" class="mt-1 block w-full rounded-lg border border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">فعال ادائیگی طریقے / Payment Methods Enabled</label>
                    <input type="text" name="payment_methods_enabled" value="{{ old('payment_methods_enabled', $settings['business.payment_methods_enabled'] ?? 'cash,card,bank,mobile,credit') }}" placeholder="cash,card,bank,mobile,credit" class="mt-1 block w-full rounded-lg border border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <p class="mt-1 text-xs text-slate-500">کوما سے الگ کریں (cash, card, bank, mobile, credit)</p>
                </div>
            </div>
        </div>

        {{-- TAB 3: NOTIFICATIONS & SMS --}}
        <div class="tab-content space-y-4 hidden" id="notifications">
            <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm dark:border-blue-900/30 dark:from-blue-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-blue-700 dark:text-blue-400">📱 SMS اور الرٹ ترتیبات / SMS & Alert Settings</h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">SMS فراہم کنندہ / SMS Provider</label>
                        <select name="sms_provider" class="mt-1 block w-full rounded-lg border border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="jazz" {{ old('sms_provider', $settings['notifications.sms_provider'] ?? 'jazz') === 'jazz' ? 'selected' : '' }}>Jazz (Mobilink)</option>
                            <option value="zong" {{ old('sms_provider', $settings['notifications.sms_provider'] ?? 'jazz') === 'zong' ? 'selected' : '' }}>Zong (PTCL)</option>
                            <option value="telenor" {{ old('sms_provider', $settings['notifications.sms_provider'] ?? 'jazz') === 'telenor' ? 'selected' : '' }}>Telenor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">SMS API کلید / API Key</label>
                        <input type="password" name="sms_api_key" value="{{ old('sms_api_key', $settings['notifications.sms_api_key'] ?? '') }}" placeholder="Your API key" class="mt-1 block w-full rounded-lg border border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Sender ID</label>
                        <input type="text" name="sms_sender_id" value="{{ old('sms_sender_id', $settings['notifications.sms_sender_id'] ?? 'MEHAR') }}" maxlength="11" class="mt-1 block w-full rounded-lg border border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm">الرٹ کی اقسام / Alert Types</h4>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="low_stock_alert_enabled" value="1" {{ old('low_stock_alert_enabled', $settings['notifications.low_stock_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">🪣 کم سٹاک الرٹ / Low Stock</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="variance_alert_enabled" value="1" {{ old('variance_alert_enabled', $settings['notifications.variance_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">⚠️ فرق کا الرٹ / Variance Alert</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="overdue_credit_alert_enabled" value="1" {{ old('overdue_credit_alert_enabled', $settings['notifications.overdue_credit_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">💳 ادھار زائل المیعاد / Overdue Credit</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pending_approval_alert_enabled" value="1" {{ old('pending_approval_alert_enabled', $settings['notifications.pending_approval_alert_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">✋ منظوری زیر التوا / Pending Approval</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm">ترجیحی ذریعے / Delivery Methods</h4>
                    <div class="mt-3 flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="email_enabled" value="1" {{ old('email_enabled', $settings['notifications.email_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📧 ای میل / Email</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="sms_enabled" value="1" {{ old('sms_enabled', $settings['notifications.sms_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📱 SMS</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 4: REPORTS --}}
        <div class="tab-content space-y-4 hidden" id="reports">
            <div class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-white p-6 shadow-sm dark:border-purple-900/30 dark:from-purple-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-purple-700 dark:text-purple-400">📈 خودکار رپورٹنگ / Auto-Report Settings</h3>

                <div class="mt-4">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm mb-3">فعال رپورٹ ادوار / Enabled Report Periods</h4>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_report_12h" value="1" {{ old('auto_report_12h', $settings['reports.auto_report_12h'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">⏰ 12 گھنٹے / 12 Hours</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_report_24h" value="1" {{ old('auto_report_24h', $settings['reports.auto_report_24h'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📅 24 گھنٹے / 24 Hours</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_report_7d" value="1" {{ old('auto_report_7d', $settings['reports.auto_report_7d'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📆 7 دن / 7 Days</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_report_15d" value="1" {{ old('auto_report_15d', $settings['reports.auto_report_15d'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📊 15 دن / 15 Days</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_report_30d" value="1" {{ old('auto_report_30d', $settings['reports.auto_report_30d'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📈 30 دن / 30 Days</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="daily_closing_enabled" value="1" {{ old('daily_closing_enabled', $settings['reports.daily_closing_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">🌙 روزمرہ بند ہونا / Daily Closing</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">رپورٹ بھیجنے کا وقت / Report Send Time</label>
                        <input type="time" name="report_send_time" value="{{ old('report_send_time', $settings['reports.report_send_time'] ?? '23:00') }}" class="mt-1 block w-full rounded-lg border border-purple-200 px-3 py-2 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm mb-3">تسلیم کے طریقے / Delivery Methods</h4>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="report_send_via_email" value="1" {{ old('report_send_via_email', $settings['reports.report_send_via_email'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📧 ای میل / Email</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="report_send_via_sms" value="1" {{ old('report_send_via_sms', $settings['reports.report_send_via_sms'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">📱 SMS</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 5: EMAIL (SMTP) --}}
        <div class="tab-content space-y-4 hidden" id="email">
            <div class="rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-sm dark:border-orange-900/30 dark:from-orange-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-orange-700 dark:text-orange-400">📧 SMTP ای میل ترتیبات / SMTP Email</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">خودکار الرٹس اور رپورٹس بھیجنے کے لیے۔ / For sending automated alerts and reports.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">ای میل ڈرائیور / Mail Driver</label>
                        <select name="mail_driver" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="smtp" {{ old('mail_driver', $settings['email.mail_driver'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ old('mail_driver', $settings['email.mail_driver'] ?? 'smtp') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            <option value="mailgun" {{ old('mail_driver', $settings['email.mail_driver'] ?? 'smtp') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['email.mail_host'] ?? 'smtp.gmail.com') }}" placeholder="smtp.gmail.com" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">SMTP Port</label>
                        <input type="number" name="mail_port" value="{{ old('mail_port', $settings['email.mail_port'] ?? '587') }}" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Encryption</label>
                        <select name="mail_encryption" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="tls" {{ old('mail_encryption', $settings['email.mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('mail_encryption', $settings['email.mail_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">ای میل پتہ / Email Address</label>
                        <input type="email" name="mail_username" value="{{ old('mail_username', $settings['email.mail_username'] ?? '') }}" placeholder="your-email@gmail.com" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">پاس ورڈ / Password</label>
                        <input type="password" name="mail_password" value="{{ old('mail_password', $settings['email.mail_password'] ?? '') }}" placeholder="Your app password" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">سے پتہ / From Address</label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['email.mail_from_address'] ?? '') }}" placeholder="noreply@meharfilling.com" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">سے نام / From Name</label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['email.mail_from_name'] ?? '') }}" placeholder="Mehar Filling Station" class="mt-1 block w-full rounded-lg border border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 6: THEME & UI --}}
        <div class="tab-content space-y-4 hidden" id="theme">
            <div class="rounded-xl border border-pink-200 bg-gradient-to-br from-pink-50 to-white p-6 shadow-sm dark:border-pink-900/30 dark:from-pink-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-pink-700 dark:text-pink-400">🎨 تھیم اور رنگ / Theme Colors (Red/White/Green)</h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">بنیادی رنگ / Primary Color</label>
                        <div class="mt-1 flex gap-2">
                            <input type="color" name="theme_primary_color" value="{{ old('theme_primary_color', $settings['theme.theme_primary_color'] ?? '#D71920') }}" class="h-10 w-16 rounded-lg border border-pink-200 cursor-pointer">
                            <input type="text" value="{{ old('theme_primary_color', $settings['theme.theme_primary_color'] ?? '#D71920') }}" disabled class="flex-1 rounded-lg border border-pink-200 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">ثانوی رنگ / Secondary Color (Green)</label>
                        <div class="mt-1 flex gap-2">
                            <input type="color" name="theme_secondary_color" value="{{ old('theme_secondary_color', $settings['theme.theme_secondary_color'] ?? '#27AE60') }}" class="h-10 w-16 rounded-lg border border-pink-200 cursor-pointer">
                            <input type="text" value="{{ old('theme_secondary_color', $settings['theme.theme_secondary_color'] ?? '#27AE60') }}" disabled class="flex-1 rounded-lg border border-pink-200 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">لہجہ رنگ / Accent Color (White)</label>
                        <div class="mt-1 flex gap-2">
                            <input type="color" name="theme_accent_color" value="{{ old('theme_accent_color', $settings['theme.theme_accent_color'] ?? '#FFFFFF') }}" class="h-10 w-16 rounded-lg border border-pink-200 cursor-pointer">
                            <input type="text" value="{{ old('theme_accent_color', $settings['theme.theme_accent_color'] ?? '#FFFFFF') }}" disabled class="flex-1 rounded-lg border border-pink-200 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">متن کا رنگ / Text Color</label>
                        <div class="mt-1 flex gap-2">
                            <input type="color" name="theme_text_color" value="{{ old('theme_text_color', $settings['theme.theme_text_color'] ?? '#1B1B1B') }}" class="h-10 w-16 rounded-lg border border-pink-200 cursor-pointer">
                            <input type="text" value="{{ old('theme_text_color', $settings['theme.theme_text_color'] ?? '#1B1B1B') }}" disabled class="flex-1 rounded-lg border border-pink-200 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">بیک گراؤنڈ رنگ / Background Color</label>
                        <div class="mt-1 flex gap-2">
                            <input type="color" name="theme_dark_bg" value="{{ old('theme_dark_bg', $settings['theme.theme_dark_bg'] ?? '#F6F6F6') }}" class="h-10 w-16 rounded-lg border border-pink-200 cursor-pointer">
                            <input type="text" value="{{ old('theme_dark_bg', $settings['theme.theme_dark_bg'] ?? '#F6F6F6') }}" disabled class="flex-1 rounded-lg border border-pink-200 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm mb-3">اینیمیشن ترتیبات / Animation Settings</h4>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="dashboard_animation_enabled" value="1" {{ old('dashboard_animation_enabled', $settings['theme.dashboard_animation_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">ڈیش بورڈ اینیمیشن / Dashboard Animations</span>
                        </label>
                    </div>
                    <div class="mt-3">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">رفتار / Animation Speed</label>
                        <select name="animation_speed" class="mt-1 block w-full rounded-lg border border-pink-200 px-3 py-2 text-sm focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white sm:w-48">
                            <option value="slow" {{ old('animation_speed', $settings['theme.animation_speed'] ?? 'medium') === 'slow' ? 'selected' : '' }}>سست / Slow</option>
                            <option value="medium" {{ old('animation_speed', $settings['theme.animation_speed'] ?? 'medium') === 'medium' ? 'selected' : '' }}>درمیاني / Medium</option>
                            <option value="fast" {{ old('animation_speed', $settings['theme.animation_speed'] ?? 'medium') === 'fast' ? 'selected' : '' }}>تیز / Fast</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 7: PRINTING --}}
        <div class="tab-content space-y-4 hidden" id="printing">
            <div class="rounded-xl border border-cyan-200 bg-gradient-to-br from-cyan-50 to-white p-6 shadow-sm dark:border-cyan-900/30 dark:from-cyan-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-cyan-700 dark:text-cyan-400">🖨️ پرنٹنگ ترتیبات / Printing Settings</h3>

                <div class="mt-4">
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="thermal_printer_enabled" value="1" {{ old('thermal_printer_enabled', $settings['printing.thermal_printer_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">تھرمل پرنٹر / Thermal Printer</span>
                        </label>
                    </div>

                    <div class="mt-3">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">کاغذ کی چوڑائی (ملی میٹر) / Paper Width</label>
                        <select name="thermal_paper_width" class="mt-1 block w-full rounded-lg border border-cyan-200 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white sm:w-48">
                            <option value="58" {{ old('thermal_paper_width', $settings['printing.thermal_paper_width'] ?? '80') == '58' ? 'selected' : '' }}>58 mm</option>
                            <option value="80" {{ old('thermal_paper_width', $settings['printing.thermal_paper_width'] ?? '80') == '80' ? 'selected' : '' }}>80 mm (معیاری / Standard)</option>
                        </select>
                    </div>

                    <div class="mt-3 flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="print_logo" value="1" {{ old('print_logo', $settings['printing.print_logo'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">لوگو پرنٹ کریں / Print Logo</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="print_qr_code" value="1" {{ old('print_qr_code', $settings['printing.print_qr_code'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">QR کوڈ پرنٹ کریں / Print QR Code</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="print_fbr_sms" value="1" {{ old('print_fbr_sms', $settings['printing.print_fbr_sms'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                            <span class="text-sm text-slate-700 dark:text-slate-300">FBR SMS پرنٹ کریں / Print FBR SMS</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 8: TAX --}}
        <div class="tab-content space-y-4 hidden" id="tax">
            <div class="rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-6 shadow-sm dark:border-indigo-900/30 dark:from-indigo-950/20 dark:to-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-bold text-indigo-700 dark:text-indigo-400">💰 ٹیکس ترتیبات / Tax Settings</h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-lg border border-indigo-200 p-3 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-950/20">
                        <input type="checkbox" name="fbr_enabled" value="1" {{ old('fbr_enabled', $settings['tax.fbr_enabled'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">FBR ڈیجیٹل انوائسنگ فعال کریں / FBR Digital Invoicing</span>
                    </label>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">صوبائی ٹیکس کی شرح (%) / Provincial Tax Rate</label>
                        <input type="number" step="0.01" name="provincial_tax_rate" value="{{ old('provincial_tax_rate', $settings['tax.provincial_tax_rate'] ?? '16.00') }}" class="mt-1 block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">بیکار ٹیکس کی شرح (%) / Sales Tax Rate (Fuel)</label>
                        <input type="number" step="0.01" name="sales_tax_rate_fuel" value="{{ old('sales_tax_rate_fuel', $settings['tax.sales_tax_rate_fuel'] ?? '17.00') }}" class="mt-1 block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">غیر ایندھن ٹیکس کی شرح (%) / Sales Tax Rate (Non-Fuel)</label>
                        <input type="number" step="0.01" name="sales_tax_rate_nonfu" value="{{ old('sales_tax_rate_nonfu', $settings['tax.sales_tax_rate_nonfu'] ?? '17.00') }}" class="mt-1 block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">سے روکا ہوا ٹیکس (%) / Withholding Tax</label>
                        <input type="number" step="0.01" name="withholding_tax_rate" value="{{ old('withholding_tax_rate', $settings['tax.withholding_tax_rate'] ?? '2.00') }}" class="mt-1 block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">POS سروس فیس (Rs.) / POS Service Fee</label>
                        <input type="number" step="0.01" name="pos_service_fee_rate" value="{{ old('pos_service_fee_rate', $settings['tax.pos_service_fee_rate'] ?? '1.00') }}" class="mt-1 block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <p class="mt-1 text-xs text-slate-500">SRO 1006(I)/2021</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAVE BUTTON --}}
        <div class="sticky bottom-0 flex justify-end gap-2 bg-white/95 backdrop-blur px-6 py-4 border-t border-slate-200 dark:bg-slate-900/95 dark:border-slate-700 -mx-6">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 rounded-lg bg-slate-200 px-6 py-2.5 text-sm font-bold text-slate-700 shadow-md hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                منسوخ کریں / Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-red-600 to-red-700 px-8 py-2.5 text-sm font-bold text-white shadow-lg hover:shadow-xl hover:from-red-700 hover:to-red-800 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                تبدیلیاں محفوظ کریں / Save All Settings
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
