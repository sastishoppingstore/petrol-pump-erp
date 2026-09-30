@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-6">⚙️ System Settings</h1>
            <p class="text-muted">Configure all system parameters. Changes take effect immediately.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.settings.audit') }}" class="btn btn-outline-secondary">
                📋 View Change History
            </a>
        </div>
    </div>
    
    <!-- Category Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="nav nav-tabs flex-wrap" role="tablist">
                @foreach ($categories as $cat_key => $cat_label)
                    <button class="nav-link {{ $cat_key === $activeCategory ? 'active' : '' }}" 
                            onclick="window.location.href='?category={{ $cat_key }}'" 
                            type="button">
                        {{ $cat_label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Settings Content -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="settingsForm" class="settings-form">
                        @csrf
                        
                        @if ($activeCategory === 'fuel')
                            <div class="settings-section">
                                <h5 class="mb-3">⛽ Fuel Prices (Per Liter)</h5>
                                
                                @foreach (['fuel_petrol_price' => 'Petrol (Mogas)', 'fuel_diesel_price' => 'Diesel (HSD)', 'fuel_hsd_price' => 'HSD (Agri)', 'fuel_octane_price' => 'Octane 95'] as $key => $label)
                                    <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">{{ $label }}</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <span class="input-group-text">Rs</span>
                                                <input type="number" 
                                                       step="0.01" 
                                                       class="form-control setting-input" 
                                                       data-key="{{ $key }}"
                                                       value="{{ $allSettings[$key]['value'] ?? 0 }}"
                                                       placeholder="0.00">
                                            </div>
                                            <small class="text-muted">Last updated: 
                                                <span class="last-update-{{ $key }}">—</span>
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if ($activeCategory === 'tax')
                            <div class="settings-section">
                                <h5 class="mb-3">💰 Tax Configuration</h5>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">GST Rate (%)</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="tax_gst_rate"
                                                   value="{{ $allSettings['tax_gst_rate']['value'] }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">FBR Rate (%)</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="tax_frb_rate"
                                                   value="{{ $allSettings['tax_frb_rate']['value'] }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Provincial Tax (%)</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="tax_provincial_rate"
                                                   value="{{ $allSettings['tax_provincial_rate']['value'] }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Enable FBR Invoicing</label>
                                    <div class="col-sm-8">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input setting-toggle" 
                                                   data-key="frb_enabled" id="frbEnabled"
                                                   {{ $allSettings['frb_enabled']['value'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="frbEnabled">
                                                Automatically create FBR-compliant invoices
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if ($activeCategory === 'variance')
                            <div class="settings-section">
                                <h5 class="mb-3">⚠️ Variance Thresholds</h5>
                                <p class="text-muted small mb-3">Alerts trigger when variance exceeds these values</p>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Shift Cash Variance</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <span class="input-group-text">Rs</span>
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="shift_cash_variance_threshold"
                                                   value="{{ $allSettings['shift_cash_variance_threshold']['value'] }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Meter Variance</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="meter_variance_threshold"
                                                   value="{{ $allSettings['meter_variance_threshold']['value'] }}">
                                            <span class="input-group-text">L</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Tank Variance</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="tank_variance_threshold"
                                                   value="{{ $allSettings['tank_variance_threshold']['value'] }}">
                                            <span class="input-group-text">L</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Low Stock Alert</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control setting-input" 
                                                   data-key="low_stock_threshold"
                                                   value="{{ $allSettings['low_stock_threshold']['value'] }}">
                                            <span class="input-group-text">L</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if ($activeCategory === 'notifications')
                            <div class="settings-section">
                                <h5 class="mb-3">🔔 Notifications & Alerts</h5>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Enable SMS</label>
                                    <div class="col-sm-8">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input setting-toggle" 
                                                   data-key="sms_enabled" id="smsEnabled"
                                                   {{ $allSettings['sms_enabled']['value'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="smsEnabled">
                                                Send alerts via SMS
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Enable Email</label>
                                    <div class="col-sm-8">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input setting-toggle" 
                                                   data-key="email_enabled" id="emailEnabled"
                                                   {{ $allSettings['email_enabled']['value'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="emailEnabled">
                                                Send alerts via Email
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">SMS Provider</label>
                                    <div class="col-sm-8">
                                        <select class="form-control setting-select" data-key="sms_provider">
                                            <option value="jazz" {{ $allSettings['sms_provider']['value'] === 'jazz' ? 'selected' : '' }}>Jazz (Mobilink)</option>
                                            <option value="zong" {{ $allSettings['sms_provider']['value'] === 'zong' ? 'selected' : '' }}>Zong</option>
                                            <option value="telenor" {{ $allSettings['sms_provider']['value'] === 'telenor' ? 'selected' : '' }}>Telenor</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">SMS API Key</label>
                                    <div class="col-sm-8">
                                        <input type="password" class="form-control setting-input" 
                                               data-key="sms_api_key"
                                               value="{{ $allSettings['sms_api_key']['value'] }}"
                                               placeholder="Encrypted API key">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Alert Email</label>
                                    <div class="col-sm-8">
                                        <input type="email" class="form-control setting-input" 
                                               data-key="alert_admin_email"
                                               value="{{ $allSettings['alert_admin_email']['value'] }}"
                                               placeholder="admin@example.com">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Alert Phone</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control setting-input" 
                                               data-key="alert_admin_phone"
                                               value="{{ $allSettings['alert_admin_phone']['value'] }}"
                                               placeholder="+923001234567">
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if ($activeCategory === 'reports')
                            <div class="settings-section">
                                <h5 class="mb-3">📊 Auto-Report Generation</h5>
                                
                                <div class="mb-4">
                                    <h6 class="mb-3">Report Schedules</h6>
                                    @foreach ([
                                        'auto_report_12h' => '12-Hour Reports',
                                        'auto_report_24h' => 'Daily (24h) Reports',
                                        'auto_report_7d' => 'Weekly (7d) Reports',
                                        'auto_report_15d' => '15-Day Reports',
                                        'auto_report_30d' => 'Monthly (30d) Reports'
                                    ] as $key => $label)
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input setting-toggle" 
                                                   data-key="{{ $key }}" id="{{ $key }}"
                                                   {{ $allSettings[$key]['value'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Report Send Time</label>
                                    <div class="col-sm-8">
                                        <input type="time" class="form-control setting-input" 
                                               data-key="report_send_time"
                                               value="{{ $allSettings['report_send_time']['value'] }}">
                                        <small class="text-muted">Daily report sent at this time (24h format)</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="mb-3">Report Formats</h6>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input setting-toggle" 
                                               data-key="report_include_pdf" id="reportPdf"
                                               {{ $allSettings['report_include_pdf']['value'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="reportPdf">
                                            Include PDF
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input setting-toggle" 
                                               data-key="report_include_excel" id="reportExcel"
                                               {{ $allSettings['report_include_excel']['value'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="reportExcel">
                                            Include Excel
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if ($activeCategory === 'remember')
                            <div class="settings-section">
                                <h5 class="mb-3">💾 Remember/Keep Alert Settings</h5>
                                
                                <div class="mb-4">
                                    <h6 class="mb-3">What to Keep/Remember</h6>
                                    @foreach ([
                                        'remember_low_stock' => 'Low Stock Alerts',
                                        'remember_variance_alerts' => 'Variance Alerts',
                                        'remember_overdue_credit' => 'Overdue Credit Alerts',
                                        'remember_pending_approvals' => 'Pending Approval Alerts'
                                    ] as $key => $label)
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input setting-toggle" 
                                                   data-key="{{ $key }}" id="{{ $key }}"
                                                   {{ $allSettings[$key]['value'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Keep Alerts For</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" min="1" max="90" class="form-control setting-input" 
                                                   data-key="alert_keep_days"
                                                   value="{{ $allSettings['alert_keep_days']['value'] }}">
                                            <span class="input-group-text">days</span>
                                        </div>
                                        <small class="text-muted">Old alerts auto-delete after this many days</small>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if ($activeCategory === 'system')
                            <div class="settings-section">
                                <h5 class="mb-3">🔧 System Configuration</h5>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Company Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control setting-input" 
                                               data-key="company_name"
                                               value="{{ $allSettings['company_name']['value'] }}"
                                               placeholder="Vital Petroleum">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Timezone</label>
                                    <div class="col-sm-8">
                                        <select class="form-control setting-select" data-key="timezone">
                                            <option value="Asia/Karachi" {{ $allSettings['timezone']['value'] === 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                                            <option value="Asia/Islamabad" {{ $allSettings['timezone']['value'] === 'Asia/Islamabad' ? 'selected' : '' }}>Asia/Islamabad (PKT)</option>
                                            <option value="UTC" {{ $allSettings['timezone']['value'] === 'UTC' ? 'selected' : '' }}>UTC</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label">Currency</label>
                                    <div class="col-sm-8">
                                        <select class="form-control setting-select" data-key="currency">
                                            <option value="PKR" {{ $allSettings['currency']['value'] === 'PKR' ? 'selected' : '' }}>PKR (Pakistani Rupee)</option>
                                            <option value="USD" {{ $allSettings['currency']['value'] === 'USD' ? 'selected' : '' }}>USD (US Dollar)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                💾 Save All Changes
                            </button>
                            <span class="ms-3 text-success d-none" id="saveSuccess">
                                ✅ Saved successfully!
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card bg-light border-0 mb-4">
                <div class="card-body">
                    <h6 class="card-title">💡 Tips</h6>
                    <ul class="small mb-0" style="line-height: 1.8;">
                        <li>All changes are immediately effective</li>
                        <li>Prices update across all sales</li>
                        <li>Thresholds control alert triggers</li>
                        <li>SMS API key is encrypted</li>
                        <li>Changes are audit-logged</li>
                    </ul>
                </div>
            </div>
            
            <div class="card border-0">
                <div class="card-body">
                    <h6 class="card-title">📝 Change History</h6>
                    <p class="small text-muted">All setting changes are logged with:</p>
                    <ul class="small text-muted mb-0">
                        <li>Who changed it (user)</li>
                        <li>When it changed (timestamp)</li>
                        <li>What was changed (old → new)</li>
                        <li>IP address & session</li>
                    </ul>
                    <a href="{{ route('admin.settings.audit') }}" class="btn btn-sm btn-outline-secondary mt-3 w-100">
                        View Audit Log
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-section {
        border-bottom: 1px solid #eee;
        padding-bottom: 30px;
        margin-bottom: 30px;
    }
    
    .settings-section:last-child {
        border-bottom: none;
    }
    
    .form-control:focus {
        border-color: #d71920;
        box-shadow: 0 0 0 0.2rem rgba(215, 25, 32, 0.25);
    }
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
