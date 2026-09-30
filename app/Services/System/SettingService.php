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
        'company' => [
            'company_name' => ['type' => 'text', 'default' => 'Mehar Filling Station'],
            'owner_name' => ['type' => 'text', 'default' => 'Muhammad Rizwan Aslam'],
            'phone' => ['type' => 'text', 'default' => '0300-4342343'],
            'address' => [
                'type' => 'text',
                'default' => 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Pakistan',
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
     * Station identity, used by the app shell and every invoice.
     *
     * @return array<string, string|null>
     */
    public function company(): array
    {
        return $this->group('company');
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
