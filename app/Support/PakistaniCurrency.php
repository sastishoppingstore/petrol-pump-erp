<?php

namespace App\Support;

class PakistaniCurrency
{
    /**
     * Format a number in Pakistani Lakh format:
     * e.g., 450000 -> "Rs. 4,50,000.00"
     * 12345678.50 -> "Rs. 1,23,45,678.50"
     */
    public static function format(string|float|int|null $amount, bool $showSymbol = true): string
    {
        if ($amount === null || $amount === '') {
            $amount = '0.00';
        }

        $rounded = Money::round((string) $amount);
        $isNegative = str_starts_with($rounded, '-');
        $clean = ltrim($rounded, '-');

        $parts = explode('.', $clean);
        $integerPart = $parts[0];
        $decimalPart = $parts[1] ?? '00';

        // Format integer part using Pakistani numbering: 3 digits, then groups of 2
        if (strlen($integerPart) > 3) {
            $lastThree = substr($integerPart, -3);
            $remaining = substr($integerPart, 0, -3);
            $grouped = '';

            while (strlen($remaining) > 2) {
                $grouped = ',' . substr($remaining, -2) . $grouped;
                $remaining = substr($remaining, 0, -2);
            }

            $formattedInteger = $remaining . $grouped . ',' . $lastThree;
        } else {
            $formattedInteger = $integerPart;
        }

        $formatted = $formattedInteger . '.' . $decimalPart;

        if ($isNegative) {
            $formatted = '-' . $formatted;
        }

        return $showSymbol ? 'Rs. ' . $formatted : $formatted;
    }

    /**
     * Convert an amount into Urdu words with "روپے صرف" suffix:
     * e.g., 450000 -> "چار لاکھ پچاس ہزار روپے صرف"
     */
    public static function inWordsUrdu(string|float|int|null $amount): string
    {
        return AmountInWords::toUrdu($amount);
    }

    public static function toWordsUrdu(string|float|int|null $amount): string
    {
        return self::inWordsUrdu($amount);
    }

    public static function toUrduWords(string|float|int|null $amount): string
    {
        return self::inWordsUrdu($amount);
    }

    private static function convertUrduNumber(int $num): string
    {
        if ($num === 0) {
            return 'صفر';
        }

        $units = [
            0 => '', 1 => 'ایک', 2 => 'دو', 3 => 'تین', 4 => 'چار', 5 => 'پانچ',
            6 => 'چھ', 7 => 'سات', 8 => 'آٹھ', 9 => 'نو', 10 => 'دس',
            11 => 'گیارہ', 12 => 'بارہ', 13 => 'تیرہ', 14 => 'چودہ', 15 => 'پندرہ',
            16 => 'سولہ', 17 => 'سترہ', 18 => 'اٹھارہ', 19 => 'انیس', 20 => 'بیس',
            21 => 'اکیس', 22 => 'بائیس', 23 => 'تیئیس', 24 => 'چوبیس', 25 => 'پچیس',
            26 => 'چھبیس', 27 => 'ستائیس', 28 => 'اٹائیس', 29 => 'انتیس', 30 => 'تیس',
            31 => 'اکتیس', 32 => 'بتیس', 33 => 'تینتیس', 34 => 'چونتیس', 35 => 'پینتیس',
            36 => 'چھتیس', 37 => 'سینتیس', 38 => 'اڑتیس', 39 => 'انتالیس', 40 => 'چالیس',
            41 => 'اکتالیس', 42 => 'بیالیس', 43 => 'تینتالیس', 44 => 'چوالیس', 45 => 'پینتالیس',
            46 => 'چھیاسٹھ', 47 => 'سینتالیس', 48 => 'اڑتالیس', 49 => 'انچاس', 50 => 'پچاس',
            51 => 'اکیاون', 52 => 'باون', 53 => 'ترپن', 54 => 'چون', 55 => 'پچپن',
            56 => 'چھپن', 57 => 'ستاون', 58 => 'اٹھاون', 59 => 'انسٹھ', 60 => 'ساٹھ',
            61 => 'اکسٹھ', 62 => 'باسٹھ', 63 => 'تریسٹھ', 64 => 'چونسٹھ', 65 => 'پینسٹھ',
            66 => 'چھیاسٹھ', 67 => 'سڑسٹھ', 68 => 'اڑسٹھ', 69 => 'انہتر', 70 => 'ستر',
            71 => 'اکہتر', 72 => 'بہتر', 73 => 'تہتر', 74 => 'چوہتر', 75 => 'پچھتر',
            76 => 'چھہتر', 77 => 'ستتر', 78 => 'اٹھتر', 79 => 'اناسی', 80 => 'اسی',
            81 => 'اکیاسی', 82 => 'بیاسی', 83 => 'تراسی', 84 => 'چوراسی', 85 => 'پچاسی',
            86 => 'چھیاسی', 87 => 'ستاسی', 88 => 'اٹاسی', 89 => 'نواسی', 90 => 'نوے',
            91 => 'اکیانوے', 92 => 'بانوے', 93 => 'ترانوے', 94 => 'چورانوے', 95 => 'پچانوے',
            96 => 'چھیانوے', 97 => 'ستانوے', 98 => 'اٹھانوے', 99 => 'نناوے'
        ];

        $parts = [];

        // Crores (1,00,00,000)
        if ($num >= 10000000) {
            $crore = (int) floor($num / 10000000);
            $parts[] = self::convertUrduNumber($crore) . ' کروڑ';
            $num %= 10000000;
        }

        // Lakhs (1,00,000)
        if ($num >= 100000) {
            $lakh = (int) floor($num / 100000);
            $parts[] = self::convertUrduNumber($lakh) . ' لاکھ';
            $num %= 100000;
        }

        // Thousands (1,000)
        if ($num >= 1000) {
            $thousand = (int) floor($num / 1000);
            $parts[] = self::convertUrduNumber($thousand) . ' ہزار';
            $num %= 1000;
        }

        // Hundreds (100)
        if ($num >= 100) {
            $hundred = (int) floor($num / 100);
            $parts[] = $units[$hundred] . ' سو';
            $num %= 100;
        }

        if ($num > 0) {
            $parts[] = $units[$num];
        }

        return implode(' ', $parts);
    }
}
