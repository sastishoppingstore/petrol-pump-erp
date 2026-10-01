<?php

namespace App\Services\Tax;

use App\Services\System\SettingService;
use Illuminate\Support\Facades\Cache;

/**
 * FBR & Tax Engine
 * Handles FBR digital invoicing, sales tax, petroleum levy toggles
 * Non-breaking: Existing invoice logic unchanged, tax calculation additive
 *
 * Settings are read through SettingService — the canonical keys are the
 * tax group keys (tax.fbr_invoicing_enabled, tax.sales_tax_enabled, ...).
 * The previous implementation queried the Setting model directly and read
 * the `value` column, but number-type settings are stored in
 * `value_numeric` (value stays NULL), so every toggle silently read as
 * "disabled" no matter what the admin saved.
 */
class FbrTaxEngine
{
    private bool $fbrEnabled;
    private bool $salesTaxEnabled;
    private bool $petroleumLevyEnabled;
    private float $salesTaxRate;
    private float $petroleumLevyRate;

    public function __construct()
    {
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $cached = Cache::remember('fbr_tax_settings', 3600, function () {
            $settings = app(SettingService::class);

            // Number-type settings come back as decimal strings ("1.0000"),
            // so toggles are compared numerically, never === '1'.
            $on = fn (?string $value): bool => (float) ($value ?? '0') > 0;

            return [
                'fbr_enabled' => $on($settings->get('fbr_invoicing_enabled')),
                'sales_tax_enabled' => $on($settings->get('sales_tax_enabled')),
                'petroleum_levy_enabled' => $on($settings->get('petroleum_levy_enabled')),
                'sales_tax_rate' => (float) ($settings->get('sales_tax_rate') ?? '17.0'),
                'petroleum_levy_rate' => (float) ($settings->get('petroleum_levy_rate') ?? '9.7'),
            ];
        });

        $this->fbrEnabled = $cached['fbr_enabled'];
        $this->salesTaxEnabled = $cached['sales_tax_enabled'];
        $this->petroleumLevyEnabled = $cached['petroleum_levy_enabled'];
        $this->salesTaxRate = $cached['sales_tax_rate'];
        $this->petroleumLevyRate = $cached['petroleum_levy_rate'];
    }

    /**
     * Calculate invoice total with optional FBR tax components
     */
    public function calculateInvoiceTotal(float $subtotal, float $discount = 0): array
    {
        $taxableAmount = $subtotal - $discount;
        
        $salesTax = $this->salesTaxEnabled ? bcmul($taxableAmount, bcdiv($this->salesTaxRate, 100, 4), 2) : 0;
        $petroleumLevy = $this->petroleumLevyEnabled ? bcmul($subtotal, bcdiv($this->petroleumLevyRate, 100, 4), 2) : 0;
        
        $total = bcadd($taxableAmount, bcadd($salesTax, $petroleumLevy, 2), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxable_amount' => $taxableAmount,
            'sales_tax' => $salesTax,
            'petroleum_levy' => $petroleumLevy,
            'total' => $total,
            'tax_breakdown' => [
                'fbr_enabled' => $this->fbrEnabled,
                'sales_tax_enabled' => $this->salesTaxEnabled,
                'petroleum_levy_enabled' => $this->petroleumLevyEnabled,
            ]
        ];
    }

    /**
     * Generate FBR-compliant QR code payload
     */
    public function generateFbrQrPayload(string $invoiceNumber, float $total, string $ntn, string $date): string
    {
        if (!$this->fbrEnabled) {
            return '';
        }

        // FBR format: INV_NUM|NTN|DATE|AMOUNT|HASH
        $payload = implode('|', [
            $invoiceNumber,
            $ntn,
            $date,
            number_format($total, 2, '.', ''),
            hash('sha256', $invoiceNumber . $ntn . $date . $total)
        ]);

        return base64_encode($payload);
    }

    /**
     * Get invoice layout based on FBR status and user selection
     */
    public function getInvoiceLayout(string $preferredLayout = 'MODERN_RED_BAND'): array
    {
        return [
            'layout' => $preferredLayout,
            'show_tax_lines' => $this->fbrEnabled || $this->salesTaxEnabled,
            'show_fbr_qr' => $this->fbrEnabled,
            'show_petroleum_levy' => $this->petroleumLevyEnabled,
            'show_ntn_strn' => $this->fbrEnabled,
            'receipt_type' => $this->fbrEnabled ? 'FISCAL_INVOICE' : 'STANDARD_RECEIPT'
        ];
    }

    /**
     * Clear cache when settings change
     */
    public static function clearCache(): void
    {
        Cache::forget('fbr_tax_settings');
    }

    public function isFbrEnabled(): bool { return $this->fbrEnabled; }
    public function isSalesTaxEnabled(): bool { return $this->salesTaxEnabled; }
    public function isPetroleumLevyEnabled(): bool { return $this->petroleumLevyEnabled; }
}
