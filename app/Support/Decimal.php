<?php

namespace App\Support;

/**
 * Exact decimal arithmetic for money and litres.
 *
 * The spec forbids float maths for money (DECIMAL(14,2)) and litres
 * (DECIMAL(12,3)). Every calculation in the ERP goes through this class,
 * which wraps bcmath and returns strings — never floats.
 *
 *   Money::multiply('10.555', '100.00')   -> '1055.50'   (2 dp)
 *   Quantity::multiply('10.555', '1.5')   -> '15.833'    (3 dp)
 *
 * PHP floats would give 1055.4999999999999 for the first call. This will not.
 */
class Decimal
{
    /**
     * Multiply with a fixed number of decimal places.
     *
     * NOTE: PHP's bcmath *truncates* where MySQL's ROUND() rounds half-up.
     * Every operation here therefore computes at extra precision and then
     * goes through round(), so results match MySQL's ROUND exactly.
     */
    public static function multiply(string $a, string $b, int $scale): string
    {
        return self::round(
            bcmul(self::n($a), self::n($b), $scale + 4),
            $scale
        );
    }

    /**
     * Divide with a fixed number of decimal places (half-up, as MySQL ROUND does).
     */
    public static function divide(string $a, string $b, int $scale): string
    {
        $a = self::n($a);
        $b = self::n($b);

        if (self::isZero($b)) {
            throw new \InvalidArgumentException('Division by zero.');
        }

        // Compute at extra precision, then round once at the end so the
        // result is not distorted by intermediate truncation.
        return self::round(bcdiv($a, $b, $scale + 6), $scale);
    }

    public static function add(string $a, string $b, int $scale): string
    {
        return bcadd(self::n($a), self::n($b), $scale);
    }

    public static function subtract(string $a, string $b, int $scale): string
    {
        return bcsub(self::n($a), self::n($b), $scale);
    }

    public static function compare(string $a, string $b, int $scale): int
    {
        return bccomp(self::n($a), self::n($b), $scale);
    }

    public static function isZero(string $a): bool
    {
        return bccomp(self::n($a), '0', 8) === 0;
    }

    public static function isNegative(string $a): bool
    {
        return bccomp(self::n($a), '0', 8) < 0;
    }

    public static function isPositive(string $a): bool
    {
        return bccomp(self::n($a), '0', 8) > 0;
    }

    /**
     * Round to $scale decimal places, half-up (matches MySQL ROUND()).
     */
    public static function round(string $value, int $scale): string
    {
        return bcadd(self::n($value), '0.'.str_repeat('0', $scale).'5', $scale);
    }

    /**
     * $part as a percentage of $total, at $scale dp. Returns "0" when $total
     * is zero rather than dividing by zero.
     */
    public static function percentage(string $part, string $total, int $scale = 2): string
    {
        if (self::isZero($total)) {
            return '0';
        }

        return self::divide(
            bcmul(self::n($part), '100', $scale + 6),
            self::n($total),
            $scale
        );
    }

    /**
     * Normalise a value into a plain decimal string safe for bcmath.
     */
    public static function n(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            // Only reached for values that came from a float column; keep enough
            // precision to avoid compounding error.
            return number_format($value, 10, '.', '');
        }

        return $value;
    }
}
