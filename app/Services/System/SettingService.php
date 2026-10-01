<?php

namespace App\Services\System;

use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Schema;

/**
 * Application settings (spec section 4).
 *
 * Text and numeric settings are stored in different columns so a money
 * threshold never round-trips through a string. Every getter returns a plain
 * string, so callers never touch a float.
 */
class SettingService
{
    /**
     * Declared settings. Each entry declares its own type.
     *
     * @var array<string, array<string, array{type: string, default: string|null}>>
     */
    private const SCHEMA = [
        'station' => [
            'station_identity' => ['type' => 'text', 'default' => null],
            'station_name' => ['type' => 'text', 'default' => 'Mehar Filling Station (مہر فلنگ اسٹیشن)'],
            'station_name_en' => ['type' => 'text', 'default' => 'Mehar Filling Station'],
            'station_name_ur' => ['type' => 'text', 'default' => 'مہر فلنگ اسٹیشن'],
            'omc_brand' => ['type' => 'text', 'default' => 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)'],
            'omc_brand_name' => ['type' => 'text', 'default' => 'Vital Petroleum Pvt. Ltd.'],
            'omc_branch' => ['type' => 'text', 'default' => 'Vital Petrol Branch • وائٹل پیٹرول'],
            'owner_name' => ['type' => 'text', 'default' => 'Muhammad Rizwan Aslam (محمد رضوان اسلم)'],
            'owner_name_en' => ['type' => 'text', 'default' => 'Muhammad Rizwan Aslam'],
            'owner_name_ur' => ['type' => 'text', 'default' => 'محمد رضوان اسلم'],
            'phone' => ['type' => 'text', 'default' => '0300-4342343'],
            'whatsapp' => ['type' => 'text', 'default' => 'wa.me/923004342343'],
            'address' => [
                'type' => 'text',
                'default' => 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan',
            ],
            'currency' => ['type' => 'text', 'default' => 'PKR'],
            'currency_symbol' => ['type' => 'text', 'default' => 'Rs.'],
            'number_format' => ['type' => 'text', 'default' => 'lakh'],
            'default_language' => ['type' => 'text', 'default' => 'ur'],
            'theme_primary' => ['type' => 'text', 'default' => '#D71920'],
            'theme_dark_red' => ['type' => 'text', 'default' => '#A30F15'],
            'theme_white' => ['type' => 'text', 'default' => '#FFFFFF'],
            'theme_light_grey' => ['type' => 'text', 'default' => '#F6F6F6'],
            'theme_text' => ['type' => 'text', 'default' => '#1B1B1B'],
        ],

        'company' => [
            'company_name' => ['type' => 'text', 'default' => 'Mehar Filling Station (مہر فلنگ اسٹیشن)'],
            'owner_name' => ['type' => 'text', 'default' => 'Muhammad Rizwan Aslam'],
            'phone' => ['type' => 'text', 'default' => '0300-4342343'],
            'address' => [
                'type' => 'text',
                'default' => 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan',
            ],
            'email' => ['type' => 'text', 'default' => null],
            'ntn' => ['type' => 'text', 'default' => null],
            'strn' => ['type' => 'text', 'default' => null],
            'province' => ['type' => 'text', 'default' => 'Punjab'],
            'tax_authority' => ['type' => 'text', 'default' => 'PRA'],
        ],

        'invoice' => [
            'currency_code' => ['type' => 'text', 'default' => 'PKR'],
            'currency_symbol' => ['type' => 'text', 'default' => 'Rs.'],
            'thank_you_message' => ['type' => 'text', 'default' => 'Thank you for your patronage. Drive safely.'],
            // SRO 1006(I)/2021 requires a Rs.1 PoS service fee per invoice.
            'pos_service_fee' => ['type' => 'number', 'default' => '1.00'],
            // Buyer name/CNIC/NTN is mandatory above this invoice value.
            'buyer_details_required_above' => ['type' => 'number', 'default' => '100000.00'],
            'fbr_invoicing_enabled' => ['type' => 'number', 'default' => '0'],
        ],

        'business' => [
            'shift_variance_threshold' => ['type' => 'number', 'default' => '100.00'],
            'meter_variance_tolerance' => ['type' => 'number', 'default' => '0.500'],
            'credit_overdue_days' => ['type' => 'number', 'default' => '30'],
            'auto_shift_close_time' => ['type' => 'text', 'default' => '23:00'],
            'low_stock_threshold_liters' => ['type' => 'number', 'default' => '500.00'],
            'payment_methods_enabled' => ['type' => 'text', 'default' => 'cash,card,bank,mobile,credit'],
        ],

        'notifications' => [
            'sms_provider' => ['type' => 'text', 'default' => 'jazz'],
            'sms_api_key' => ['type' => 'text', 'default' => null],
            'sms_sender_id' => ['type' => 'text', 'default' => 'MEHAR'],
            'email_enabled' => ['type' => 'number', 'default' => '1'],
            'sms_enabled' => ['type' => 'number', 'default' => '1'],
            'low_stock_alert_enabled' => ['type' => 'number', 'default' => '1'],
            'variance_alert_enabled' => ['type' => 'number', 'default' => '1'],
            'overdue_credit_alert_enabled' => ['type' => 'number', 'default' => '1'],
            'pending_approval_alert_enabled' => ['type' => 'number', 'default' => '1'],
        ],

        'reports' => [
            'auto_report_12h' => ['type' => 'number', 'default' => '1'],
            'auto_report_24h' => ['type' => 'number', 'default' => '1'],
            'auto_report_7d' => ['type' => 'number', 'default' => '1'],
            'auto_report_15d' => ['type' => 'number', 'default' => '1'],
            'auto_report_30d' => ['type' => 'number', 'default' => '1'],
            'report_send_time' => ['type' => 'text', 'default' => '23:00'],
            'report_send_via_email' => ['type' => 'number', 'default' => '1'],
            'report_send_via_sms' => ['type' => 'number', 'default' => '1'],
            'report_email_recipients' => ['type' => 'text', 'default' => null],
            'auto_report_custom_hours' => ['type' => 'number', 'default' => '0'],
            'daily_closing_enabled' => ['type' => 'number', 'default' => '1'],
        ],

        'email' => [
            'mail_driver' => ['type' => 'text', 'default' => 'smtp'],
            'mail_host' => ['type' => 'text', 'default' => 'smtp.gmail.com'],
            'mail_port' => ['type' => 'number', 'default' => '587'],
            'mail_username' => ['type' => 'text', 'default' => null],
            'mail_password' => ['type' => 'text', 'default' => null],
            'mail_encryption' => ['type' => 'text', 'default' => 'tls'],
            'mail_from_address' => ['type' => 'text', 'default' => 'noreply@meharfilling.com'],
            'mail_from_name' => ['type' => 'text', 'default' => 'Mehar Filling Station'],
        ],

        'theme' => [
            'theme_primary_color' => ['type' => 'text', 'default' => '#D71920'],
            'theme_secondary_color' => ['type' => 'text', 'default' => '#27AE60'],
            'theme_accent_color' => ['type' => 'text', 'default' => '#FFFFFF'],
            'theme_text_color' => ['type' => 'text', 'default' => '#1B1B1B'],
            'theme_dark_bg' => ['type' => 'text', 'default' => '#F6F6F6'],
            'dashboard_animation_enabled' => ['type' => 'number', 'default' => '1'],
            'animation_speed' => ['type' => 'text', 'default' => 'medium'],
        ],

        'printing' => [
            'thermal_printer_enabled' => ['type' => 'number', 'default' => '1'],
            'thermal_paper_width' => ['type' => 'number', 'default' => '80'],
            'print_logo' => ['type' => 'number', 'default' => '1'],
            'print_qr_code' => ['type' => 'number', 'default' => '1'],
            'print_fbr_sms' => ['type' => 'number', 'default' => '1'],
        ],

        'tax' => [
            'fbr_invoicing_enabled' => ['type' => 'number', 'default' => '0'],
            'sales_tax_enabled' => ['type' => 'number', 'default' => '1'],
            'petroleum_levy_enabled' => ['type' => 'number', 'default' => '1'],
            'provincial_tax_rate' => ['type' => 'number', 'default' => '16.00'],
            'sales_tax_rate' => ['type' => 'number', 'default' => '17.00'],
            'petroleum_levy_rate' => ['type' => 'number', 'default' => '9.70'],
            'sales_tax_rate_nonfu' => ['type' => 'number', 'default' => '17.00'],
            'withholding_tax_rate' => ['type' => 'number', 'default' => '2.00'],
            'pos_service_fee_rate' => ['type' => 'number', 'default' => '1.00'],
        ],
    ];

