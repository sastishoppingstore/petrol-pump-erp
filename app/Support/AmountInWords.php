<?php

namespace App\Support;

/**
 * Currency amount in words helper (Urdu and English) & South Asian Lakh formatting.
 * Specifically crafted for Mehar Filling Station (Vital Petroleum franchise).
 *
 * Examples:
 *   AmountInWords::toUrdu(59400)   => "انسٹھ ہزار چار سو روپے صرف"
 *   AmountInWords::toUrdu(450000)  => "چار لاکھ پچاس ہزار روپے صرف"
 *   AmountInWords::toEnglish(59400)  => "Fifty-Nine Thousand Four Hundred Rupees Only"
 *   AmountInWords::toEnglish(450000) => "Four Lakh Fifty Thousand Rupees Only"
 *   AmountInWords::formatLakh(450000) => "Rs. 4,50,000"
 */
class AmountInWords
{
    private const URDU_ONES = [
        0 => 'صفر',
        1 => 'ایک',
        2 => 'دو',
        3 => 'تین',
        4 => 'چار',
        5 => 'پانچ',
        6 => 'چھ',
        7 => 'سات',
        8 => 'آٹھ',
        9 => 'نو',
        10 => 'دس',
        11 => 'گیارہ',
        12 => 'بارہ',
        13 => 'تیرہ',
        14 => 'چودہ',
        15 => 'پندرہ',
        16 => 'سولہ',
        17 => 'سترہ',
        18 => 'اٹھارہ',
        19 => 'انیس',
        20 => 'بیس',
        21 => 'اکیس',
        22 => 'بائیس',
        23 => 'تیئیس',
        24 => 'چوبیس',
        25 => 'پچیس',
        26 => 'چھبیس',
        27 => 'ستائیس',
        28 => 'اٹھائیس',
        29 => 'انتیس',
        30 => 'تیس',
        31 => 'اکتیس',
        32 => 'بتیس',
        33 => 'تینتیس',
        34 => 'چونتیس',
        35 => 'پینتیس',
        36 => 'چھتیس',
        37 => 'سینتیس',
        38 => 'اڑتیس',
        39 => 'انتالیس',
        40 => 'چالیس',
        41 => 'اکتالیس',
        42 => 'بیالیس',
        43 => 'تینتالیس',
        44 => 'چوالیس',
        45 => 'پینتالیس',
        46 => 'چھیالیس',
        47 => 'سینتالیس',
        48 => 'اڑتالیس',
        49 => 'انچاس',
        50 => 'پچاس',
        51 => 'اکیاون',
        52 => 'باون',
        53 => 'ترپن',
        54 => 'چون',
        55 => 'پچپن',
        56 => 'چھپن',
        57 => 'ستاون',
        58 => 'اٹھاون',
        59 => 'انسٹھ',
        60 => 'ساٹھ',
        61 => 'اکسٹھ',
        62 => 'باسٹھ',
        63 => 'تریسٹھ',
        64 => 'چونسٹھ',
        65 => 'پینسٹھ',
        66 => 'چھیاسٹھ',
        67 => 'سڑسٹھ',
        68 => 'اڑسٹھ',
        69 => 'انہتر',
        70 => 'ستر',
        71 => 'اکہتر',
        72 => 'بہتر',
        73 => 'تہتر',
        74 => 'چوہتر',
        75 => 'پچہتر',
        76 => 'چھہتر',
        77 => 'ستتر',
        78 => 'اٹھتر',
        79 => 'اناسی',
        80 => 'اسی',
        81 => 'اکیاسی',
        82 => 'بیاسی',
        83 => 'تراسی',
        84 => 'چوراسی',
        85 => 'پچاسی',
        86 => 'چھیاسی',
        87 => 'ستاسی',
        88 => 'اٹھاسی',
        89 => 'نواسی',
        90 => 'نوے',
        91 => 'اکیانوے',
        92 => 'بانوے',
        93 => 'ترانوے',
        94 => 'چورانوے',
        95 => 'پچانوے',
        96 => 'چھیانوے',
        97 => 'ستانوے',
        98 => 'اٹھانوے',
        99 => 'ننانوے',
    ];

    private const EN_ONES = [
        0 => 'Zero',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
    ];

