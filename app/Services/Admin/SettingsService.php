<?php

namespace App\Services\Admin;

use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * Centralized system settings management
 * All prices, taxes, thresholds, notifications controlled through this service
 */
class SettingsService
{
    const CACHE_KEY = 'system_settings';
    const CACHE_TTL = 3600; // 1 hour
    
    // Default settings
    private array $defaults = [
        // Fuel & Prices
        'fuel_petrol_price' => ['type' => 'decimal', 'value' => 0, 'label' => 'Petrol Price (Rs/L)'],
        'fuel_diesel_price' => ['type' => 'decimal', 'value' => 0, 'label' => 'Diesel Price (Rs/L)'],
        'fuel_hsd_price' => ['type' => 'decimal', 'value' => 0, 'label' => 'HSD Price (Rs/L)'],
        'fuel_octane_price' => ['type' => 'decimal', 'value' => 0, 'label' => 'Octane Price (Rs/L)'],
        
        // Tax Settings
        'tax_gst_rate' => ['type' => 'decimal', 'value' => 17, 'label' => 'GST Rate (%)'],
        'tax_frb_rate' => ['type' => 'decimal', 'value' => 0, 'label' => 'FBR Rate (%)'],
        'tax_provincial_rate' => ['type' => 'decimal', 'value' => 0, 'label' => 'Provincial Tax (%)'],
        'frb_enabled' => ['type' => 'boolean', 'value' => true, 'label' => 'Enable FBR Invoicing'],
        
        // Variance Thresholds
        'shift_cash_variance_threshold' => ['type' => 'decimal', 'value' => 500, 'label' => 'Shift Cash Variance Threshold (Rs)'],
        'meter_variance_threshold' => ['type' => 'decimal', 'value' => 5, 'label' => 'Meter Variance Threshold (L)'],
        'tank_variance_threshold' => ['type' => 'decimal', 'value' => 10, 'label' => 'Tank Variance Threshold (L)'],
        'low_stock_threshold' => ['type' => 'decimal', 'value' => 500, 'label' => 'Low Stock Alert (L)'],
        
        // Notifications & Alerts
        'sms_enabled' => ['type' => 'boolean', 'value' => false, 'label' => 'Enable SMS Notifications'],
        'email_enabled' => ['type' => 'boolean', 'value' => true, 'label' => 'Enable Email Notifications'],
        'sms_provider' => ['type' => 'string', 'value' => 'jazz', 'label' => 'SMS Provider (jazz/zong/telenor)'],
        'sms_api_key' => ['type' => 'string', 'value' => '', 'label' => 'SMS API Key (encrypted)'],
        'alert_admin_email' => ['type' => 'string', 'value' => '', 'label' => 'Alert Recipient Email'],
        'alert_admin_phone' => ['type' => 'string', 'value' => '', 'label' => 'Alert Recipient Phone'],
        
        // Report Settings
        'auto_report_12h' => ['type' => 'boolean', 'value' => true, 'label' => 'Generate 12-Hour Reports'],
        'auto_report_24h' => ['type' => 'boolean', 'value' => true, 'label' => 'Generate Daily Reports'],
        'auto_report_7d' => ['type' => 'boolean', 'value' => true, 'label' => 'Generate Weekly Reports'],
        'auto_report_15d' => ['type' => 'boolean', 'value' => false, 'label' => 'Generate 15-Day Reports'],
        'auto_report_30d' => ['type' => 'boolean', 'value' => true, 'label' => 'Generate Monthly Reports'],
        'report_send_time' => ['type' => 'string', 'value' => '23:00', 'label' => 'Daily Report Send Time (HH:MM)'],
        'report_include_pdf' => ['type' => 'boolean', 'value' => true, 'label' => 'Include PDF in Reports'],
        'report_include_excel' => ['type' => 'boolean', 'value' => true, 'label' => 'Include Excel in Reports'],
        
        // Remember/Keep Settings
        'remember_low_stock' => ['type' => 'boolean', 'value' => true, 'label' => 'Remember Low Stock Alerts'],
        'remember_variance_alerts' => ['type' => 'boolean', 'value' => true, 'label' => 'Remember Variance Alerts'],
        'remember_overdue_credit' => ['type' => 'boolean', 'value' => true, 'label' => 'Remember Overdue Credit'],
        'remember_pending_approvals' => ['type' => 'boolean', 'value' => true, 'label' => 'Remember Pending Approvals'],
        'alert_keep_days' => ['type' => 'integer', 'value' => 30, 'label' => 'Keep Alerts for (days)'],
        
        // System
        'company_name' => ['type' => 'string', 'value' => 'Vital Petroleum', 'label' => 'Company Name'],
        'timezone' => ['type' => 'string', 'value' => 'Asia/Karachi', 'label' => 'Timezone'],
        'currency' => ['type' => 'string', 'value' => 'PKR', 'label' => 'Currency Code'],
    ];
    
