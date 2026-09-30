<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * System settings seeder - Mehar Filling Station
 * Only seeds if settings don't exist (idempotent)
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Fuel prices
        $this->seedSetting('fuel_petrol_price', 315.50, 'decimal', 'Petrol Price (Rs/L)');
        $this->seedSetting('fuel_diesel_price', 335.00, 'decimal', 'Diesel Price (Rs/L)');
        $this->seedSetting('fuel_hsd_price', 330.50, 'decimal', 'HSD Price (Rs/L)');
        $this->seedSetting('fuel_octane_price', 405.00, 'decimal', 'Octane Price (Rs/L)');
        
        // Tax settings
        $this->seedSetting('tax_gst_rate', 17, 'decimal', 'GST Rate (%)');
        $this->seedSetting('tax_frb_rate', 0, 'decimal', 'FBR Rate (%)');
        $this->seedSetting('tax_provincial_rate', 0, 'decimal', 'Provincial Tax (%)');
        $this->seedSetting('frb_enabled', true, 'boolean', 'Enable FBR Invoicing');
        
        // Variance thresholds
        $this->seedSetting('shift_cash_variance_threshold', 500, 'decimal', 'Shift Cash Variance Threshold (Rs)');
        $this->seedSetting('meter_variance_threshold', 5, 'decimal', 'Meter Variance Threshold (L)');
        $this->seedSetting('tank_variance_threshold', 10, 'decimal', 'Tank Variance Threshold (L)');
        $this->seedSetting('low_stock_threshold', 500, 'decimal', 'Low Stock Alert (L)');
        
        // Notifications
        $this->seedSetting('sms_enabled', false, 'boolean', 'Enable SMS Notifications');
        $this->seedSetting('email_enabled', true, 'boolean', 'Enable Email Notifications');
        $this->seedSetting('sms_provider', 'jazz', 'string', 'SMS Provider');
        $this->seedSetting('sms_api_key', '', 'string', 'SMS API Key');
        $this->seedSetting('alert_admin_email', '', 'string', 'Alert Recipient Email');
        $this->seedSetting('alert_admin_phone', '', 'string', 'Alert Recipient Phone');
        
        // Report settings
        $this->seedSetting('auto_report_12h', true, 'boolean', 'Generate 12-Hour Reports');
        $this->seedSetting('auto_report_24h', true, 'boolean', 'Generate Daily Reports');
        $this->seedSetting('auto_report_7d', true, 'boolean', 'Generate Weekly Reports');
        $this->seedSetting('auto_report_15d', false, 'boolean', 'Generate 15-Day Reports');
        $this->seedSetting('auto_report_30d', true, 'boolean', 'Generate Monthly Reports');
        $this->seedSetting('report_send_time', '23:00', 'string', 'Daily Report Send Time');
        $this->seedSetting('report_include_pdf', true, 'boolean', 'Include PDF in Reports');
        $this->seedSetting('report_include_excel', true, 'boolean', 'Include Excel in Reports');
        
        // Remember settings
        $this->seedSetting('remember_low_stock', true, 'boolean', 'Remember Low Stock Alerts');
        $this->seedSetting('remember_variance_alerts', true, 'boolean', 'Remember Variance Alerts');
        $this->seedSetting('remember_overdue_credit', true, 'boolean', 'Remember Overdue Credit');
        $this->seedSetting('remember_pending_approvals', true, 'boolean', 'Remember Pending Approvals');
        $this->seedSetting('alert_keep_days', 30, 'integer', 'Keep Alerts for (days)');
        
        // System
        $this->seedSetting('company_name', 'Vital Petroleum', 'string', 'Company Name');
        $this->seedSetting('timezone', 'Asia/Karachi', 'string', 'Timezone');
        $this->seedSetting('currency', 'PKR', 'string', 'Currency Code');
    }
    
    private function seedSetting($key, $value, $type, $description)
    {
        if (!SystemSetting::where('key', $key)->exists()) {
            SystemSetting::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]);
        }
    }
}

