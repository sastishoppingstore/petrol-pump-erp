<?php

namespace App\Services\Invoicing;

use App\Models\Sale;
use App\Models\InvoiceTemplate;
use App\Models\InvoiceSnapshot;
use App\Models\DigitalSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceDesignerService
{
    const LAYOUT_MODERN_RED_BAND = 'MODERN_RED_BAND';
    const LAYOUT_CLASSIC = 'CLASSIC';
    const LAYOUT_MINIMAL = 'MINIMAL';

    const PAPER_A4 = 'A4';
    const PAPER_A5 = 'A5';
    const PAPER_THERMAL_80MM = 'THERMAL_80MM';
    const PAPER_THERMAL_58MM = 'THERMAL_58MM';

    /**
     * Get or create default invoice templates
     */
    public function initializeDefaultTemplates(): void
    {
        $defaults = [
            [
                'layout_type' => self::LAYOUT_MODERN_RED_BAND,
                'paper_size' => self::PAPER_A4,
                'description' => 'Modern layout with red top band, suitable for professional invoices',
                'design_config' => json_encode([
                    'header_bg_color' => '#cc0000',
                    'header_text_color' => '#ffffff',
                    'show_company_logo' => true,
                    'show_qr_code' => true,
                    'footer_text' => 'Thank you for your business!',
                    'accent_color' => '#cc0000',
                ]),
            ],
            [
                'layout_type' => self::LAYOUT_CLASSIC,
                'paper_size' => self::PAPER_A4,
                'description' => 'Classic business invoice layout',
                'design_config' => json_encode([
                    'header_bg_color' => '#ffffff',
                    'header_text_color' => '#000000',
                    'show_company_logo' => true,
                    'show_qr_code' => false,
                    'border_style' => 'solid',
                    'border_color' => '#333333',
                ]),
            ],
            [
                'layout_type' => self::LAYOUT_MINIMAL,
                'paper_size' => self::PAPER_THERMAL_80MM,
                'description' => 'Minimal thermal receipt format',
                'design_config' => json_encode([
                    'show_company_logo' => false,
                    'show_qr_code' => true,
                    'compact_mode' => true,
                    'font_size_reduction' => 0.8,
                ]),
            ],
        ];

        foreach ($defaults as $template) {
            InvoiceTemplate::firstOrCreate(
                ['layout_type' => $template['layout_type']],
                $template
            );
        }

        Log::info('Default invoice templates initialized');
    }

    /**
     * Generate invoice snapshot with layout
     */
    public function generateSnapshot(Sale $sale, string $layoutType = self::LAYOUT_MODERN_RED_BAND): InvoiceSnapshot
    {
        return DB::transaction(function () use ($sale, $layoutType) {
            // Get template
            $template = InvoiceTemplate::where('layout_type', $layoutType)->first();
            if (!$template) {
                $template = InvoiceTemplate::where('layout_type', self::LAYOUT_MODERN_RED_BAND)->first();
            }

            // Prepare invoice data
            $invoiceData = $this->prepareInvoiceData($sale);

            // Generate design snapshot
            $designSnapshot = $this->generateDesignSnapshot($invoiceData, $template);

            // Create snapshot record
            $snapshot = InvoiceSnapshot::create([
                'invoice_id' => $sale->id,
                'invoice_data' => json_encode($invoiceData),
                'design_snapshot' => json_encode($designSnapshot),
            ]);

            Log::info("Invoice snapshot generated", [
                'sale_id' => $sale->id,
                'layout' => $layoutType,
                'snapshot_id' => $snapshot->id,
            ]);

            return $snapshot;
        });
    }

    /**
     * Prepare complete invoice data including taxes, FBR compliance
     */
    private function prepareInvoiceData(Sale $sale): array
    {
        $fbrEnabled = setting('fbr_invoicing_enabled', false);
        $salesTaxEnabled = setting('sales_tax_enabled', false);
        $petroleumLevyEnabled = setting('petroleum_levy_enabled', false);

        $data = [
            'invoice_number' => $sale->invoice_number,
            'invoice_date' => $sale->sale_date->format('Y-m-d'),
            'invoice_time' => $sale->created_at->format('H:i:s'),
            'invoice_type' => $fbrEnabled ? 'FBR_FISCAL' : 'STANDARD',
            
            'seller' => [
                'name' => setting('company_name', 'Mehar Filling Station'),
                'address' => setting('company_address', ''),
                'phone' => setting('company_phone', ''),
                'ntn' => setting('company_ntn', ''),
                'strn' => setting('company_strn', ''),
            ],

            'buyer' => $sale->customer ? [
                'name' => $sale->customer->name,
                'phone' => $sale->customer->phone ?? '',
                'address' => $sale->customer->address ?? '',
                'ntn' => $sale->customer->ntn_number ?? '',
            ] : [
                'name' => 'Walk-in Customer',
                'phone' => '',
            ],

            'items' => $sale->items->map(function ($item) {
                return [
                    'description' => $item->fuelProduct->name ?? 'Fuel',
                    'quantity' => $item->litres,
                    'unit' => 'Litres',
                    'rate' => $item->rate,
                    'amount' => $item->amount,
                    'cost_rate' => $item->cost_rate,
                ];
            })->toArray(),

            'totals' => [
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount ?? 0,
                'subtotal_after_discount' => ($sale->subtotal - ($sale->discount ?? 0)),
                'sales_tax' => $salesTaxEnabled ? round(($sale->subtotal - ($sale->discount ?? 0)) * (setting('sales_tax_rate', 0) / 100), 2) : 0,
                'petroleum_levy' => $petroleumLevyEnabled ? round(($sale->subtotal - ($sale->discount ?? 0)) * (setting('petroleum_levy_rate', 0) / 100), 2) : 0,
                'total' => $sale->total,
            ],

            'payment' => [
                'method' => $sale->paymentMethod ?? 'CASH',
                'status' => $sale->status,
            ],

            'metadata' => [
                'branch' => $sale->branch->name ?? '',
                'shift' => $sale->shift->shift_number ?? '',
                'attendant' => $sale->employee->name ?? auth()->user()->name ?? '',
                'vehicle' => $sale->vehicle->registration_number ?? 'N/A',
                'meter_readings' => [
                    'start' => $sale->items->first()?->meter_start ?? null,
                    'end' => $sale->items->first()?->meter_end ?? null,
                ],
            ],

            'fbr' => $fbrEnabled ? $this->generateFbrData($sale) : null,
        ];

        return $data;
    }

    /**
     * Generate FBR-compliant data (QR code, STRN reference, etc)
     */
    private function generateFbrData(Sale $sale): array
    {
        return [
            'invoice_number' => $sale->invoice_number,
            'invoice_date' => $sale->sale_date->format('Y-m-d'),
            'total_amount' => $sale->total,
            'ntn' => setting('company_ntn', ''),
            'qr_payload' => $this->generateFbrQrPayload($sale),
            'qr_code_base64' => $this->generateQrCodeImage($this->generateFbrQrPayload($sale)),
            'certificate_serial' => setting('fbr_certificate_serial', ''),
        ];
    }

    /**
     * Generate FBR QR payload
     */
    private function generateFbrQrPayload(Sale $sale): string
    {
        $payload = implode('|', [
            setting('company_ntn', ''),
            $sale->invoice_number,
            $sale->sale_date->format('Y-m-d'),
            round($sale->total, 2),
            $sale->tax ?? 0,
            $sale->subtotal - ($sale->discount ?? 0),
            setting('fbr_certificate_serial', ''),
        ]);

        return base64_encode($payload);
    }

    /**
     * Generate QR code image (placeholder for actual QR generation)
     */
    private function generateQrCodeImage(string $payload): string
    {
        // In production, use a QR code library like endroid/qr-code
        // For now, return a placeholder
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="white"/><text x="50" y="50" text-anchor="middle" font-size="10" fill="black">QR</text></svg>'
        );
    }

    /**
     * Generate design snapshot (HTML structure for rendering)
     */
    private function generateDesignSnapshot(array $invoiceData, InvoiceTemplate $template): array
    {
        $layoutType = $template->layout_type;

        return match ($layoutType) {
            self::LAYOUT_MODERN_RED_BAND => $this->designModernRedBand($invoiceData, $template),
            self::LAYOUT_CLASSIC => $this->designClassic($invoiceData, $template),
            self::LAYOUT_MINIMAL => $this->designMinimal($invoiceData, $template),
            default => $this->designModernRedBand($invoiceData, $template),
        };
    }

    /**
     * Design: Modern Red Band
     */
    private function designModernRedBand(array $data, InvoiceTemplate $template): array
    {
        $config = json_decode($template->design_config, true);

        return [
            'layout' => 'MODERN_RED_BAND',
            'paper_size' => $template->paper_size,
            'sections' => [
                'header' => [
                    'bg_color' => $config['header_bg_color'],
                    'text_color' => $config['header_text_color'],
                    'content' => [
                        'company_logo' => $config['show_company_logo'] ? setting('company_logo_path', '') : '',
                        'company_name' => $data['seller']['name'],
                        'tagline' => '100% Authentic Fuel | Premium Service',
                    ],
                ],
                'invoice_details' => [
                    'invoice_number' => $data['invoice_number'],
                    'invoice_date' => $data['invoice_date'],
                    'type_badge' => $data['invoice_type'] === 'FBR_FISCAL' ? 'FBR FISCAL' : 'STANDARD',
                ],
                'buyer_seller' => [
                    'seller' => $data['seller'],
                    'buyer' => $data['buyer'],
                ],
                'items_table' => [
                    'columns' => ['Description', 'Qty (L)', 'Rate', 'Amount'],
                    'rows' => array_map(function ($item) {
                        return [
                            $item['description'],
                            number_format($item['quantity'], 3),
                            'Rs. ' . number_format($item['rate'], 2),
                            'Rs. ' . number_format($item['amount'], 2),
                        ];
                    }, $data['items']),
                ],
                'totals' => [
                    'subtotal' => 'Rs. ' . number_format($data['totals']['subtotal'], 2),
                    'discount' => $data['totals']['discount'] > 0 ? '-Rs. ' . number_format($data['totals']['discount'], 2) : 'N/A',
                    'sales_tax' => $data['totals']['sales_tax'] > 0 ? 'Rs. ' . number_format($data['totals']['sales_tax'], 2) : 'N/A',
                    'petroleum_levy' => $data['totals']['petroleum_levy'] > 0 ? 'Rs. ' . number_format($data['totals']['petroleum_levy'], 2) : 'N/A',
                    'total' => 'Rs. ' . number_format($data['totals']['total'], 2),
                ],
                'qr_code' => $data['fbr']['qr_code_base64'] ?? null,
                'footer' => [
                    'text' => $config['footer_text'] ?? 'Thank you for your business!',
                    'powered_by' => 'Powered by Mehar ERP v4',
                ],
            ],
        ];
    }

    /**
     * Design: Classic
     */
    private function designClassic(array $data, InvoiceTemplate $template): array
    {
        $config = json_decode($template->design_config, true);

        return [
            'layout' => 'CLASSIC',
            'paper_size' => $template->paper_size,
            'sections' => [
                'header' => [
                    'border_color' => $config['border_color'] ?? '#333333',
                    'company_name' => $data['seller']['name'],
                    'company_details' => [
                        'address' => $data['seller']['address'],
                        'phone' => $data['seller']['phone'],
                        'ntn' => $data['seller']['ntn'],
                    ],
                ],
                'invoice_meta' => [
                    'invoice_number' => $data['invoice_number'],
                    'date' => $data['invoice_date'],
                ],
                'buyer_section' => [
                    'title' => 'BILL TO:',
                    'name' => $data['buyer']['name'],
                    'address' => $data['buyer']['address'],
                    'phone' => $data['buyer']['phone'],
                ],
                'items' => $data['items'],
                'totals' => $data['totals'],
            ],
        ];
    }

    /**
     * Design: Minimal (Thermal)
     */
    private function designMinimal(array $data, InvoiceTemplate $template): array
    {
        return [
            'layout' => 'MINIMAL',
            'paper_size' => $template->paper_size,
            'compact' => true,
            'sections' => [
                'header' => [
                    'company' => $data['seller']['name'],
                    'receipt_type' => 'FUEL RECEIPT',
                ],
                'invoice_number' => $data['invoice_number'],
                'date_time' => $data['invoice_date'] . ' ' . $data['invoice_time'],
                'items' => array_map(function ($item) {
                    return $item['description'] . ' ' . number_format($item['quantity'], 3) . 'L @ Rs.' . number_format($item['rate'], 2) . ' = Rs.' . number_format($item['amount'], 2);
                }, $data['items']),
                'total' => 'TOTAL: Rs. ' . number_format($data['totals']['total'], 2),
                'payment_method' => $data['payment']['method'],
                'qr_code' => $data['fbr']['qr_code_base64'] ?? null,
            ],
        ];
    }

    /**
     * Get available layouts
     */
    public function getAvailableLayouts(): array
    {
        return [
            self::LAYOUT_MODERN_RED_BAND => 'Modern (Red Band)',
            self::LAYOUT_CLASSIC => 'Classic',
            self::LAYOUT_MINIMAL => 'Minimal (Thermal)',
        ];
    }

    /**
     * Get available paper sizes
     */
    public function getAvailablePaperSizes(): array
    {
        return [
            self::PAPER_A4 => 'A4 (210 × 297 mm)',
            self::PAPER_A5 => 'A5 (148 × 210 mm)',
            self::PAPER_THERMAL_80MM => 'Thermal 80mm',
            self::PAPER_THERMAL_58MM => 'Thermal 58mm',
        ];
    }

    /**
     * Add digital signature to invoice
     */
    public function addSignature(
        Sale $sale,
        string $signatureSvg,
        string $signerName,
        ?string $signerPhone = null
    ): DigitalSignature {
        return DigitalSignature::create([
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'signature_svg' => $signatureSvg,
            'signed_at' => now(),
            'signer_name' => $signerName,
            'signer_phone' => $signerPhone,
        ]);
    }

    /**
     * Export invoice as PDF (placeholder)
     */
    public function exportPdf(Sale $sale, string $layoutType = self::LAYOUT_MODERN_RED_BAND): string
    {
        // In production, use dompdf or similar
        // Return file path or download
        Log::info("PDF export requested", [
            'sale_id' => $sale->id,
            'layout' => $layoutType,
        ]);

        return 'invoice_' . $sale->invoice_number . '.pdf';
    }

    /**
     * Regenerate snapshot (for layout/config changes)
     */
    public function regenerateSnapshot(Sale $sale, string $layoutType = self::LAYOUT_MODERN_RED_BAND): InvoiceSnapshot
    {
        // Delete old snapshot
        InvoiceSnapshot::where('invoice_id', $sale->id)->delete();

        // Generate new one
        return $this->generateSnapshot($sale, $layoutType);
    }

    /**
     * Get invoice snapshot
     */
    public function getSnapshot(Sale $sale): ?InvoiceSnapshot
    {
        return InvoiceSnapshot::where('invoice_id', $sale->id)->latest()->first();
    }
}
