<?php

namespace Tests\Unit;

use App\Support\Fbr;
use App\Support\Money;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * FBR Digital Invoicing — SRO 1006(I)/2021.
 *
 * These rules are legally specified, so the exact format, wording and
 * thresholds are pinned here rather than left to the UI layer.
 */
class FbrRulesTest extends TestCase
{
    private const POS_CODE = 'MFS001';

    public function test_fiscal_number_matches_the_sro_format(): void
    {
        $number = Fbr::fiscalNumber(self::POS_CODE, Carbon::parse('2026-09-30 14:05:09'));

        // XXXXXX-DDMMYYHHMMSS-0001
        $this->assertSame('MFS001-300926140509-0001', $number);
        $this->assertTrue(Fbr::isValidFiscalNumber($number));
    }

    public function test_fiscal_number_sequence_starts_at_one_and_increments(): void
    {
        $at = Carbon::parse('2026-09-30 14:05:09');

        $this->assertSame('MFS001-300926140509-0001', Fbr::fiscalNumber(self::POS_CODE, $at, 1));
        $this->assertSame('MFS001-300926140509-0002', Fbr::fiscalNumber(self::POS_CODE, $at, 2));
        $this->assertSame('MFS001-300926140509-0099', Fbr::fiscalNumber(self::POS_CODE, $at, 99));
    }

    public function test_fiscal_number_sequence_cannot_be_zero_or_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Fbr::fiscalNumber(self::POS_CODE, now(), 0);
    }

    public function test_pos_branch_code_must_be_exactly_six_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Fbr::fiscalNumber('MFS1', now());
    }

    public function test_pos_branch_code_is_upper_cased(): void
    {
        $number = Fbr::fiscalNumber('mfs001', Carbon::parse('2026-01-02 03:04:05'));

        $this->assertStringStartsWith('MFS001-', $number);
    }

    public function test_timestamp_round_trips_out_of_the_fiscal_number(): void
    {
        $at = Carbon::parse('2026-09-30 14:05:09');
        $number = Fbr::fiscalNumber(self::POS_CODE, $at);

        $this->assertSame(
            '2026-09-30 14:05:09',
            Fbr::fiscalNumberTimestamp($number),
        );
    }

    public function test_malformed_fiscal_numbers_are_rejected(): void
    {
        foreach ([
            '',
            'MFS001-3009261405-0001',   // 12 digits, not 14
            'MFS001-300926140509-01',   // sequence not 4 digits
            'MFS00-300926140509-0001',  // branch code not 6 chars
            'mfs001-300926140509-0001', // must be upper case
        ] as $bad) {
            $this->assertFalse(
                Fbr::isValidFiscalNumber($bad),
                "[{$bad}] should be rejected as a fiscal number."
            );
            $this->assertNull(Fbr::fiscalNumberTimestamp($bad));
        }
    }

    public function test_verification_statement_matches_the_sro_wording(): void
    {
        // SRO 1006(I)/2021 requires this sentence in a legible font.
        $this->assertSame(
            'Verify this invoice through FBR Tax Asaan Mobile App or SMS at 9966 and win exciting prizes in draw',
            Fbr::VERIFICATION_STATEMENT,
        );
    }

    public function test_pos_service_fee_is_one_rupee(): void
    {
        $this->assertSame('1.00', Fbr::POS_SERVICE_FEE);
        $this->assertSame('1.00', Money::round(Fbr::POS_SERVICE_FEE));
    }

    public function test_qr_code_is_seven_millimetres_square(): void
    {
        $this->assertSame(7, Fbr::QR_SIZE_MM);
    }

    public function test_buyer_details_are_required_above_one_hundred_thousand(): void
    {
        $this->assertFalse(
            Fbr::requiresBuyerDetails('100000.00'),
            'Exactly Rs.100,000 does not exceed the threshold.'
        );

        $this->assertTrue(
            Fbr::requiresBuyerDetails('100000.01'),
            'Rs.100,000.01 exceeds the threshold, so CNIC/NTN is mandatory.'
        );

        $this->assertTrue(
            Fbr::requiresBuyerDetails('250000.00'),
        );
    }

    public function test_buyer_details_are_always_required_for_a_tax_liable_buyer(): void
    {
        // A small invoice still needs the buyer's CNIC/NTN if they are tax-liable.
        $this->assertTrue(Fbr::requiresBuyerDetails('500.00', buyerIsTaxLiable: true));
    }

    public function test_qr_payload_carries_the_key_invoice_facts(): void
    {
        $number = Fbr::fiscalNumber(self::POS_CODE, Carbon::parse('2026-09-30 14:05:09'));

        $payload = Fbr::qrPayload(
            fiscalNumber: $number,
            totalAmount: '6281.25',
            date: '30/09/2026 02:05:09 PM',
            ntn: '1234567-8',
            strn: '17-00-9988',
        );

        $this->assertSame($number, $payload['fbr_invoice_no']);
        $this->assertSame('6281.25', $payload['total_amount']);
        $this->assertSame('1234567-8', $payload['ntn']);
        $this->assertSame('17-00-9988', $payload['strn']);
    }

    public function test_qr_amount_is_rounded_to_two_places(): void
    {
        $payload = Fbr::qrPayload('MFS001-300926140509-0001', '6281.2567', '30/09/2026');

        $this->assertSame('6281.26', $payload['total_amount']);
    }
}
