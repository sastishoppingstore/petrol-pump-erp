@extends('layouts.app')

@section('content')
{{--
    Notification Preferences — 2026 redesign (shell/cards only).
    SAKHT NOTE: form id (notificationForm), tamam checkbox names
    (notifications[type][email|sms|active]), contact input names
    (email_addr / phone), data-setting attributes aur ids bilkul pehle
    jaisay hain; neeche wala AJAX submit script bhi bilkul waisa hi hai
    (wo apni success alert khud banata hai).
--}}
<div class="space-y-6">
    <div class="page-head">
        <h1>🔔 Notification Preferences</h1>
        <p>Manage how you receive alerts and notifications</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="glass-card p-6">
                <form id="notificationForm" method="POST" action="{{ route('admin.notifications.update') }}">
                    @csrf

                    <!-- Global Settings -->
                    <h5 class="mb-4 text-center text-base font-black text-slate-800 dark:text-slate-100">📢 Global Notification Settings</h5>
                    <div class="mx-auto mb-6 grid max-w-xl gap-3 sm:grid-cols-2">
                        <label for="emailGlobal" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" id="emailGlobal"
                                   data-setting="email_enabled" {{ $globalSettings['email_enabled'] ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">📧 Email Notifications (Global)</span>
                        </label>
                        <label for="smsGlobal" class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white/60 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                            <input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" id="smsGlobal"
                                   data-setting="sms_enabled" {{ $globalSettings['sms_enabled'] ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">📱 SMS Notifications (Global)</span>
                        </label>
                    </div>

                    <hr class="border-slate-200/70 dark:border-slate-700/60">

                    <!-- Notification Types -->
                    <h5 class="mb-4 mt-6 text-center text-base font-black text-slate-800 dark:text-slate-100">🎯 Alert Types &amp; Subscriptions</h5>

                    @php
                        $notificationTypes = [
                            'low_stock' => ['icon' => '📦', 'label' => 'Low Stock Alerts', 'description' => 'When fuel inventory drops below threshold'],
                            'shift_variance' => ['icon' => '💰', 'label' => 'Shift Variance Alerts', 'description' => 'When cash variance exceeds tolerance'],
                            'overdue_credit' => ['icon' => '📝', 'label' => 'Overdue Credit Alerts', 'description' => 'When customer credit is overdue'],
                            'pending_approval' => ['icon' => '✅', 'label' => 'Pending Approvals', 'description' => 'When stock adjustments await approval'],
                            'tank_variance' => ['icon' => '📊', 'label' => 'Tank Variance Alerts', 'description' => 'When physical vs. system stock differs'],
                        ];
                    @endphp

                    @foreach ($notificationTypes as $type => $config)
                        @php
                            $subscription = $subscriptions->firstWhere('notification_type', $type);
                        @endphp
                        <div class="mb-4 rounded-2xl border border-slate-200/80 bg-white/60 p-4 shadow-sm dark:border-slate-700/70 dark:bg-white/5">
                            <div class="flex flex-col items-center gap-4 text-center">
                                <div>
                                    <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">
                                        {{ $config['icon'] }} {{ $config['label'] }}
                                    </h6>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $config['description'] }}</p>
                                </div>
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <label for="email_{{ $type }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-3 py-2 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                                   name="notifications[{{ $type }}][email]"
                                                   id="email_{{ $type }}"
                                                   {{ $subscription && $subscription->email_enabled ? 'checked' : '' }}>
                                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Email</span>
                                        </label>
                                        <label for="sms_{{ $type }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-3 py-2 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                                   name="notifications[{{ $type }}][sms]"
                                                   id="sms_{{ $type }}"
                                                   {{ $subscription && $subscription->sms_enabled ? 'checked' : '' }}>
                                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">SMS</span>
                                        </label>
                                        <label for="active_{{ $type }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-3 py-2 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary"
                                                   name="notifications[{{ $type }}][active]"
                                                   id="active_{{ $type }}"
                                                   {{ $subscription && $subscription->active ? 'checked' : '' }}>
                                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Active</span>
                                        </label>
                                    </div>

                                    <!-- Contact info for SMS -->
                                    @if (in_array($type, ['low_stock', 'shift_variance', 'overdue_credit']))
                                        <div class="grid w-full gap-3 sm:grid-cols-2">
                                            <div class="field-3d">
                                                <input type="email" class="input-3d text-center text-sm"
                                                       name="notifications[{{ $type }}][email_addr]"
                                                       placeholder="Email address"
                                                       value="{{ $subscription?->email ?? auth()->user()->email }}">
                                            </div>
                                            <div class="field-3d">
                                                <input type="tel" class="input-3d text-center text-sm"
                                                       name="notifications[{{ $type }}][phone]"
                                                       placeholder="Phone (03xxxxxxxxx)"
                                                       value="{{ $subscription?->phone ?? '' }}">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <hr class="border-slate-200/70 dark:border-slate-700/60">

                    <!-- Remember Settings -->
                    <h5 class="mb-2 mt-6 text-center text-base font-black text-slate-800 dark:text-slate-100">💾 Remember/Keep Alerts</h5>
                    <p class="mb-4 text-center text-xs text-slate-400">Select which alerts should be stored and kept for history</p>

                    <div class="mx-auto grid max-w-xl gap-2 sm:grid-cols-2">
                        @foreach ([
                            'remember_low_stock' => 'Remember Low Stock Alerts',
                            'remember_variance_alerts' => 'Remember Variance Alerts',
                            'remember_overdue_credit' => 'Remember Overdue Credit Alerts',
                            'remember_pending_approvals' => 'Remember Pending Approvals'
                        ] as $key => $label)
                            <label for="{{ $key }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5">
                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" id="{{ $key }}"
                                       data-setting="{{ $key }}"
                                       {{ $globalSettings[$key] ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mx-auto mt-4 max-w-xl rounded-2xl border border-sky-300/60 bg-sky-50 px-4 py-3 text-center text-sm text-sky-800 shadow-sm dark:bg-sky-500/10 dark:text-sky-300">
                        <strong>Alerts are kept for {{ $globalSettings['alert_keep_days'] }} days</strong> (configurable in System Settings)
                    </div>

                    <div class="mt-6 flex justify-center">
                        <button type="submit" class="btn-3d btn-3d-primary">
                            💾 Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="space-y-6">
            <div class="glass-card p-5 text-center">
                <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">💡 How It Works</h6>
                <ul class="mt-3 space-y-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    <li><strong class="text-slate-700 dark:text-slate-200">Email:</strong> Sent immediately when alert triggers</li>
                    <li><strong class="text-slate-700 dark:text-slate-200">SMS:</strong> Requires provider API key in settings</li>
                    <li><strong class="text-slate-700 dark:text-slate-200">Active:</strong> Toggle alerts on/off per type</li>
                    <li><strong class="text-slate-700 dark:text-slate-200">Remember:</strong> Keeps alert history for audits</li>
                    <li><strong class="text-slate-700 dark:text-slate-200">Keep for:</strong> Auto-delete old alerts after N days</li>
                </ul>
            </div>

            <div class="glass-card p-5 text-center">
                <h6 class="text-sm font-black text-slate-800 dark:text-slate-100">📱 SMS Providers</h6>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">To enable SMS, configure in <strong class="text-slate-700 dark:text-slate-200">Settings → Notifications</strong>:</p>
                <ul class="mt-2 space-y-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    <li>🟢 <strong class="text-slate-700 dark:text-slate-200">Jazz (Mobilink)</strong> - Most popular</li>
                    <li>🟡 <strong class="text-slate-700 dark:text-slate-200">Zong</strong> - Nationwide coverage</li>
                    <li>🔵 <strong class="text-slate-700 dark:text-slate-200">Telenor</strong> - Alternative option</li>
                </ul>
                <a href="{{ route('admin.settings.index', ['category' => 'notifications']) }}"
                   class="btn-3d btn-3d-ghost btn-3d-sm mt-4 w-full">
                    Setup SMS Provider
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('notificationForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Collect all form data
        const formData = new FormData(form);

        fetch("{{ route('admin.notifications.update') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = '✅ Preferences saved! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                form.insertAdjacentElement('beforebegin', alert);

                setTimeout(() => alert.remove(), 3000);
            }
        })
        .catch(err => console.error('Error:', err));
    });
});
</script>
@endsection
