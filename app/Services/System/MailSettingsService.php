<?php

namespace App\Services\System;

/**
 * MailSettingsService — DB (settings table, group "email") me save shuda
 * SMTP configuration ko Laravel mail config par runtime me apply karta hai.
 *
 * Settings keys (App\Services\System\SettingService SCHEMA, group "email"):
 *   mail_driver, mail_host, mail_port, mail_username, mail_password,
 *   mail_encryption, mail_from_address, mail_from_name
 *
 * Agar DB me host set nahi hai to .env wali config hi rehti hai — apply()
 * kuch change nahi karta. Doosri teams (bill email waghera) bhi email
 * bhejne se pehle isi apply() ko call karein taake admin ki SMTP setting
 * har jagah laagu ho.
 */
class MailSettingsService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    /**
     * Apply DB mail settings to the runtime mail configuration.
     *
     * Safe to call repeatedly; mail manager instances are forgotten so the
     * next Mail::send() picks up the fresh configuration.
     */
    public function apply(): void
    {
        $host = $this->settings->get('mail_host');

        if (! $host) {
            // DB me SMTP configure nahi — .env config par rehne do.
            return;
        }

        $driver = $this->settings->get('mail_driver') ?: 'smtp';
        $port = $this->settings->get('mail_port');
        $encryption = $this->settings->get('mail_encryption');
        $fromAddress = $this->settings->get('mail_from_address');
        $fromName = $this->settings->get('mail_from_name');

        config([
            'mail.default' => $driver,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port !== null && $port !== '' ? (int) $port : 587,
            'mail.mailers.smtp.username' => $this->settings->get('mail_username'),
            'mail.mailers.smtp.password' => $this->settings->get('mail_password'),
            'mail.mailers.smtp.encryption' => $encryption !== null && $encryption !== '' ? $encryption : null,
        ]);

        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }

        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }

        // Pehle se resolved mailer purani config cache kar sakta hai —
        // instances bhool jao taake agli send fresh config use kare.
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }

    /**
     * Is there a usable SMTP configuration (DB setting or .env fallback)?
     * Callers use this to skip sending gracefully instead of crashing.
     */
    public function isConfigured(): bool
    {
        if ($this->settings->get('mail_host')) {
            return true;
        }

        $mailer = (string) config('mail.default', 'log');

        return ! in_array($mailer, ['log', 'array'], true);
    }
}
