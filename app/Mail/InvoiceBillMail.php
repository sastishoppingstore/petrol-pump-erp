<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\System\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Customer ko bheja jane wala bill — A4 PDF attached.
 *
 * PDF content BillEmailService pehle se generate karke deta hai (wohi
 * DomPDF jo print/PDF download me istemal hota hai) taake mailable khud
 * koi generation na kare aur mail-send ke waqt fail na ho.
 */
class InvoiceBillMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $pdfContent,
        public readonly string $stationName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->stationName} — Bill {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-bill',
            with: [
                'station' => app(SettingService::class)->stationIdentity(),
                'fbrInvoice' => $this->invoice->fbrInvoice,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                'bill-' . $this->invoice->invoice_number . '.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
