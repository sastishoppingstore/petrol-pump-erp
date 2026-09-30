<?php

namespace App\Mail;

use App\Models\DailyClosing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyClosingSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DailyClosing $closing,
        public readonly array $breakdown = [],
    ) {
    }

    public function envelope(): Envelope
    {
        $date = $this->closing->closing_date->format('d M Y');
        return new Envelope(
            subject: "Daily Forecourt Closing Report - {$date} [Mehar Filling Station]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_closing_summary',
        );
    }
}
