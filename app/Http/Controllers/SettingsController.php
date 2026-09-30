<?php

namespace App\Http\Controllers;

use App\Services\System\SettingService;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function index(): View
    {
        $station = $this->settingService->stationIdentity();
        $settings = $this->settingService->all();

        return view('settings.index', compact('station', 'settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Station Identity
            'station_name_ur' => 'nullable|string|max:255',
            'station_name_en' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'omc_brand' => 'nullable|string|max:255',
            
            // Operational Thresholds
            'shift_variance_threshold' => 'nullable|numeric|min:0',
            'meter_variance_tolerance' => 'nullable|numeric|min:0',
            'overdue_credit_days' => 'nullable|integer|min:1',
            'auto_shift_close_time' => 'nullable|date_format:H:i',
            'low_stock_threshold_liters' => 'nullable|numeric|min:0',
            'payment_methods_enabled' => 'nullable|string',
            'thank_you_message' => 'nullable|string|max:500',
            
            // Notifications & SMS
            'sms_provider' => 'nullable|in:jazz,zong,telenor',
            'sms_api_key' => 'nullable|string|max:500',
            'sms_sender_id' => 'nullable|string|max:20',
            'email_enabled' => 'nullable|boolean',
            'sms_enabled' => 'nullable|boolean',
            'low_stock_alert_enabled' => 'nullable|boolean',
            'variance_alert_enabled' => 'nullable|boolean',
            'overdue_credit_alert_enabled' => 'nullable|boolean',
            'pending_approval_alert_enabled' => 'nullable|boolean',
            
            // Reports
            'auto_report_12h' => 'nullable|boolean',
            'auto_report_24h' => 'nullable|boolean',
            'auto_report_7d' => 'nullable|boolean',
            'auto_report_15d' => 'nullable|boolean',
            'auto_report_30d' => 'nullable|boolean',
            'report_send_time' => 'nullable|date_format:H:i',
            'report_send_via_email' => 'nullable|boolean',
            'report_send_via_sms' => 'nullable|boolean',
            'daily_closing_enabled' => 'nullable|boolean',
            
            // Email SMTP
            'mail_driver' => 'nullable|in:smtp,sendmail,mailgun',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            
            // Theme Colors
            'theme_primary_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            'theme_secondary_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            'theme_accent_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            'theme_text_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            'theme_dark_bg' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            'dashboard_animation_enabled' => 'nullable|boolean',
            'animation_speed' => 'nullable|in:slow,medium,fast',
            
            // Printing
            'thermal_printer_enabled' => 'nullable|boolean',
            'thermal_paper_width' => 'nullable|numeric|in:58,80',
            'print_logo' => 'nullable|boolean',
            'print_qr_code' => 'nullable|boolean',
            'print_fbr_sms' => 'nullable|boolean',
            
            // Tax Settings
            'fbr_enabled' => 'nullable|boolean',
            'provincial_tax_rate' => 'nullable|numeric|min:0|max:100',
            'sales_tax_rate_fuel' => 'nullable|numeric|min:0|max:100',
            'sales_tax_rate_nonfu' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'pos_service_fee_rate' => 'nullable|numeric|min:0',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $this->settingService->set($key, (string) $value);
            }
        }

        return back()->with('success', 'ترتیبات کامیابی کے ساتھ محفوظ ہو گئی ہیں۔ Settings saved successfully!');
    }
}
