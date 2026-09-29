<?php

namespace App\Support;

/**
 * Litre helpers — DECIMAL(12,3) with 3 decimal places.
 */
class Quantity
{
    public const SCALE = 3;

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

    public static function abs(string $value): string
    {
        return Decimal::abs($value);
    }

    /**
     * Stock as a percentage of capacity, 0-100 at 1 dp.
     */
    public static function percentOf(string $part, string $whole): string
    {
        return Decimal::percentage($part, $whole, 1);
    }

    /**
     * Format for display, e.g. "1,234.567".
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
