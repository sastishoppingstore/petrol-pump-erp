@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-6">🔔 Notification Preferences</h1>
            <p class="text-muted">Manage how you receive alerts and notifications</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="notificationForm" method="POST" action="{{ route('admin.notifications.update') }}">
                        @csrf
                        
                        <!-- Global Settings -->
                        <h5 class="mb-4">📢 Global Notification Settings</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="emailGlobal" 
                                           data-setting="email_enabled" {{ $globalSettings['email_enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailGlobal">
                                        📧 Email Notifications (Global)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="smsGlobal" 
                                           data-setting="sms_enabled" {{ $globalSettings['sms_enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="smsGlobal">
                                        📱 SMS Notifications (Global)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Notification Types -->
                        <h5 class="mb-4">🎯 Alert Types & Subscriptions</h5>
                        
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
                            <div class="card mb-3 bg-light">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <h6 class="mb-1">
                                                {{ $config['icon'] }} {{ $config['label'] }}
                                            </h6>
                                            <small class="text-muted">{{ $config['description'] }}</small>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="row g-2">
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" 
                                                               name="notifications[{{ $type }}][email]"
                                                               id="email_{{ $type }}"
                                                               {{ $subscription && $subscription->email_enabled ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="email_{{ $type }}">
                                                            Email
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" 
                                                               name="notifications[{{ $type }}][sms]"
                                                               id="sms_{{ $type }}"
                                                               {{ $subscription && $subscription->sms_enabled ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="sms_{{ $type }}">
                                                            SMS
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" 
                                                               name="notifications[{{ $type }}][active]"
                                                               id="active_{{ $type }}"
                                                               {{ $subscription && $subscription->active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="active_{{ $type }}">
                                                            Active
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Contact info for SMS -->
                                            @if (in_array($type, ['low_stock', 'shift_variance', 'overdue_credit']))
                                                <div class="row mt-2">
                                                    <div class="col-md-6">
                                                        <input type="email" class="form-control form-control-sm" 
                                                               name="notifications[{{ $type }}][email_addr]"
                                                               placeholder="Email address"
                                                               value="{{ $subscription?->email ?? auth()->user()->email }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="tel" class="form-control form-control-sm" 
                                                               name="notifications[{{ $type }}][phone]"
                                                               placeholder="Phone (03xxxxxxxxx)"
                                                               value="{{ $subscription?->phone ?? '' }}">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        <hr>
                        
                        <!-- Remember Settings -->
                        <h5 class="mb-3">💾 Remember/Keep Alerts</h5>
                        <p class="text-muted small mb-3">Select which alerts should be stored and kept for history</p>
                        
                        <div class="row">
                            @foreach ([
                                'remember_low_stock' => 'Remember Low Stock Alerts',
                                'remember_variance_alerts' => 'Remember Variance Alerts',
                                'remember_overdue_credit' => 'Remember Overdue Credit Alerts',
                                'remember_pending_approvals' => 'Remember Pending Approvals'
                            ] as $key => $label)
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="{{ $key }}"
                                               data-setting="{{ $key }}"
                                               {{ $globalSettings[$key] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $key }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <small>
                                <strong>Alerts are kept for {{ $globalSettings['alert_keep_days'] }} days</strong> (configurable in System Settings)
                            </small>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                💾 Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Info Sidebar -->
        <div class="col-lg-4">
            <div class="card bg-light border-0 mb-4">
                <div class="card-body">
                    <h6 class="card-title">💡 How It Works</h6>
                    <ul class="small mb-0" style="line-height: 1.8;">
                        <li><strong>Email:</strong> Sent immediately when alert triggers</li>
                        <li><strong>SMS:</strong> Requires provider API key in settings</li>
                        <li><strong>Active:</strong> Toggle alerts on/off per type</li>
                        <li><strong>Remember:</strong> Keeps alert history for audits</li>
                        <li><strong>Keep for:</strong> Auto-delete old alerts after N days</li>
                    </ul>
                </div>
            </div>
            
            <div class="card border-0">
                <div class="card-body">
                    <h6 class="card-title">📱 SMS Providers</h6>
                    <p class="small text-muted mb-2">To enable SMS, configure in <strong>Settings → Notifications</strong>:</p>
                    <ul class="small" style="line-height: 1.8;">
                        <li>🟢 <strong>Jazz (Mobilink)</strong> - Most popular</li>
                        <li>🟡 <strong>Zong</strong> - Nationwide coverage</li>
                        <li>🔵 <strong>Telenor</strong> - Alternative option</li>
                    </ul>
                    <a href="{{ route('admin.settings.index', ['category' => 'notifications']) }}" 
                       class="btn btn-sm btn-outline-primary w-100 mt-3">
                        Setup SMS Provider
                    </a>
                </div>
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