    /**
     * Get a setting value with type casting
     */
    public function get($key, $default = null)
    {
        $cache = Cache::get(self::CACHE_KEY, []);
        
        if (isset($cache[$key])) {
            return $this->castValue($cache[$key]);
        }
        
        $setting = SystemSetting::where('key', $key)->first();
        
        if ($setting) {
            $cache[$key] = $setting->value;
            Cache::put(self::CACHE_KEY, $cache, self::CACHE_TTL);
            return $this->castValue($setting->value, $setting->type);
        }
        
        return $default ?? ($this->defaults[$key]['value'] ?? null);
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value, $type = 'string', $description = null)
    {
        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->encryptSensitive($key, $value),
                'type' => $type,
                'description' => $description,
                'updated_by' => Auth::id(),
            ]
        );
        
        // Clear cache
        Cache::forget(self::CACHE_KEY);
        
        // Log change
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE',
            'module' => 'settings',
            'reference_type' => 'SystemSetting',
            'reference_id' => $setting->id,
            'old_data' => json_encode([]),
            'new_data' => json_encode(['key' => $key, 'value' => $this->maskSensitive($key, $value)]),
            'ip' => request()->ip(),
        ]);
        
        return $setting;
    }
    
    /**
     * Get all settings
     */
    public function all()
    {
        $cache = Cache::get(self::CACHE_KEY, []);
        $settings = [];
        
        foreach ($this->defaults as $key => $config) {
            $settings[$key] = $this->get($key);
        }
        
        return $settings;
    }
    
    /**
     * Get settings by category
     */
    public function byCategory($category)
    {
        $categories = [
            'fuel' => ['fuel_petrol_price', 'fuel_diesel_price', 'fuel_hsd_price', 'fuel_octane_price'],
            'tax' => ['tax_gst_rate', 'tax_frb_rate', 'tax_provincial_rate', 'frb_enabled'],
            'variance' => ['shift_cash_variance_threshold', 'meter_variance_threshold', 'tank_variance_threshold', 'low_stock_threshold'],
            'notifications' => ['sms_enabled', 'email_enabled', 'sms_provider', 'sms_api_key', 'alert_admin_email', 'alert_admin_phone'],
            'reports' => ['auto_report_12h', 'auto_report_24h', 'auto_report_7d', 'auto_report_15d', 'auto_report_30d', 'report_send_time', 'report_include_pdf', 'report_include_excel'],
            'remember' => ['remember_low_stock', 'remember_variance_alerts', 'remember_overdue_credit', 'remember_pending_approvals', 'alert_keep_days'],
            'system' => ['company_name', 'timezone', 'currency'],
        ];
        
        $keys = $categories[$category] ?? [];
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        
        return $result;
    }
    
    /**
     * Get all settings with metadata (for admin UI)
     */
    public function allWithMetadata()
    {
        $result = [];
        
        foreach ($this->defaults as $key => $config) {
            $result[$key] = [
                'value' => $this->get($key),
                'type' => $config['type'],
                'label' => $config['label'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Cast value to appropriate type
     */
    private function castValue($value, $type = 'string')
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
    
    /**
     * Encrypt sensitive settings
     */
    private function encryptSensitive($key, $value)
    {
        $sensitive = ['sms_api_key', 'smtp_password'];
        
        if (in_array($key, $sensitive) && !empty($value)) {
            return encrypt($value);
        }
        
        return $value;
    }
    
    /**
     * Mask sensitive settings for logging
     */
    private function maskSensitive($key, $value)
    {
        $sensitive = ['sms_api_key', 'smtp_password', 'alert_admin_phone'];
        
        if (in_array($key, $sensitive)) {
            return '***' . substr($value, -4);
        }
        
        return $value;
    }
}
