<?php

namespace App\Support;

/**
 * Money helpers — DECIMAL(14,2) with 2 decimal places.
 */
class Money
{
    public const SCALE = 2;

    /** amount = ROUND(litres x rate, 2) */
    public static function amountForLitres(string $litres, string $rate): string
    {
        return Decimal::multiply($litres, $rate, self::SCALE);
    }

    /** litres = ROUND(amount / rate, 3) */
    public static function litresForAmount(string $amount, string $rate): string
    {
        if (Decimal::isZero($rate)) {
            throw new \InvalidArgumentException('Cannot compute litres for a zero rate.');
        }

        return Decimal::divide($amount, $rate, Quantity::SCALE);
    }

    public static function add(string $a, string $b): string
    {
        return Decimal::add($a, $b, self::SCALE);
    }

    public static function subtract(string $a, string $b): string
    {
        return Decimal::subtract($a, $b, self::SCALE);
    }

    public static function round(string $value): string
    {
        return Decimal::round($value, self::SCALE);
    }

    public static function isZero(string $value): bool
    {
        return Decimal::isZero($value);
    }

    public static function isNegative(string $value): bool
    {
        return Decimal::isNegative($value);
    }

    public static function compare(string $a, string $b): int
    {
        return Decimal::compare($a, $b, self::SCALE);
    }

    public static function isPositive(string $value): bool
    {
        return Decimal::isPositive($value);
    }

    /**
     * Absolute value, as a plain decimal string. Needed for tolerance checks:
     * a 50 shortfall and a 50 surplus are both "50 out".
     */
    public static function abs(string $value): string
    {
        $value = self::round($value);

        return self::isNegative($value)
            ? self::subtract('0.00', $value)
            : $value;
    }

    /**
     * Is the value outside +/- $tolerance?
     */
    public static function exceedsTolerance(string $value, string $tolerance): bool
    {
        if (self::isZero($tolerance)) {
            return false;
        }

        return self::compare(self::abs($value), self::round($tolerance)) > 0;
    }

    /**
     * Format for display, e.g. "1,234.50".
     */
    public static function format(?string $value): string
    {
        return number_format((float) self::n($value), self::SCALE, '.', ',');
    }

    public static function n(string|int|float|null $value): string
    {
        return Decimal::n($value);
    }
}
