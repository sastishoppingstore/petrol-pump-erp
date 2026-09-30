<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * FBR Digital Invoicing — SRO 1006(I)/2021.
 *
 * This is not optional formatting. SRO 1006(I)/2021 specifies a standard
 * invoice format for businesses integrated with FBR's POS system, and every
 * printed invoice must carry:
 *
 *   - the FBR fiscal invoice number, format XXXXXX-DDMMYYHHMMSS-0001
 *   - a verifiable QR code, 7mm x 7mm
 *   - the statement "Verify this invoice through FBR Tax Asaan Mobile App or
 *     SMS at 9966 and win exciting prizes in draw"
 *   - a Rs.1/- PoS service fee as a separate line item
 *   - the buyer's name / CNIC / NTN whenever the buyer is tax-liable or the
 *     invoice value exceeds Rs.100,000
 *
 * Everything here is deterministic and unit-testable. The actual transmission
 * to FBR must be performed by an integrator holding a valid FBR licence
 * (Chapter XIV, Sales Tax Rules 2006) — see FbrIntegrationService.
 */
class Fbr
{
    /** The exact wording SRO 1006(I)/2021 requires on the printed invoice. */
    public const VERIFICATION_STATEMENT =
        'Verify this invoice through FBR Tax Asaan Mobile App or SMS at 9966 and win exciting prizes in draw';

    /** Invoice values above this require the buyer's CNIC / NTN. */
    public const BUYER_DETAILS_THRESHOLD = '100000.00';

    /** Mandatory per-invoice service fee, in rupees. */
    public const POS_SERVICE_FEE = '1.00';

    /** QR code dimensions in millimetres, as specified. */
    public const QR_SIZE_MM = 7;

    /**
     * Build the fiscal invoice number.
     *
     * Format: XXXXXX-DDMMYYHHMMSS-0001
     *   XXXXXX         six-character POS branch code (e.g. MFS001)
     *   DDMMYY         issue date, two-digit year
     *   HHMMSS         issue time, to the second
     *   0001           per-invoice sequence within that second
     *
     * The date/time block is therefore twelve digits, not fourteen.
     *
     * @param  string  $posBranchCode  six characters, e.g. "MFS001"
     * @param  int  $sequence  per-second counter, 1-based
     */
    public static function fiscalNumber(
        string $posBranchCode,
        CarbonInterface $issuedAt,
        int $sequence = 1,
    ): string {
        if ($sequence < 1) {
            throw new \InvalidArgumentException('Fiscal invoice sequence starts at 1.');
        }

        if (! preg_match('/^[A-Za-z0-9]{6}$/', $posBranchCode)) {
            throw new \InvalidArgumentException(
                'The POS branch code must be exactly six letters or digits.'
            );
        }

        return sprintf(
            '%s-%s-%04d',
            strtoupper($posBranchCode),
            $issuedAt->format('dmyHis'),
            $sequence,
        );
    }

    /**
     * The data encoded in the invoice's QR code. FBR's app verifies against
     * the fiscal number plus the key invoice totals, so the QR has to carry
     * enough to identify the invoice without exposing customer data.
     *
     * @return array<string, string>
     */
    public static function qrPayload(
        string $fiscalNumber,
        string $totalAmount,
        string $date,
        string $ntn = '',
        string $strn = '',
    ): array {
        return [
            'fbr_invoice_no' => $fiscalNumber,
            'total_amount' => Money::round($totalAmount),
            'date' => $date,
            'ntn' => $ntn,
            'strn' => $strn,
        ];
    }

    /**
     * Does this invoice legally have to show the buyer's CNIC / NTN?
     *
     * True when the buyer is tax-liable, or when the invoice value exceeds
     * the SRO threshold.
     */
    public static function requiresBuyerDetails(
        string $totalAmount,
        bool $buyerIsTaxLiable = false,
    ): bool {
        if ($buyerIsTaxLiable) {
            return true;
        }

        return Money::compare(
            Money::round($totalAmount),
            // self:: is required — a bare constant inside a class resolves in
            // the global namespace, not to the class constant.
            self::BUYER_DETAILS_THRESHOLD,
        ) > 0;
    }

    /**
     * Is the fiscal number structurally valid?
     *
     * Catches a mistyped or truncated number before it reaches an invoice.
     */
    public static function isValidFiscalNumber(string $fiscalNumber): bool
    {
        return (bool) preg_match(
            '/^[A-Z0-9]{6}-\d{12}-\d{4}$/',
            $fiscalNumber,
        );
    }

    /**
     * Extract the timestamp embedded in a fiscal number, or null if malformed.
     */
    public static function fiscalNumberTimestamp(string $fiscalNumber): ?string
    {
        if (! self::isValidFiscalNumber($fiscalNumber)) {
            return null;
        }

        $stamp = substr($fiscalNumber, 7, 12);

        $parsed = \Carbon\Carbon::createFromFormat('dmyHis', $stamp);

        return $parsed->format('Y-m-d H:i:s');
    }

    /**
     * Per-second sequence counter for fiscal numbers.
     *
     * The unique index on (fiscal_number) is the real guard; this just makes
     * the counter behave predictably when several invoices are issued in the
     * same second.
     */
    public static function nextSequence(\DateTimeInterface $issuedAt): int
    {
        return ((int) $issuedAt->format('s')) + 1;
    }
}
