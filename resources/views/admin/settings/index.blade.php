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
        <h1>⚙️ System Settings</h1>
        <p>Configure all system parameters. Changes take effect immediately.</p>
        <div class="page-actions">
            <a href="{{ route('admin.settings.audit') }}" class="btn-3d btn-3d-ghost">
                📋 View Change History
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
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">⛽ Fuel Prices (Per Liter)</h5>

                            @foreach (['fuel_petrol_price' => 'Petrol (Mogas)', 'fuel_diesel_price' => 'Diesel (HSD)', 'fuel_hsd_price' => 'HSD (Agri)', 'fuel_octane_price' => 'Octane 95'] as $key => $label)
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
                                    <small class="mt-1 block text-center text-xs text-slate-400">Last updated:
                                        <span class="last-update-{{ $key }}">—</span>
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($activeCategory === 'tax')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">💰 Tax Configuration</h5>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">GST Rate (%)</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tax_gst_rate"
                                           value="{{ $allSettings['tax_gst_rate']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">%</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">FBR Rate (%)</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tax_frb_rate"
                                           value="{{ $allSettings['tax_frb_rate']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">%</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Provincial Tax (%)</label>
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
                                        Enable FBR Invoicing
                                        <span class="block text-xs font-normal text-slate-400">Automatically create FBR-compliant invoices</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'variance')
                        <div class="settings-section">
                            <h5 class="mb-2 text-center text-base font-black text-slate-800 dark:text-slate-100">⚠️ Variance Thresholds</h5>
                            <p class="mb-5 text-center text-xs text-slate-400">Alerts trigger when variance exceeds these values</p>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Shift Cash Variance</label>
                                <div class="flex items-stretch gap-2">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">Rs</span>
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="shift_cash_variance_threshold"
                                           value="{{ $allSettings['shift_cash_variance_threshold']['value'] }}">
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Meter Variance</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="meter_variance_threshold"
                                           value="{{ $allSettings['meter_variance_threshold']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">L</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tank Variance</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" step="0.01" class="input-3d setting-input text-center"
                                           data-key="tank_variance_threshold"
                                           value="{{ $allSettings['tank_variance_threshold']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">L</span>
                                </div>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Low Stock Alert</label>
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
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">🔔 Notifications &amp; Alerts</h5>

                            <div class="mx-auto mb-4 grid max-w-xl gap-3 sm:grid-cols-2">
                                <label for="smsEnabled" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                    <input type="checkbox" class="setting-toggle h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                           data-key="sms_enabled" id="smsEnabled"
                                           {{ $allSettings['sms_enabled']['value'] ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        Enable SMS
                                        <span class="block text-xs font-normal text-slate-400">Send alerts via SMS</span>
                                    </span>
                                </label>

                                <label for="emailEnabled" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                    <input type="checkbox" class="setting-toggle h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                           data-key="email_enabled" id="emailEnabled"
                                           {{ $allSettings['email_enabled']['value'] ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        Enable Email
                                        <span class="block text-xs font-normal text-slate-400">Send alerts via Email</span>
                                    </span>
                                </label>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">SMS Provider</label>
                                <select class="input-3d setting-select text-center" data-key="sms_provider">
                                    <option value="jazz" {{ $allSettings['sms_provider']['value'] === 'jazz' ? 'selected' : '' }}>Jazz (Mobilink)</option>
                                    <option value="zong" {{ $allSettings['sms_provider']['value'] === 'zong' ? 'selected' : '' }}>Zong</option>
                                    <option value="telenor" {{ $allSettings['sms_provider']['value'] === 'telenor' ? 'selected' : '' }}>Telenor</option>
                                </select>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">SMS API Key</label>
                                <input type="password" class="input-3d setting-input text-center"
                                       data-key="sms_api_key"
                                       value="{{ $allSettings['sms_api_key']['value'] }}"
                                       placeholder="Encrypted API key">
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Alert Email</label>
                                <input type="email" class="input-3d setting-input text-center"
                                       data-key="alert_admin_email"
                                       value="{{ $allSettings['alert_admin_email']['value'] }}"
                                       placeholder="admin@example.com">
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Alert Phone</label>
                                <input type="text" class="input-3d setting-input text-center"
                                       data-key="alert_admin_phone"
                                       value="{{ $allSettings['alert_admin_phone']['value'] }}"
                                       placeholder="+923001234567">
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'reports')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">📊 Auto-Report Generation</h5>

                            <div class="mx-auto mb-5 max-w-xl">
                                <h6 class="mb-3 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Report Schedules</h6>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ([
                                        'auto_report_12h' => '12-Hour Reports',
                                        'auto_report_24h' => 'Daily (24h) Reports',
                                        'auto_report_7d' => 'Weekly (7d) Reports',
                                        'auto_report_15d' => '15-Day Reports',
                                        'auto_report_30d' => 'Monthly (30d) Reports'
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
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Report Send Time</label>
                                <input type="time" class="input-3d setting-input text-center"
                                       data-key="report_send_time"
                                       value="{{ $allSettings['report_send_time']['value'] }}">
                                <small class="mt-1 block text-center text-xs text-slate-400">Daily report sent at this time (24h format)</small>
                            </div>

                            <div class="mx-auto max-w-xl">
                                <h6 class="mb-3 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Report Formats</h6>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label for="reportPdf" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                        <input type="checkbox" class="setting-toggle h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                               data-key="report_include_pdf" id="reportPdf"
                                               {{ $allSettings['report_include_pdf']['value'] ? 'checked' : '' }}>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Include PDF</span>
                                    </label>
                                    <label for="reportExcel" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                        <input type="checkbox" class="setting-toggle h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                               data-key="report_include_excel" id="reportExcel"
                                               {{ $allSettings['report_include_excel']['value'] ? 'checked' : '' }}>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Include Excel</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'remember')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">💾 Remember/Keep Alert Settings</h5>

                            <div class="mx-auto mb-5 max-w-xl">
                                <h6 class="mb-3 text-center text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">What to Keep/Remember</h6>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ([
                                        'remember_low_stock' => 'Low Stock Alerts',
                                        'remember_variance_alerts' => 'Variance Alerts',
                                        'remember_overdue_credit' => 'Overdue Credit Alerts',
                                        'remember_pending_approvals' => 'Pending Approval Alerts'
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
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Keep Alerts For</label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number" min="1" max="90" class="input-3d setting-input text-center"
                                           data-key="alert_keep_days"
                                           value="{{ $allSettings['alert_keep_days']['value'] }}">
                                    <span class="flex items-center rounded-xl bg-slate-900/5 px-3 text-sm font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">days</span>
                                </div>
                                <small class="mt-1 block text-center text-xs text-slate-400">Old alerts auto-delete after this many days</small>
                            </div>
                        </div>
                    @endif

                    @if ($activeCategory === 'system')
                        <div class="settings-section">
                            <h5 class="mb-5 text-center text-base font-black text-slate-800 dark:text-slate-100">🔧 System Configuration</h5>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Company Name</label>
                                <input type="text" class="input-3d setting-input text-center"
                                       data-key="company_name"
                                       value="{{ $allSettings['company_name']['value'] }}"
                                       placeholder="Vital Petroleum">
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Timezone</label>
                                <select class="input-3d setting-select text-center" data-key="timezone">
                                    <option value="Asia/Karachi" {{ $allSettings['timezone']['value'] === 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                                    <option value="Asia/Islamabad" {{ $allSettings['timezone']['value'] === 'Asia/Islamabad' ? 'selected' : '' }}>Asia/Islamabad (PKT)</option>
                                    <option value="UTC" {{ $allSettings['timezone']['value'] === 'UTC' ? 'selected' : '' }}>UTC</option>
                                </select>
                            </div>

                            <div class="field-3d mx-auto mb-4 max-w-md">
                                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Currency</label>
                                <select class="input-3d setting-select text-center" data-key="currency">
                                    <option value="PKR" {{ $allSettings['currency']['value'] === 'PKR' ? 'selected' : '' }}>PKR (Pakistani Rupee)</option>
                                    <option value="USD" {{ $allSettings['currency']['value'] === 'USD' ? 'selected' : '' }}>USD (US Dollar)</option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <button type="submit" class="btn-3d btn-3d-primary">
                            💾 Save All Changes
                        </button>
                        <span class="d-none font-bold text-emerald-600" id="saveSuccess">
                            ✅ Saved successfully!
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="glass-card p-5 text-center">
                <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">💡 Tips</h6>
                <ul class="mt-3 space-y-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    <li>All changes are immediately effective</li>
                    <li>Prices update across all sales</li>
                    <li>Thresholds control alert triggers</li>
                    <li>SMS API key is encrypted</li>
                    <li>Changes are audit-logged</li>
                </ul>
            </div>

            <div class="glass-card p-5 text-center">
                <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">📝 Change History</h6>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">All setting changes are logged with:</p>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-500 dark:text-slate-400">
                    <li>Who changed it (user)</li>
                    <li>When it changed (timestamp)</li>
                    <li>What was changed (old → new)</li>
                    <li>IP address &amp; session</li>
                </ul>
                <a href="{{ route('admin.settings.audit') }}" class="btn-3d btn-3d-ghost btn-3d-sm mt-4 w-full">
                    View Audit Log
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