    public function get(string $key, ?string $default = null): ?string
    {
        $schema = $this->schemaFor($key);
        $fallback = $default ?? $schema['default'] ?? null;

        if (! Schema::hasTable('settings')) {
            return $fallback;
        }

        $row = Setting::query()->where('key', $key)->first();

        if (! $row) {
            return $fallback;
        }

        return $schema['type'] === 'number'
            ? ($row->value_numeric ?? $fallback)
            : ($row->value ?? $fallback);
    }

    /** A numeric setting as an exact decimal string. */
    public function money(string $key): string
    {
        return Money::n($this->get($key) ?? '0');
    }

    public function set(string $key, ?string $value, ?string $group = null): void
    {
        $schema = $this->schemaFor($key);
        $isNumber = $schema['type'] === 'number';

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group ?? $this->groupFor($key) ?? 'general',
                'value' => $isNumber ? null : $value,
                'value_numeric' => $isNumber ? $value : null,
                'value_type' => $isNumber ? Setting::TYPE_NUMBER : Setting::TYPE_TEXT,
            ],
        );
    }

    /**
     * Every setting in a group, keyed by setting key.
     *
     * @return array<string, string|null>
     */
    public function group(string $group): array
    {
        $out = [];

        foreach (self::SCHEMA[$group] ?? [] as $key => $schema) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }

    /**
     * Station identity, used by the app shell, bill designer, and every invoice.
     *
     * @return array<string, mixed>
     */
    public function stationIdentity(): array
    {
        $raw = $this->get('station_identity');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'station_name' => $this->get('station_name', 'Mehar Filling Station (مہر فلنگ اسٹیشن)'),
            'station_name_en' => $this->get('station_name_en', 'Mehar Filling Station'),
            'station_name_ur' => $this->get('station_name_ur', 'مہر فلنگ اسٹیشن'),
            'omc_brand' => $this->get('omc_brand', 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)'),
            'omc_brand_name' => $this->get('omc_brand_name', 'Vital Petroleum Pvt. Ltd.'),
            'omc_branch' => $this->get('omc_branch', 'Vital Petrol Branch • وائٹل پیٹرول'),
            'owner' => $this->get('owner_name', 'Muhammad Rizwan Aslam (محمد رضوان اسلم)'),
            'owner_name' => $this->get('owner_name_en', 'Muhammad Rizwan Aslam'),
            'owner_name_ur' => $this->get('owner_name_ur', 'محمد رضوان اسلم'),
            'phone' => $this->get('phone', '0300-4342343'),
            'whatsapp' => $this->get('whatsapp', 'wa.me/923004342343'),
            'whatsapp_url' => 'https://wa.me/923004342343',
            'address' => $this->get('address', 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan'),
            'currency' => $this->get('currency', 'PKR'),
            'currency_symbol' => $this->get('currency_symbol', 'Rs.'),
            'number_format' => $this->get('number_format', 'lakh'),
            'default_language' => $this->get('default_language', 'ur'),
            'theme' => $this->themeTokens(),
        ];
    }

    /**
     * Vital Red + White brand theme tokens.
     *
     * @return array<string, string>
     */
    public function themeTokens(): array
    {
        return [
            'primary' => $this->get('theme_primary', '#D71920'),
            'dark_red' => $this->get('theme_dark_red', '#A30F15'),
            'white' => $this->get('theme_white', '#FFFFFF'),
            'light_grey' => $this->get('theme_light_grey', '#F6F6F6'),
            'text' => $this->get('theme_text', '#1B1B1B'),
        ];
    }

    /**
     * Station identity, used by the app shell and every invoice.
     *
     * @return array<string, string|null>
     */
    public function company(): array
    {
        $station = $this->stationIdentity();
        $company = $this->group('company');

        return array_merge([
            'company_name' => $station['station_name'],
            'owner_name' => $station['owner_name'],
            'phone' => $station['phone'],
            'whatsapp' => $station['whatsapp'],
            'address' => $station['address'],
            'omc_brand' => $station['omc_brand'],
            'currency' => $station['currency'],
            'number_format' => $station['number_format'],
            'default_language' => $station['default_language'],
        ], $company);
    }

    /**
     * Insert any missing setting from the declared defaults. Never overwrites
     * a value that already exists, so re-running is safe.
     */
    public function seedDefaults(): void
    {
        foreach (self::SCHEMA as $group => $entries) {
            foreach ($entries as $key => $schema) {
                if ($schema['default'] === null) {
                    continue;
                }

                if (Setting::where('key', $key)->exists()) {
                    continue;
                }

                $this->set($key, $schema['default'], $group);
            }
        }

        // Seed or maintain single comprehensive station_identity record
        $identity = [
            'station_name' => $this->get('station_name', 'Mehar Filling Station (مہر فلنگ اسٹیشن)'),
            'station_name_en' => 'Mehar Filling Station',
            'station_name_ur' => 'مہر فلنگ اسٹیشن',
            'omc_brand' => $this->get('omc_brand', 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)'),
            'omc_brand_name' => 'Vital Petroleum Pvt. Ltd.',
            'omc_branch' => 'Vital Petrol Branch • وائٹل پیٹرول',
            'owner' => $this->get('owner_name', 'Muhammad Rizwan Aslam (محمد رضوان اسلم)'),
            'owner_name' => 'Muhammad Rizwan Aslam',
            'owner_name_ur' => 'محمد رضوان اسلم',
            'phone' => $this->get('phone', '0300-4342343'),
            'whatsapp' => $this->get('whatsapp', 'wa.me/923004342343'),
            'whatsapp_url' => 'https://wa.me/923004342343',
            'address' => $this->get('address', 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan'),
            'currency' => $this->get('currency', 'PKR'),
            'currency_symbol' => $this->get('currency_symbol', 'Rs.'),
            'number_format' => $this->get('number_format', 'lakh'),
            'default_language' => $this->get('default_language', 'ur'),
            'theme' => [
                'primary' => '#D71920',
                'dark_red' => '#A30F15',
                'white' => '#FFFFFF',
                'light_grey' => '#F6F6F6',
                'text' => '#1B1B1B',
            ],
        ];

        Setting::updateOrCreate(
            ['key' => 'station_identity'],
            [
                'group' => 'station',
                'value' => json_encode($identity, JSON_UNESCAPED_UNICODE),
                'value_numeric' => null,
                'value_type' => Setting::TYPE_TEXT,
                'description' => 'Mehar Filling Station complete identity profile (Vital Petroleum franchise)',
                'is_public' => true,
            ]
        );
    }

    /**
     * @return array{type: string, default: string|null}
     */
    private function schemaFor(string $key): array
    {
        foreach (self::SCHEMA as $entries) {
            if (isset($entries[$key])) {
                return $entries[$key];
            }
        }

        return ['type' => 'text', 'default' => null];
    }

    private function groupFor(string $key): ?string
    {
        foreach (self::SCHEMA as $group => $entries) {
            if (isset($entries[$key])) {
                return $group;
            }
        }

        return null;
    }
}