    private const EN_TENS = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety',
    ];

    /**
     * Convert currency amount to Urdu words with "روپے صرف".
     *
     * Example: 59400 -> "انسٹھ ہزار چار سو روپے صرف"
     * Example: 450000 -> "چار لاکھ پچاس ہزار روپے صرف"
     */
    public static function toUrdu(string|float|int|null $amount): string
    {
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        $num = (float) $amount;
        $isNegative = $num < 0;
        $absAmount = abs($num);

        $intVal = (int) floor($absAmount);
        $paisaVal = (int) round(($absAmount - $intVal) * 100);

        if ($intVal === 0 && $paisaVal === 0) {
            return 'صفر روپے صرف';
        }

        $words = [];
        if ($intVal > 0) {
            $words[] = self::numberToUrdu($intVal) . ' روپے';
        }

        if ($paisaVal > 0) {
            $words[] = self::numberToUrdu($paisaVal) . ' پیسے';
        }

        $text = implode(' اور ', $words) . ' صرف';

        return $isNegative ? 'منفی ' . $text : $text;
    }

    /**
     * Recursive Urdu number words (Units up to Arab).
     */
    public static function numberToUrdu(int $n): string
    {
        if ($n < 0) {
            return 'منفی ' . self::numberToUrdu(abs($n));
        }

        if ($n <= 99) {
            return self::URDU_ONES[$n] ?? (string) $n;
        }

        // Hundreds (100 - 999)
        if ($n < 1000) {
            $hundreds = (int) floor($n / 100);
            $rem = $n % 100;
            $text = self::URDU_ONES[$hundreds] . ' سو';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Thousands (1,000 - 99,999)
        if ($n < 100000) {
            $thousands = (int) floor($n / 1000);
            $rem = $n % 1000;
            $text = self::numberToUrdu($thousands) . ' ہزار';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Lakhs (1,00,000 - 99,99,999)
        if ($n < 10000000) {
            $lakhs = (int) floor($n / 100000);
            $rem = $n % 100000;
            $text = self::numberToUrdu($lakhs) . ' لاکھ';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Crores (1,00,00,000 - 99,99,99,999)
        if ($n < 1000000000) {
            $crores = (int) floor($n / 10000000);
            $rem = $n % 10000000;
            $text = self::numberToUrdu($crores) . ' کروڑ';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Arabs (1,00,00,00,000+)
        $arabs = (int) floor($n / 1000000000);
        $rem = $n % 1000000000;
        $text = self::numberToUrdu($arabs) . ' ارب';
        if ($rem > 0) {
            $text .= ' ' . self::numberToUrdu($rem);
        }
        return $text;
    }

    /**
     * Convert currency amount to English words with "Rupees Only".
     * Supports South Asian Lakh format (default) or International million format.
     *
     * Example: 59400 => "Fifty-Nine Thousand Four Hundred Rupees Only"
     * Example: 450000 => "Four Lakh Fifty Thousand Rupees Only"
     */
    public static function toEnglish(string|float|int|null $amount, bool $southAsian = true): string
    {
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        $num = (float) $amount;
        $isNegative = $num < 0;
        $absAmount = abs($num);

        $intVal = (int) floor($absAmount);
        $paisaVal = (int) round(($absAmount - $intVal) * 100);

        if ($intVal === 0 && $paisaVal === 0) {
            return 'Zero Rupees Only';
        }

        $parts = [];
        if ($intVal > 0) {
            $parts[] = ($southAsian ? self::intToEnglishSouthAsian($intVal) : self::intToEnglishInternational($intVal)) . ' Rupees';
        }

        if ($paisaVal > 0) {
            $parts[] = self::intToEnglish99($paisaVal) . ' Paisas';
        }

        $result = implode(' and ', $parts) . ' Only';

        return $isNegative ? 'Minus ' . $result : $result;
    }

    private static function intToEnglish99(int $n): string
    {
        if ($n < 20) {
            return self::EN_ONES[$n];
        }

        $tens = (int) floor($n / 10);
        $rem = $n % 10;

        return self::EN_TENS[$tens] . ($rem > 0 ? '-' . self::EN_ONES[$rem] : '');
    }

    private static function intToEnglishUnder1000(int $n): string
    {
        if ($n < 100) {
            return self::intToEnglish99($n);
        }

        $hundreds = (int) floor($n / 100);
        $rem = $n % 100;
        $str = self::EN_ONES[$hundreds] . ' Hundred';

        if ($rem > 0) {
            $str .= ' ' . self::intToEnglish99($rem);
        }

        return $str;
    }

    /**
     * South Asian English (Hundreds, Thousands, Lakhs, Crores).
     */
    private static function intToEnglishSouthAsian(int $n): string
    {
        if ($n < 1000) {
            return self::intToEnglishUnder1000($n);
        }

        // Thousands (1,000 - 99,999)
        if ($n < 100000) {
            $thousands = (int) floor($n / 1000);
            $rem = $n % 1000;
            $str = self::intToEnglishUnder1000($thousands) . ' Thousand';
            if ($rem > 0) {
                $str .= ' ' . self::intToEnglishUnder1000($rem);
            }
            return $str;
        }

        // Lakhs (1,00,000 - 99,99,999)
        if ($n < 10000000) {
            $lakhs = (int) floor($n / 100000);
            $rem = $n % 100000;
            $str = self::intToEnglishUnder1000($lakhs) . ' Lakh';
            if ($rem > 0) {
                $str .= ' ' . self::intToEnglishSouthAsian($rem);
            }
            return $str;
        }

        // Crores (1,00,00,000+)
        $crores = (int) floor($n / 10000000);
        $rem = $n % 10000000;
        $str = self::intToEnglishSouthAsian($crores) . ' Crore';
        if ($rem > 0) {
            $str .= ' ' . self::intToEnglishSouthAsian($rem);
        }
        return $str;
    }

    /**
     * International English (Thousands, Millions, Billions).
     */
    private static function intToEnglishInternational(int $n): string
    {
        if ($n < 1000) {
            return self::intToEnglishUnder1000($n);
        }

        if ($n < 1000000) {
            $thousands = (int) floor($n / 1000);
            $rem = $n % 1000;
            $str = self::intToEnglishUnder1000($thousands) . ' Thousand';
            if ($rem > 0) {
                $str .= ' ' . self::intToEnglishUnder1000($rem);
            }
            return $str;
        }

        if ($n < 1000000000) {
            $millions = (int) floor($n / 1000000);
            $rem = $n % 1000000;
            $str = self::intToEnglishUnder1000($millions) . ' Million';
            if ($rem > 0) {
                $str .= ' ' . self::intToEnglishInternational($rem);
            }
            return $str;
        }

        $billions = (int) floor($n / 1000000000);
        $rem = $n % 1000000000;
        $str = self::intToEnglishUnder1000($billions) . ' Billion';
        if ($rem > 0) {
            $str .= ' ' . self::intToEnglishInternational($rem);
        }
        return $str;
    }

    /**
     * Pakistani Lakh formatting: e.g. "Rs. 4,50,000" or "Rs. 1,23,45,678".
     */
    public static function formatLakh(string|int|float|null $amount, bool $withPrefix = true, int $decimals = 0): string
    {
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        $num = (float) $amount;
        $isNegative = $num < 0;
        $absAmount = abs($num);

        $formattedDecimals = number_format($absAmount, $decimals, '.', '');
        $parts = explode('.', $formattedDecimals);
        $intPart = $parts[0];
        $decPart = $parts[1] ?? '';

        if (strlen($intPart) <= 3) {
            $grouped = $intPart;
        } else {
            $last3 = substr($intPart, -3);
            $leading = substr($intPart, 0, -3);

            // Group leading digits into pairs of 2 from right
            $reversed = strrev($leading);
            $pairs = str_split($reversed, 2);
            $groupedLeading = strrev(implode(',', $pairs));

            $grouped = $groupedLeading . ',' . $last3;
        }

        $result = $grouped;
        if ($decimals > 0 && $decPart !== '') {
            $result .= '.' . $decPart;
        }

        if ($isNegative) {
            $result = '-' . $result;
        }

        return $withPrefix ? 'Rs. ' . $result : $result;
    }

    /**
     * Universal converter helper.
     */
    public static function convert(string|float|int|null $amount, string $lang = 'ur'): string
    {
        return strtolower($lang) === 'en'
            ? self::toEnglish($amount)
            : self::toUrdu($amount);
    }
}
