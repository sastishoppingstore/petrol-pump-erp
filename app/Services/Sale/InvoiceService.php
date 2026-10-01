<?php

namespace App\Services\Sale;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSnapshot;
use App\Models\InvoiceTemplate;
use App\Models\Sale;
use App\Services\System\NumberSequenceService;
use App\Services\System\SettingService;
use App\Support\AmountInWords;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceService
{
    public function __construct(
        private readonly NumberSequenceService $numbers,
        private readonly SettingService $settings,
    ) {}

    /**
     * Generate next gapless row-locked sequential invoice number: MFS-{YYYY}-{000000}.
     */
    public function generateInvoiceNumber(?DateTimeInterface $date = null): string
    {
        $date ??= now();

        return DB::transaction(function () use ($date) {
            return $this->numbers->next('mfs_invoice', $date);
        });
    }

    /**
     * Create an official Invoice with gapless numbering, immutable snapshot, and QR payload.
     */
    public function createInvoice(array $data, array $items = [], ?InvoiceTemplate $template = null): Invoice
    {
        return DB::transaction(function () use ($data, $items, $template) {
            $template ??= InvoiceTemplate::defaultTemplate();
            $invoiceDate = isset($data['invoice_date']) ? Carbon::parse($data['invoice_date']) : now();
            $invoiceNumber = $data['invoice_number'] ?? $this->generateInvoiceNumber($invoiceDate);

            // Compute totals
            $subtotal = '0.00';
            foreach ($items as $item) {
                $qty = (string) ($item['quantity'] ?? '0.000');
                $price = (string) ($item['unit_price'] ?? '0.00');
                $itemTotal = $item['total_amount'] ?? Money::amountForLitres($qty, $price);
                $subtotal = Money::add($subtotal, (string) $itemTotal);
            }

            $discount = Money::round((string) ($data['discount_amount'] ?? '0.00'));
            $taxAmount = Money::round((string) ($data['tax_amount'] ?? '0.00'));
            $posFee = Money::round((string) ($data['pos_fee'] ?? $this->settings->money('pos_service_fee')));

            $total = Money::subtract(Money::add(Money::add($subtotal, $taxAmount), $posFee), $discount);
            $paid = Money::round((string) ($data['paid_amount'] ?? $total));
            $balanceDue = Money::subtract($total, $paid);
            if (Money::compare($balanceDue, '0.00') < 0) {
                $balanceDue = '0.00';
            }

            $previousBalance = Money::round((string) ($data['previous_balance'] ?? '0.00'));
            $closingBalance = Money::add($previousBalance, $balanceDue);

            // Verification hash: unique 32-hex cryptographic verification identifier
            $hash = $data['hash'] ?? bin2hex(random_bytes(16));

            // Words conversion
            $wordsUrdu = AmountInWords::toUrdu($total);
            $wordsEnglish = AmountInWords::toEnglish($total);

            // QR payload
            $verifyUrl = route('invoice.verify', ['hash' => $hash]);
            $qrPayload = [
                'station' => 'Mehar Filling Station',
                'omc' => 'Vital Petroleum',
                'invoice_no' => $invoiceNumber,
                'date' => $invoiceDate->format('d/m/Y H:i:s'),
                'total' => $total,
                'verify_url' => $verifyUrl,
                'hash' => $hash,
            ];

            $invoice = Invoice::create([
                'branch_id' => $data['branch_id'] ?? null,
                'sale_id' => $data['sale_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'shift_id' => $data['shift_id'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'template_id' => $template->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'due_date' => isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
                'type' => $data['type'] ?? Invoice::TYPE_SALE,
                'status' => $data['status'] ?? (Money::compare($balanceDue, '0.00') <= 0 ? Invoice::STATUS_PAID : Invoice::STATUS_ISSUED),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'pos_fee' => $posFee,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'balance_due' => $balanceDue,
                'previous_balance' => $previousBalance,
                'closing_balance' => $closingBalance,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_details' => $data['payment_details'] ?? null,
                'currency' => $data['currency'] ?? 'PKR',
                'amount_in_words_ur' => $wordsUrdu,
                'amount_in_words_en' => $wordsEnglish,
                'hash' => $hash,
                'qr_payload' => $qrPayload,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create items
            $savedItems = [];
            foreach ($items as $item) {
                $qty = (string) ($item['quantity'] ?? '0.000');
                $price = (string) ($item['unit_price'] ?? '0.00');
                $itemTotal = (string) ($item['total_amount'] ?? Money::amountForLitres($qty, $price));

                $savedItem = $invoice->items()->create([
                    'fuel_product_id' => $item['fuel_product_id'] ?? null,
                    'nozzle_id' => $item['nozzle_id'] ?? null,
                    'item_description' => $item['item_description'] ?? 'Fuel Sale',
                    'item_type' => $item['item_type'] ?? 'fuel',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $item['tax_rate'] ?? 0.00,
                    'tax_amount' => $item['tax_amount'] ?? 0.00,
                    'discount' => $item['discount'] ?? 0.00,
                    'total_amount' => $itemTotal,
                    'meter_start' => $item['meter_start'] ?? null,
                    'meter_end' => $item['meter_end'] ?? null,
                ]);

                $savedItems[] = [
                    'id' => $savedItem->id,
                    'description' => $savedItem->item_description,
                    'quantity' => $savedItem->quantity,
                    'unit_price' => $savedItem->unit_price,
                    'total_amount' => $savedItem->total_amount,
                ];
            }

            // Create immutable snapshot at issuance time
            $this->createSnapshot($invoice, $template, $savedItems, $data);

            return $invoice->load(['items', 'snapshot', 'template']);
        });
    }

    /**
     * The Invoice for a sale, creating it on first call.
     *
     * Idempotent: one sale maps to one official invoice. The sale-completion
     * hook in SaleService (and any retry/reprint path) may call this any
     * number of times — an existing invoice is returned untouched, never
     * duplicated.
     */
    public function invoiceForSale(Sale $sale, ?InvoiceTemplate $template = null): Invoice
    {
        $existing = Invoice::query()
            ->where('sale_id', $sale->id)
            ->first();

        if ($existing) {
            return $existing->loadMissing(['items', 'snapshot', 'template']);
        }

        return $this->createFromSale($sale, $template);
    }

    /**
     * Create an Invoice from a POS Sale.
     */
    public function createFromSale(Sale $sale, ?InvoiceTemplate $template = null): Invoice
    {
        $sale->loadMissing(['items.fuelProduct', 'items.nozzle', 'customer', 'vehicle', 'payments']);

        $items = [];
        foreach ($sale->items as $saleItem) {
            $items[] = [
                'fuel_product_id' => $saleItem->fuel_product_id,
                'nozzle_id' => $saleItem->nozzle_id,
                'item_description' => $saleItem->fuelProduct?->name ?? 'Fuel',
                'item_type' => 'fuel',
                'quantity' => $saleItem->litres,
                'unit_price' => $saleItem->rate,
                'tax_rate' => '0.00',
                'tax_amount' => '0.00',
                'discount' => '0.00',
                'total_amount' => $saleItem->amount,
                'meter_start' => $saleItem->meter_start,
                'meter_end' => $saleItem->meter_end,
            ];
        }

        $paymentMethod = 'cash';
        $paidAmount = '0.00';
        $paymentDetails = [];

        foreach ($sale->payments as $payment) {
            $paidAmount = Money::add($paidAmount, (string) $payment->amount);
            $paymentMethod = $payment->method;
            $paymentDetails[] = [
                'method' => $payment->method,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
            ];
        }

        if (count($paymentDetails) > 1) {
            $paymentMethod = 'split';
        }

        return $this->createInvoice([
            'branch_id' => $sale->branch_id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            // The sales table carries vehicle_id / employee_id; the columns
            // customer_vehicle_id / user_id do not exist on Sale and silently
            // produced NULLs on every generated invoice.
            'vehicle_id' => $sale->vehicle_id,
            'shift_id' => $sale->shift_id,
            'user_id' => $sale->employee_id,
            'invoice_date' => $sale->sale_date ?? now(),
            'subtotal' => $sale->subtotal,
            'discount_amount' => $sale->discount,
            'tax_amount' => $sale->tax,
            'pos_fee' => $this->settings->money('pos_service_fee'),
            'paid_amount' => $paidAmount,
            'payment_method' => $paymentMethod,
            'payment_details' => $paymentDetails,
        ], $items, $template);
    }

    /**
     * Create immutable snapshot record for an invoice.
     */
    private function createSnapshot(Invoice $invoice, InvoiceTemplate $template, array $items, array $data): InvoiceSnapshot
    {
        $stationIdentity = $this->settings->stationIdentity();
        $customer = $invoice->customer;
        $vehicle = $invoice->vehicle;

        $customerSnapshot = [
            'id' => $customer?->id,
            'name' => $customer?->name ?? ($data['customer_name'] ?? 'Walk-in Customer / کیش کسٹمر'),
            'phone' => $customer?->phone ?? ($data['customer_phone'] ?? null),
            'ntn' => $customer?->ntn_number ?? null,
            'cnic' => $customer?->cnic ?? null,
            'type' => $customer?->customer_type ?? 'CASH',
            'vehicle_number' => $vehicle?->registration_number ?? ($data['vehicle_number'] ?? null),
            'vehicle_model' => $vehicle?->make_model ?? null,
            'previous_balance' => $invoice->previous_balance,
            'closing_balance' => $invoice->closing_balance,
        ];

        $paymentSnapshot = [
            'method' => $invoice->payment_method,
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount_amount,
            'pos_fee' => $invoice->pos_fee,
            'tax' => $invoice->tax_amount,
            'total' => $invoice->total_amount,
            'paid' => $invoice->paid_amount,
            'balance_due' => $invoice->balance_due,
            'previous_balance' => $invoice->previous_balance,
            'closing_balance' => $invoice->closing_balance,
            'amount_in_words_ur' => $invoice->amount_in_words_ur,
            'amount_in_words_en' => $invoice->amount_in_words_en,
        ];

        $themeSnapshot = [
            'template_name' => $template->name,
            'template_slug' => $template->slug,
            'paper_size' => $template->paper_size,
            'primary_color' => $template->primary_color,
            'dark_red_color' => $template->dark_red_color,
            'accent_color' => $template->accent_color,
            'background_color' => $template->background_color,
            'light_grey_color' => $template->light_grey_color,
            'text_color' => $template->text_color,
            'header_bg' => $template->header_bg,
            'header_text' => $template->header_text,
            'footer_bg' => $template->footer_bg,
            'footer_text' => $template->footer_text,
            'show_logo' => $template->show_logo,
            'show_urdu_name' => $template->show_urdu_name,
            'show_vehicle' => $template->show_vehicle,
            'show_customer_box' => $template->show_customer_box,
            'show_amount_in_words' => $template->show_amount_in_words,
            'show_signatures' => $template->show_signatures,
            'show_qr_code' => $template->show_qr_code,
            'show_udhaar_balance' => $template->show_udhaar_balance,
            'show_fbr_details' => $template->show_fbr_details,
        ];

        $rawSnapshot = [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->format('Y-m-d H:i:s'),
            'hash' => $invoice->hash,
            'station' => $stationIdentity,
            'customer' => $customerSnapshot,
            'items' => $items,
            'payment' => $paymentSnapshot,
            'theme' => $themeSnapshot,
        ];

        $snapshotHash = InvoiceSnapshot::computeHash($rawSnapshot);

        return InvoiceSnapshot::create([
            'invoice_id' => $invoice->id,
            'station_snapshot' => $stationIdentity,
            'customer_snapshot' => $customerSnapshot,
            'items_snapshot' => $items,
            'payment_snapshot' => $paymentSnapshot,
            'theme_snapshot' => $themeSnapshot,
            'raw_snapshot' => $rawSnapshot,
            'snapshot_hash' => $snapshotHash,
        ]);
    }

    /**
     * Generate A4 Modern Red Band DomPDF document.
     */
    public function generatePdf(Invoice $invoice, ?string $paperSize = 'A4'): DomPdfWrapper
    {
        $invoice->loadMissing(['items', 'snapshot', 'template']);
        $snapshot = $invoice->snapshot;

        $station = $snapshot?->station_snapshot ?? $this->settings->stationIdentity();
        $customer = $snapshot?->customer_snapshot ?? [];
        $theme = $snapshot?->theme_snapshot ?? ($invoice->template?->toArray() ?? []);
        $qrSvg = $this->generateQrCodeSvg($invoice->verification_url, 95);

        $pdf = Pdf::loadView('invoices.templates.modern_red_band', [
            'invoice' => $invoice,
            'station' => $station,
            'customer' => $customer,
            'theme' => $theme,
            'qrSvg' => $qrSvg,
            'isPdf' => true,
        ]);

        $pdf->setPaper($paperSize === 'A4' ? 'a4' : $paperSize, 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Generate inline SVG QR code without XML header for clean Blade/DomPDF embedding.
     */
    public function generateQrCodeSvg(string $urlOrPayload, int $size = 110): string
    {
        try {
            $svg = (string) @QrCode::size($size)->margin(1)->generate($urlOrPayload);
            return preg_replace('/<\?xml.*?\?>/', '', $svg);
        } catch (\Throwable) {
            return '';
        }
    }
}
