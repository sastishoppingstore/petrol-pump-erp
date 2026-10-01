@extends('layouts.app')

@section('content')
{{--
    Admin System Settings — 2026 redesign (shell/cards only).
    SAKHT NOTE: ye page apni AJAX save istemal karta hai — tamam data-key
    attributes, JS hook classes (setting-input / setting-toggle /
    setting-select), form id (settingsForm), .last-update-* spans,
    #saveSuccess indicator aur neeche wala script BILKUL pehle jaisa hai.
    Sirf markup/styling 3D/glass design system par le jayi gayi hai.
    (.d-none ka chhota sa rule isi liye rakha hai kyunki save indicator ka
    show/hide JS usi class ko toggle karta hai.)
--}}
<div class="space-y-6">
    <div class="page-head">
        <h1>⚙️ {{ __('admin.system_settings.title') }}</h1>
        <p>{{ __('admin.system_settings.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('admin.settings.audit') }}" class="btn-3d btn-3d-ghost">
                📋 {{ __('admin.system_settings.view_history') }}
            </a>
        </div>
    </div>

    <!-- Category Navigation -->
    <div class="glass-card flex flex-wrap justify-center gap-2 p-3" role="tablist">
        @foreach ($categories as $cat_key => $cat_label)
            <button class="rounded-full px-4 py-2 text-sm font-bold transition {{ $cat_key === $activeCategory ? 'bg-gradient-to-b from-vital-primary to-vital-darkred text-white shadow-glow' : 'bg-slate-900/5 text-slate-600 hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300' }}"
                    onclick="window.location.href='?category={{ $cat_key }}'"
                    type="button">
                {{ $cat_label }}
            </button>
        @endforeach
    </div>

    <!-- Settings Content -->
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="glass-card p-6">
                <form id="settingsForm" class="settings-form">
                    @csrf

                    @if ($activeCategory === 'fuel')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">⛽ {{ __('admin.system_settings.fuel_title') }}</h5>

                            @foreach (['fuel_petrol_price' => __('admin.system_settings.fuel_petrol'), 'fuel_diesel_price' => __('admin.system_settings.fuel_diesel'), 'fuel_hsd_price' => __('admin.system_settings.fuel_hsd'), 'fuel_octane_price' => __('admin.system_settings.fuel_octane')] as $key => $label)
                                <div class="field-3d mx-auto mb-4 max-w-md">
                                    <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</label>
                                    <div class="flex items-stretch gap-2">
                                        <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">Rs</span>
                                        <input type="number"
                                               step="0.01"
                                               class="input-3d setting-input text-center"
                                               data-key="{{ $key }}"
                                               value="{{ $allSettings[$key]['value'] ?? 0 }}"
                                               placeholder="0.00">
                                    </div>
                                    <small class="mt-1 block text-center text-xs text-slate-400">{{ __('admin.system_settings.last_updated') }}
                                        <span class="last-update-{{ $key }}">—</span>
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($activeCategory === 'tax')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">💰 {{ __('admin.system_settings.tax_title') }}</h5>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.gst_rate') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tax_gst_rate"
                                           value="{{ $allSettings['tax_gst_rate']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">%</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.fbr_rate') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tax_frb_rate"
                                           value="{{ $allSettings['tax_frb_rate']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">%</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.provincial_tax') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tax_provincial_rate"
                                           value="{{ $allSettings['tax_provincial_rate']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">%</span>
                                </div>
                            </div>

                            <div class="mx-auto mb-4 max-w-md">
                                <label for="frbEnabled" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                    <input type="checkbox" class="setting-toggle h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                           data-key="frb_enabled" id="frbEnabled"
                                           {{ $allSettings['frb_enabled']['value'] ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ __('admin.system_settings.fbr_enable') }}
                                        <span class="block text-xs font-normal text-slate-400">{{ __('admin.system_settings.fbr_enable_sub') }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'variance')
                        <div class="settings-section">
                            <h5 class="mb-2 text-center text-base font-black text-slate-800 dark:text-slate-100">⚠️ {{ __('admin.system_settings.variance_title') }}</h5>
                            <p class="mb-5 text-center text-xs text-slate-400">{{ __('admin.system_settings.variance_sub') }}</p>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.shift_cash_variance') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">Rs</span>
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="shift_cash_variance_threshold"
                                           value="{{ $allSettings['shift_cash_variance_threshold']['value'] }}">
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.meter_variance') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="meter_variance_threshold"
                                           value="{{ $allSettings['meter_variance_threshold']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">L</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.tank_variance') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tank_variance_threshold"
                                           value="{{ $allSettings['tank_variance_threshold']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">L</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.low_stock_alert') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="low_stock_threshold"
                                           value="{{ $allSettings['low_stock_threshold']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">L</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'notifications')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">🔔 {{ __('admin.system_settings.notif_title') }}</h5>

                            <div class="mx-auto mb-4 grid max-w-xl gap-3 sm:grid-cols-2">
                                <label for="smsEnabled" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                    <input type="checkbox" class="setting-toggle h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                           data-key="sms_enabled" id="smsEnabled"
                                           {{ $allSettings['sms_enabled']['value'] ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ __('admin.system_settings.enable_sms') }}
                                        <span class="block text-xs font-normal text-slate-400">{{ __('admin.system_settings.enable_sms_sub') }}</span>
                                    </span>
                                </label>

                                <label for="emailEnabled" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                    <input type="checkbox" class="setting-toggle h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                           data-key="email_enabled" id="emailEnabled"
                                           {{ $allSettings['email_enabled']['value'] ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ __('admin.system_settings.enable_email') }}
                                        <span class="block text-xs font-normal text-slate-400">{{ __('admin.system_settings.enable_email_sub') }}</span>
                                    </span>
                                </label>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.sms_provider') }}</label>
                                <select class="input-3d setting-select text-center" data-key="sms_provider">
                                    <option value="jazz" {{ $allSettings['sms_provider']['value'] === 'jazz' ? 'selected' : '' }}>Jazz (Mobilink)</option>
                                    <option value="zong" {{ $allSettings['sms_provider']['value'] === 'zong' ? 'selected' : '' }}>Zong</option>
                                    <option value="telenor" {{ $allSettings['sms_provider']['value'] === 'telenor' ? 'selected' : '' }}>Telenor</option>
                                </select>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.sms_api_key') }}</label>
                                <input type="password" class="input-3d setting-input text-center"
                                       data-key="sms_api_key"
                                       value="{{ $allSettings['sms_api_key']['value'] }}"
                                       placeholder="{{ __('admin.system_settings.api_key_placeholder') }}">
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.alert_email') }}</label>
                                <input type="email" class="input-3d setting-input text-center"
                                       data-key="alert_admin_email"
                                       value="{{ $allSettings['alert_admin_email']['value'] }}"
                                       placeholder="admin@example.com">
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.alert_phone') }}</label>
                                <input type="text" class="input-3d setting-input text-center"
                                       data-key="alert_admin_phone"
                                       value="{{ $allSettings['alert_admin_phone']['value'] }}"
                                       placeholder="+923001234567">
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'reports')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">📊 {{ __('admin.system_settings.reports_title') }}</h5>

                            <div class="mx-auto mb-5 max-w-xl">
                                <h6 class="mb-3 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.report_schedules') }}</h6>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ([
                                        'auto_report_12h' => __('admin.system_settings.rep_12h'),
                                        'auto_report_24h' => __('admin.system_settings.rep_24h'),
                                        'auto_report_7d' => __('admin.system_settings.rep_7d'),
                                        'auto_report_15d' => __('admin.system_settings.rep_15d'),
                                        'auto_report_30d' => __('admin.system_settings.rep_30d')
                                    ] as $key => $label)
                                        <label for="{{ $key }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                            <input type="checkbox" class="setting-toggle h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                                   data-key="{{ $key }}" id="{{ $key }}"
                                                   {{ $allSettings[$key]['value'] ? 'checked' : '' }}>
                                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-5 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.report_send_time') }}</label>
                                <input type="time" class="input-3d setting-input text-center"
                                       data-key="report_send_time"
                                       value="{{ $allSettings['report_send_time']['value'] }}">
                                <small class="mt-1 block text-center text-xs text-slate-400">{{ __('admin.system_settings.report_send_time_hint') }}</small>
                            </div>

                            <div class="mx-auto max-w-xl">
                                <h6 class="mb-3 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.report_formats') }}</h6>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label for="reportPdf" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                        <input type="checkbox" class="setting-toggle h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                               data-key="report_include_pdf" id="reportPdf"
                                               {{ $allSettings['report_include_pdf']['value'] ? 'checked' : '' }}>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('admin.system_settings.include_pdf') }}</span>
                                    </label>
                                    <label for="reportExcel" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                        <input type="checkbox" class="setting-toggle h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                               data-key="report_include_excel" id="reportExcel"
                                               {{ $allSettings['report_include_excel']['value'] ? 'checked' : '' }}>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('admin.system_settings.include_excel') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'remember')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">💾 {{ __('admin.system_settings.remember_title') }}</h5>

                            <div class="mx-auto mb-5 max-w-xl">
                                <h6 class="mb-3 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.remember_what') }}</h6>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ([
                                        'remember_low_stock' => __('admin.system_settings.rem_low_stock'),
                                        'remember_variance_alerts' => __('admin.system_settings.rem_variance'),
                                        'remember_overdue_credit' => __('admin.system_settings.rem_overdue'),
                                        'remember_pending_approvals' => __('admin.system_settings.rem_pending')
                                    ] as $key => $label)
                                        <label for="{{ $key }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                            <input type="checkbox" class="setting-toggle h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                                   data-key="{{ $key }}" id="{{ $key }}"
                                                   {{ $allSettings[$key]['value'] ? 'checked' : '' }}>
                                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.keep_alerts_for') }}</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" min="1" max="90" class="input-3d setting-input text-center"
                                           data-key="alert_keep_days"
                                           value="{{ $allSettings['alert_keep_days']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">{{ __('admin.system_settings.days') }}</span>
                                </div>
                                <small class="mt-1 block text-center text-xs text-slate-400">{{ __('admin.system_settings.keep_hint') }}</small>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'system')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">🔧 {{ __('admin.system_settings.system_title') }}</h5>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.company_name') }}</label>
                                <input type="text" class="input-3d setting-input text-center"
                                       data-key="company_name"
                                       value="{{ $allSettings['company_name']['value'] }}"
                                       placeholder="Vital Petroleum">
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.timezone') }}</label>
                                <select class="input-3d setting-select text-center" data-key="timezone">
                                    <option value="Asia/Karachi" {{ $allSettings['timezone']['value'] === 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                                    <option value="Asia/Islamabad" {{ $allSettings['timezone']['value'] === 'Asia/Islamabad' ? 'selected' : '' }}>Asia/Islamabad (PKT)</option>
                                    <option value="UTC" {{ $allSettings['timezone']['value'] === 'UTC' ? 'selected' : '' }}>UTC</option>
                                </select>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.currency') }}</label>
                                <select class="input-3d setting-select text-center" data-key="currency">
                                    <option value="PKR" {{ $allSettings['currency']['value'] === 'PKR' ? 'selected' : '' }}>PKR (Pakistani Rupee)</option>
                                    <option value="USD" {{ $allSettings['currency']['value'] === 'USD' ? 'selected' : '' }}>USD (US Dollar)</option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <button type="submit" class="btn-3d btn-3d-primary">
                            💾 {{ __('admin.system_settings.save_all') }}
                        </button>
                        <span class="d-none font-bold text-emerald-600" id="saveSuccess">
                            ✅ {{ __('admin.system_settings.saved') }}
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="glass-card p-5 text-center">
                <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">💡 {{ __('admin.system_settings.tips_title') }}</h6>
                <ul class="mt-3 space-y-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    <li>{{ __('admin.system_settings.tip_1') }}</li>
                    <li>{{ __('admin.system_settings.tip_2') }}</li>
                    <li>{{ __('admin.system_settings.tip_3') }}</li>
                    <li>{{ __('admin.system_settings.tip_4') }}</li>
                    <li>{{ __('admin.system_settings.tip_5') }}</li>
                </ul>
            </div>

            <div class="glass-card p-5 text-center">
                <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">📝 {{ __('admin.system_settings.history_title') }}</h6>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('admin.system_settings.history_text') }}</p>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                    <li>{{ __('admin.system_settings.history_1') }}</li>
                    <li>{{ __('admin.system_settings.history_2') }}</li>
                    <li>{{ __('admin.system_settings.history_3') }}</li>
                    <li>{{ __('admin.system_settings.history_4') }}</li>
                </ul>
                <a href="{{ route('admin.settings.audit') }}" class="btn-3d btn-3d-ghost btn-3d-sm mt-4 w-full">
                    {{ __('admin.system_settings.view_audit') }}
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .d-none { display: none !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');

    // Update single setting on input change
    document.querySelectorAll('.setting-input, .setting-toggle, .setting-select').forEach(input => {
        input.addEventListener('change', function() {
            const key = this.dataset.key;
            const value = this.type === 'checkbox' ? this.checked : this.value;

            fetch("{{ route('admin.settings.update', ':key') }}".replace(':key', key), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ value: value })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Show quick save indicator
                    const indicator = document.querySelector(`.last-update-${key}`);
                    if (indicator) {
                        indicator.textContent = 'just now';
                    }
                }
            })
            .catch(err => console.error('Save error:', err));
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const updates = {};
        document.querySelectorAll('.setting-input, .setting-toggle, .setting-select').forEach(input => {
            const key = input.dataset.key;
            updates[key] = input.type === 'checkbox' ? input.checked : input.value;
        });

        fetch("{{ route('admin.settings.bulk-update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ updates: updates })
        })
        .then(r => r.json())
        .then(data => {
            const successMsg = document.getElementById('saveSuccess');
            successMsg.classList.remove('d-none');
            setTimeout(() => successMsg.classList.add('d-none'), 3000);
        })
        .catch(err => console.error('Error:', err));
    });
});
</script>
@endsection
