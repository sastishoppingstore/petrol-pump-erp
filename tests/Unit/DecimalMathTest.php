<?php

namespace Tests\Unit;

use App\Support\Decimal;
use App\Support\Money;
use App\Support\Quantity;
use PHPUnit\Framework\TestCase;

/**
 * The money and litre engine.
 *
 * The spec forbids float arithmetic for DECIMAL money and litre columns.
 * These tests pin the exact rounding behaviour, including the classic cases
 * where PHP floats give the wrong answer (2.675, 1.115) and the case where
 * bcmath truncates where MySQL ROUND() rounds.
 */
class DecimalMathTest extends TestCase
{
    public function test_amount_equals_litres_times_rate_rounded_to_two_places(): void
    {
        $this->assertSame('1055.50', Money::amountForLitres('10.555', '100.00'));
        $this->assertSame('1500.00', Money::amountForLitres('10', '150'));
        $this->assertSame('0.00', Money::amountForLitres('0', '100'));
    }

    public function test_litres_equal_amount_divided_by_rate_rounded_to_three_places(): void
    {
        $this->assertSame('10.555', Money::litresForAmount('1055.50', '100.00'));
        $this->assertSame('33.333', Money::litresForAmount('100', '3'));
        $this->assertSame('333.333', Money::litresForAmount('1000', '3'));
        $this->assertSame('1500.000', Money::litresForAmount('1500', '1'));
    }

    public function test_litres_for_amount_rejects_a_zero_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::litresForAmount('100', '0');
    }

    /**
     * PHP's bcmath truncates; MySQL's ROUND() rounds half-up. The helper must
     * behave like MySQL, otherwise every sale total drifts low.
     */
    public function test_rounding_is_half_up_like_mysql_not_truncating_like_bcmath(): void
    {
        $this->assertSame('0.15', Money::amountForLitres('0.001', '149.99'));
        $this->assertSame('0.01', Money::amountForLitres('0.005', '1.00'));
        $this->assertSame('0.03', Money::amountForLitres('0.025', '1.00'));
        $this->assertSame('0.15', Money::round('0.14999'));
        $this->assertSame('1.24', Money::round('1.235'));
    }

    /**
     * Values that PHP's native float arithmetic rounds the *wrong* way.
     * A float-based implementation fails these.
     */
    public function test_values_that_break_native_float_arithmetic(): void
    {
        // round(2.675, 2) === 2.67 in PHP with floats; correct answer is 2.68.
        $this->assertSame('2.68', Money::amountForLitres('2.675', '1.00'));

        // round(1.115, 2) === 1.11 in PHP with floats; correct answer is 1.12.
        $this->assertSame('1.12', Money::amountForLitres('1.115', '1.00'));

        // 0.1 + 0.2 !== 0.3 with floats.
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
    }

    public function test_addition_and_subtraction_keep_the_declared_scale(): void
    {
        $this->assertSame('6.500', Quantity::subtract('10', '3.5'));
        $this->assertSame('13.500', Quantity::add('10', '3.5'));
        $this->assertSame('0.000', Quantity::add('0.001', '-0.001'));
        $this->assertSame('1999.98', Money::subtract('2000.00', '0.02'));
    }

    public function test_comparison_helpers(): void
    {
        $this->assertTrue(Money::isZero('0.00'));
        $this->assertTrue(Money::isZero('0'));
        $this->assertFalse(Money::isZero('0.01'));

        $this->assertTrue(Money::isNegative('-0.01'));
        $this->assertFalse(Money::isNegative('0.00'));

        $this->assertTrue(Money::isPositive('0.01'));

        $this->assertSame(0, Money::compare('10.00', '10.000'));
        $this->assertSame(1, Money::compare('10.01', '10.00'));
        $this->assertSame(-1, Money::compare('9.99', '10.00'));
    }

    public function test_percentage_handles_a_zero_total(): void
    {
        $this->assertSame('25.0', Quantity::percentOf('50', '200'));
        $this->assertSame('0', Quantity::percentOf('50', '0'));
        $this->assertSame('100.0', Quantity::percentOf('100', '100'));
    }

    public function test_division_by_zero_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Decimal::divide('10', '0', 2);
    }

    public function test_null_and_empty_values_normalise_to_zero(): void
    {
        $this->assertSame('0.00', Money::add(Decimal::n(null), '0'));
        $this->assertSame('0', Decimal::n(''));
    }

    /**
     * A full sale calculation, end to end, the way SaleService will use it.
     * The expected values are MySQL's own: SELECT ROUND(25.125*249.99, 2) = 6281.00.
     */
    public function test_litres_mode_sale_calculation(): void
    {
        $litres = '25.125';
        $rate = '249.99';

        $amount = Money::amountForLitres($litres, $rate);

        // 25.125 x 249.99 = 6280.99875 -> 6281.00
        $this->assertSame('6281.00', $amount);
    }

    /**
     * Amount mode (spec section 3): litres are back-calculated from the amount
     * the customer entered, and **the entered amount is the final amount**.
     *
     * Because litres are stored to only 3 decimal places, multiplying them
     * back by the rate does not always reproduce the amount exactly
     * (1.980 x 252.50 = 499.95). That is expected and correct: the customer
     * paid 500.00 and the invoice says 500.00.
     */
    public function test_amount_mode_sale_keeps_the_entered_amount_exactly(): void
    {
        $amount = '500.00';
        $rate = '252.50';

        $litres = Money::litresForAmount($amount, $rate);

        $this->assertSame('1.980', $litres, 'ROUND(500.00 / 252.50, 3)');

        // The back-calculated litres are within one millilitre of the amount.
        $recomputed = Money::amountForLitres($litres, $rate);
        $this->assertSame('499.95', $recomputed);
        $this->assertSame('0.05', Money::subtract($amount, $recomputed));
    }

    public function test_litre_scale_is_three_and_money_scale_is_two(): void
    {
        $this->assertSame(2, Money::SCALE);
        $this->assertSame(3, Quantity::SCALE);
    }
}
