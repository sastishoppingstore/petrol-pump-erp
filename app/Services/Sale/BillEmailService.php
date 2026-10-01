<?php

namespace App\Services\Sale;

use App\Mail\InvoiceBillMail;
use App\Models\Sale;
use App\Services\System\SettingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Customer ko bill ki auto-email (A4 PDF attached).
 *
 * POS completion flow isay har mukammal sale ke baad bulata hai:
 * `app(BillEmailService::class)->sendForSale($sale)`.
 *
 * Ye method KABHI throw nahi karta — bill email ek side-effect hai, sale ko
 * kabhi nahi rokna chahiye. Har nakami Log me jati hai aur `false` return
 * hota hai taake caller (POS / resend button) sirf report kare, fail na ho.
 *
 * Guards (koi bhi fail ho to email nahi jati, false return hota hai):
 *  1. Setting `email_bill_to_customer` off ho (admin ne band ki ho).
 *  2. Sale voided ho — mansookh bill customer ko nahi bhejna.
 *  3. Customer record na ho, ya us ka email khali/ghalat ho (walk-in cash
 *     sales par aksar customer hi nahi hota — ye normal hai, error nahi).
 *
 * PDF hamesha wahi A4 DomPDF hai jo InvoiceService::generatePdf() banata
 * hai — email aur print kabhi alag documents nahi ho sakte.
 */
class BillEmailService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly SettingService $settings,
    ) {}

    /**
     * Sale ka bill customer ko email karo. True sirf tab jab mail asal me
     * bhej di gayi ho.
     */
    public function sendForSale(Sale $sale): bool
    {
        try {
            return $this->doSend($sale);
        } catch (\Throwable $e) {
            Log::error('BillEmail: customer bill email failed.', [
                'sale_id' => $sale->id ?? null,
                'invoice_number' => $sale->invoice_number ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function doSend(Sale $sale): bool
    {
        // 1) Admin setting — default ON ('1'). Settings number-type me save
        //    hon to value "1.0000" jaisi string aati hai, is liye off-values
        //    ki wazeh list se compare karte hain.
        $flag = strtolower(trim((string) ($this->settings->get('email_bill_to_customer', '1') ?? '1')));
        if (in_array($flag, ['', '0', '0.0', '0.00', '0.0000', 'false', 'off', 'no'], true)) {
            Log::info('BillEmail: skipped — email_bill_to_customer setting is off.', [
                'sale_id' => $sale->id,
            ]);

            return false;
        }

        // 2) Voided sale ka bill customer ko nahi jata.
        if ($sale->isVoided()) {
            Log::info('BillEmail: skipped — sale is voided.', [
                'sale_id' => $sale->id,
            ]);

            return false;
        }

        // 3) Customer + valid email lazmi hai.
        $sale->loadMissing('customer');
        $customer = $sale->customer;
        $email = trim((string) ($customer?->email ?? ''));

        if (! $customer || $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            Log::info('BillEmail: skipped — customer email missing or invalid.', [
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
            ]);

            return false;
        }

        // 4) Invoice (idempotent — D-013) aur us ka A4 PDF.
        $invoice = $this->invoices->invoiceForSale($sale);
        $invoice->loadMissing(['items.fuelProduct', 'customer', 'vehicle', 'fbrInvoice']);

        $pdfContent = $this->invoices->generatePdf($invoice)->output();

        if (! is_string($pdfContent) || $pdfContent === '') {
            Log::warning('BillEmail: skipped — invoice PDF generation returned empty content.', [
                'sale_id' => $sale->id,
                'invoice_id' => $invoice->id,
            ]);

            return false;
        }

        // 5) SMTP settings runtime par apply karo (admin panel se save hoti
        //    hain). Service doosri team ki hai — mojood ho tabhi bulayo.
        if (class_exists(\App\Services\System\MailSettingsService::class)) {
            app(\App\Services\System\MailSettingsService::class)->apply();
        }

        $stationName = (string) ($this->settings->stationIdentity()['station_name_en'] ?? 'Mehar Filling Station');

        Mail::to($email)->send(new InvoiceBillMail($invoice, $pdfContent, $stationName));

        Log::info('BillEmail: bill emailed to customer.', [
            'sale_id' => $sale->id,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $customer->id,
            'email' => $email,
        ]);

        return true;
    }
}
