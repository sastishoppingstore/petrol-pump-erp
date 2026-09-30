<?php

namespace App\Services\Tax;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * FBR & Tax Engine
 * Handles FBR digital invoicing, sales tax, petroleum levy toggles
 * Non-breaking: Existing invoice logic unchanged, tax calculation additive
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
            return [
                'fbr_enabled' => (bool) Setting::where('key', 'fbr_invoicing_enabled')->first()?->value ?? false,
                'sales_tax_enabled' => (bool) Setting::where('key', 'sales_tax_enabled')->first()?->value ?? false,
                'petroleum_levy_enabled' => (bool) Setting::where('key', 'petroleum_levy_enabled')->first()?->value ?? false,
                'sales_tax_rate' => (float) (Setting::where('key', 'sales_tax_rate')->first()?->value ?? 17.0),
                'petroleum_levy_rate' => (float) (Setting::where('key', 'petroleum_levy_rate')->first()?->value ?? 9.7),
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
