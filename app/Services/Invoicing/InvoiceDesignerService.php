<?php

namespace App\Services\Invoicing;

use App\Models\FbrInvoice;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\InvoiceTemplate;
use App\Models\InvoiceSnapshot;
use App\Models\DigitalSignature;
use App\Services\Sale\InvoiceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class InvoiceDesignerService
{
    const LAYOUT_MODERN_RED_BAND = 'MODERN_RED_BAND';
    const LAYOUT_CLASSIC = 'CLASSIC';
    const LAYOUT_MINIMAL = 'MINIMAL';

    const PAPER_A4 = 'A4';
    const PAPER_A5 = 'A5';
    const PAPER_THERMAL_80MM = 'THERMAL_80MM';
    const PAPER_THERMAL_58MM = 'THERMAL_58MM';

    public function __construct(
        private readonly InvoiceService $invoices,
    ) {
    }

    /**
     * Layout constant -> invoice_templates.slug (migration 008000 schema:
     * templates are keyed by slug and carry their design in `config` JSON —
     * the old layout_type / design_config columns never existed).
     */
    private function templateForLayout(string $layoutType): InvoiceTemplate
    {
        $slug = match ($layoutType) {
            self::LAYOUT_MODERN_RED_BAND => InvoiceTemplate::SLUG_MODERN_RED_BAND,
            self::LAYOUT_CLASSIC => InvoiceTemplate::SLUG_CLASSIC,
            self::LAYOUT_MINIMAL => InvoiceTemplate::SLUG_MINIMAL,
            default => null,
        };

        $template = $slug
            ? InvoiceTemplate::where('slug', $slug)->where('is_active', true)->first()
            : null;

        return $template ?? InvoiceTemplate::defaultTemplate();
    }

    /**
     * Get or create default invoice templates (canonical slug/config schema).
     */
    public function initializeDefaultTemplates(): void
    {
        $defaults = [
            [
                'slug' => InvoiceTemplate::SLUG_MODERN_RED_BAND,
                'name' => 'Modern Red Band',
                'paper_size' => InvoiceTemplate::PAPER_A4,
                'description' => 'Modern layout with red top band, suitable for professional invoices',
                'is_default' => true,
                'config' => [
                    'header_bg_color' => '#cc0000',
                    'header_text_color' => '#ffffff',
                    'show_company_logo' => true,
                    'show_qr_code' => true,
                    'footer_text' => 'Thank you for your business!',
                    'accent_color' => '#cc0000',
                ],
            ],
            [
                'slug' => InvoiceTemplate::SLUG_CLASSIC,
                'name' => 'Classic',
                'paper_size' => InvoiceTemplate::PAPER_A4,
                'description' => 'Classic business invoice layout',
                'config' => [
                    'header_bg_color' => '#ffffff',
                    'header_text_color' => '#000000',
                    'show_company_logo' => true,
                    'show_qr_code' => false,
                    'border_style' => 'solid',
                    'border_color' => '#333333',
                ],
            ],
            [
                'slug' => InvoiceTemplate::SLUG_MINIMAL,
                'name' => 'Minimal (Thermal)',
                'paper_size' => InvoiceTemplate::PAPER_THERMAL_80MM,
                'description' => 'Minimal thermal receipt format',
                'config' => [
                    'show_company_logo' => false,
                    'show_qr_code' => true,
                    'compact_mode' => true,
                    'font_size_reduction' => 0.8,
                ],
            ],
        ];

        foreach ($defaults as $template) {
            InvoiceTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        Log::info('Default invoice templates initialized');
    }

    /**
     * Generate invoice snapshot with layout.
     *
     * Canonical path: the snapshot is the immutable record InvoiceService
     * freezes at invoice issuance (invoice_snapshots: station/customer/
     * items/payment/theme snapshots + snapshot_hash, keyed by the INVOICE
     * id — the previous implementation wrote phantom invoice_data /
     * design_snapshot columns and keyed the row by the SALE id).
     *
     * The requested layout only takes effect when the sale's invoice is
     * first created; an already-issued invoice keeps the snapshot frozen
     * at issuance, exactly as the immutability rule requires.
     */
    public function generateSnapshot(Sale $sale, string $layoutType = self::LAYOUT_MODERN_RED_BAND): InvoiceSnapshot
    {
        $template = $this->templateForLayout($layoutType);

        $invoice = $this->invoices->invoiceForSale($sale, $template);

        $snapshot = $invoice->snapshot;

        if (! $snapshot) {
            // invoiceForSale()/createInvoice() always freezes a snapshot;
            // reaching this point means data corruption, not a user error.
            throw new \RuntimeException("Invoice {$invoice->invoice_number} has no snapshot.");
        }

        Log::info('Invoice snapshot generated', [
            'sale_id' => $sale->id,
            'invoice_id' => $invoice->id,
            'layout' => $layoutType,
            'snapshot_id' => $snapshot->id,
        ]);

        return $snapshot;
    }

    /**
     * Prepare complete invoice data including taxes, FBR compliance.
     *
     * Design-representation builder for previews; the persisted legal
     * snapshot is created by InvoiceService at issuance (see
     * generateSnapshot()).
     */
    private function prepareInvoiceData(Sale $sale): array
    {
        $sale->loadMissing(['items.fuelProduct', 'customer', 'payments', 'branch', 'shift', 'employee', 'vehicle']);

        // Number-type settings can come back as decimal strings ("0.0000"),
        // which are truthy in PHP — normalise to real booleans numerically.
        $fbrEnabled = (float) (setting('fbr_invoicing_enabled', 0) ?? 0) > 0;
        $salesTaxEnabled = (float) (setting('sales_tax_enabled', 0) ?? 0) > 0;
        $petroleumLevyEnabled = (float) (setting('petroleum_levy_enabled', 0) ?? 0) > 0;

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
                // Sale has no paymentMethod attribute; tenders live on the
                // payments relation (split payments join with '+').
                'method' => $sale->payments->pluck('method')->filter()->implode('+') ?: 'CASH',
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
     * Generate FBR-compliant data (QR code, STRN reference, etc).
     *
     * Prefers the sale's real FbrInvoice record (fiscal number + stored QR
     * payload created by FbrInvoiceService) so a designed/previewed invoice
     * shows the same fiscal identity as the printed one.
     */
    private function generateFbrData(Sale $sale): array
    {
        $fbrInvoice = FbrInvoice::where('sale_id', $sale->id)->first();

        $qrPayload = $fbrInvoice
            ? json_encode($fbrInvoice->qr_payload, JSON_UNESCAPED_UNICODE)
            : $this->generateFbrQrPayload($sale);

        return [
            'invoice_number' => $fbrInvoice?->fiscal_number ?? $sale->invoice_number,
            'invoice_date' => $sale->sale_date->format('Y-m-d'),
            'total_amount' => $sale->total,
            'ntn' => setting('company_ntn', ''),
            'qr_payload' => $qrPayload,
            'qr_code_base64' => $this->generateQrCodeImage($qrPayload),
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
     * Generate a real QR code image (SVG data URI) for a payload, using the
     * installed simplesoftwareio/simple-qrcode library. Returns an empty
     * string if generation fails — never a fake placeholder graphic.
     */
    private function generateQrCodeImage(string $payload): string
    {
        try {
            $svg = (string) QrCode::format('svg')->size(120)->margin(1)->generate($payload);
            $svg = preg_replace('/<\?xml.*?\?>/', '', $svg) ?? $svg;

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (Throwable $e) {
            Log::warning('QR code generation failed', ['error' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * Generate design snapshot (HTML structure for rendering).
     *
     * Templates are keyed by slug and carry design options in the `config`
     * JSON column (migration 008000) — there are no layout_type /
     * design_config columns.
     */
    private function generateDesignSnapshot(array $invoiceData, InvoiceTemplate $template): array
    {
        return match ($template->slug) {
            InvoiceTemplate::SLUG_MODERN_RED_BAND => $this->designModernRedBand($invoiceData, $template),
            InvoiceTemplate::SLUG_CLASSIC => $this->designClassic($invoiceData, $template),
            InvoiceTemplate::SLUG_MINIMAL => $this->designMinimal($invoiceData, $template),
            default => $this->designModernRedBand($invoiceData, $template),
        };
    }

    /**
     * Design: Modern Red Band
     */
    private function designModernRedBand(array $data, InvoiceTemplate $template): array
    {
        $config = $template->config ?? [];

        return [
            'layout' => 'MODERN_RED_BAND',
            'paper_size' => $template->paper_size,
            'sections' => [
                'header' => [
                    'bg_color' => $config['header_bg_color'] ?? $template->header_bg,
                    'text_color' => $config['header_text_color'] ?? $template->header_text,
                    'content' => [
                        'company_logo' => ($config['show_company_logo'] ?? $template->show_logo) ? setting('company_logo_path', '') : '',
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
        $config = $template->config ?? [];

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
     * Export invoice as PDF.
     *
     * Asal implementation: is layout ka invoice + frozen snapshot ensure
     * karke canonical PDF path (InvoiceService::generatePdf) se render
     * hota hai aur file storage/app/invoices/exports me save hoti hai —
     * sirf filename wapas jata hai (controller isi ki ummeed karta hai).
     * Pehle ye placeholder tha: koi PDF banta hi nahi tha.
     */
    public function exportPdf(Sale $sale, string $layoutType = self::LAYOUT_MODERN_RED_BAND): string
    {
        $this->generateSnapshot($sale, $layoutType);

        $invoice = $this->invoices->invoiceForSale($sale);
        $pdf = $this->invoices->generatePdf($invoice);

        $filename = 'invoice_' . $sale->invoice_number . '.pdf';
        Storage::disk('local')->put('invoices/exports/' . $filename, $pdf->output());

        Log::info('PDF export completed', [
            'sale_id' => $sale->id,
            'invoice_id' => $invoice->id,
            'layout' => $layoutType,
            'filename' => $filename,
        ]);

        return $filename;
    }

    /**
     * Regenerate snapshot (for layout/config changes).
     *
     * Snapshots are immutable legal records (InvoiceSnapshot throws on
     * update, and history is append-only), so an issued snapshot is never
     * deleted or rewritten. "Regeneration" therefore means: make sure the
     * sale's invoice and its canonical snapshot exist, and return the
     * authoritative record.
     */
    public function regenerateSnapshot(Sale $sale, string $layoutType = self::LAYOUT_MODERN_RED_BAND): InvoiceSnapshot
    {
        return $this->generateSnapshot($sale, $layoutType);
    }

    /**
     * Get invoice snapshot for a sale (via its Invoice — snapshots are
     * keyed by invoice_id, not sale_id).
     */
    public function getSnapshot(Sale $sale): ?InvoiceSnapshot
    {
        $invoice = Invoice::where('sale_id', $sale->id)->first();

        return $invoice?->snapshot;
    }
}
