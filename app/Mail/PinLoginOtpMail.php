<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PIN-login OTP — shift change par cashier ki tasdeeq ke liye.
 *
 * Standing policy (D-016): security one-time codes hamesha EMAIL par
 * jate hain, SMS par kabhi nahi. Ye OTP sirf tab bheja jata hai jab
 * admin ne setting 'pin_login_otp' on ki ho (D-017) — default off.
 */
class PinLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $otp,
        public readonly int $expiryMinutes = 10,
        public readonly string $stationName = 'Mehar Filling Station',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->stationName} — PIN Login Code: {$this->otp}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pin-otp',
        );
    }
}
