<?php

namespace App\Support;

class PakistaniIbanValidator
{
    /**
     * Validate a Pakistani IBAN according to SBP / ISO 13616 standards:
     * - Total 24 characters.
     * - Starts with PK + 2 check digits + 4 uppercase letters (Bank ID) + 16 alphanumeric characters.
     * - MOD-97 checksum must equal 1.
     */
    public static function isValid(?string $iban): bool
    {
        if (empty($iban)) {
            return false;
        }

        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $iban));

        if (strlen($clean) !== 24) {
            return false;
        }

        if (! preg_match('/^PK\d{2}[A-Z]{4}[A-Z0-9]{16}$/', $clean)) {
            return false;
        }

        // Rearrange: move the first 4 chars (PK + 2 digits) to the end
        $rearranged = substr($clean, 4) . substr($clean, 0, 4);

        // Convert letters to numbers (A=10, B=11, ..., Z=35)
        $numericString = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numericString .= (string) (ord($char) - ord('A') + 10);
            } else {
                $numericString .= $char;
            }
        }

        // Mod-97 check
        return bcmod($numericString, '97') === '1';
    }

    /**
     * Generate a valid Pakistani IBAN for testing / formatting.
     */
    public static function generate(string $bankCode = 'HABB', string $accountNumber = '0012345678901234'): string
    {
        $bankCode = strtoupper(str_pad(substr(preg_replace('/[^A-Za-z]/', '', $bankCode), 0, 4), 4, 'X'));
        $accountNumber = strtoupper(str_pad(substr(preg_replace('/[^A-Za-z0-9]/', '', $accountNumber), 0, 16), 16, '0', STR_PAD_LEFT));

        $rearranged = $bankCode . $accountNumber . 'PK00';
        $numericString = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numericString .= (string) (ord($char) - ord('A') + 10);
            } else {
                $numericString .= $char;
            }
        }

        $remainder = (int) bcmod($numericString, '97');
        $checkDigits = 98 - $remainder;
        $checkString = str_pad((string) $checkDigits, 2, '0', STR_PAD_LEFT);

        return 'PK' . $checkString . $bankCode . $accountNumber;
    }
}
