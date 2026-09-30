<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Urdu localization, South Asian Lakh numbering format, and Urdu currency words.
 * Specifically crafted for Pakistani forecourt / petrol pump operations.
 */
class UrduNumber
{
    private const URDU_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ONES = [
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
        46 => 'چھیاسٹھ',
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
        78 => 'اٹہتر',
        79 => 'اناسی',
        80 => 'اسی',
        81 => 'اکیاسی',
        82 => 'بیاسی',
        83 => 'تراسی',
        84 => 'چوراسی',
        85 => 'پچاسی',
        86 => 'چھیاسی',
        87 => 'ستاسی',
        88 => 'اٹاسی',
        89 => 'نواسی',
        90 => 'نوے',
        91 => 'اکیانوے',
        92 => 'بانوے',
        93 => 'ترانوے',
        94 => 'چورانوے',
        95 => 'پچانوے',
        96 => 'چھیانوے',
        97 => 'ستانوے',
        98 => 'اٹانوے',
        99 => 'ننانوے',
    ];

    private const URDU_DAYS = [
        0 => 'اتوار',
        1 => 'پیر',
        2 => 'منگل',
        3 => 'بدھ',
        4 => 'جمعرات',
        5 => 'جمعہ',
        6 => 'ہفتہ',
    ];

    private const URDU_MONTHS = [
        1 => 'جنوری',
        2 => 'فروری',
        3 => 'مارچ',
        4 => 'اپریل',
        5 => 'مئی',
        6 => 'جون',
        7 => 'جولائی',
        8 => 'اگست',
        9 => 'ستمبر',
        10 => 'اکتوبر',
        11 => 'نومبر',
        12 => 'دسمبر',
    ];

    /**
     * Format number in Pakistani Lakh & Crore format (e.g. 4,50,000 or Rs. 4,50,000).
     */
    public static function lakhFormat(string|int|float|null $amount, int $decimals = 0, bool $withPrefix = true): string
    {
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        $num = (float) $amount;
        $isNegative = $num < 0;
        $absAmount = abs($num);

        // Format to standard decimals
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
     * Convert amount to Urdu words with "روپے صرف" (e.g. "چار لاکھ پچاس ہزار روپے صرف").
     */
    public static function amountInWords(string|int|float|null $amount): string
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
     * Convert integer to Urdu words up to Arab / Kharab.
     */
    public static function numberToUrdu(int $n): string
    {
        if ($n < 0) {
            return 'منفی ' . self::numberToUrdu(abs($n));
        }

        if ($n <= 99) {
            return self::ONES[$n] ?? (string) $n;
        }

        if ($n < 1000) {
            $hundreds = (int) floor($n / 100);
            $rem = $n % 100;
            $text = self::ONES[$hundreds] . ' سو';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Thousands (1,000 to 99,999)
        if ($n < 100000) {
            $thousands = (int) floor($n / 1000);
            $rem = $n % 1000;
            $text = self::numberToUrdu($thousands) . ' ہزار';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Lakhs (1,00,000 to 99,99,999)
        if ($n < 10000000) {
            $lakhs = (int) floor($n / 100000);
            $rem = $n % 100000;
            $text = self::numberToUrdu($lakhs) . ' لاکھ';
            if ($rem > 0) {
                $text .= ' ' . self::numberToUrdu($rem);
            }
            return $text;
        }

        // Crores (1,00,00,000 to 99,99,99,999)
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
     * Convert western digits (0-9) to Urdu numeral symbols (۰-۹).
     */
    public static function toUrduDigits(string|int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;
        $result = '';
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $ch = $str[$i];
            if ($ch >= '0' && $ch <= '9') {
                $result .= self::URDU_DIGITS[(int) $ch];
            } else {
                $result .= $ch;
            }
        }

        return $result;
    }

    /**
     * Format a date into authentic Urdu date string.
     * e.g., "بدھ، 30 ستمبر 2026"
     */
    public static function urduDate(CarbonInterface|string|null $date = null, bool $withDay = true): string
    {
        $dt = $date instanceof CarbonInterface ? $date : ($date ? Carbon::parse($date) : now());

        $dayName = self::URDU_DAYS[$dt->dayOfWeek] ?? '';
        $monthName = self::URDU_MONTHS[$dt->month] ?? '';
        $dayNum = $dt->day;
        $year = $dt->year;

        if ($withDay) {
            return "{$dayName}، {$dayNum} {$monthName} {$year}";
        }

        return "{$dayNum} {$monthName} {$year}";
    }

    /**
     * Format time in Urdu (e.g. "05:30 شام" or "10:15 صبح").
     */
    public static function urduTime(CarbonInterface|string|null $date = null): string
    {
        $dt = $date instanceof CarbonInterface ? $date : ($date ? Carbon::parse($date) : now());

        $time = $dt->format('h:i');
        $ampm = $dt->format('A') === 'AM' ? 'صبح' : 'شام';

        return "{$time} {$ampm}";
    }
}
