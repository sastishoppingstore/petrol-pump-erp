<?php

namespace Tests\Unit;

use App\Support\UrduNumber;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class UrduNumberTest extends TestCase
{
    public function test_lakh_formatting(): void
    {
        $this->assertSame('Rs. 0', UrduNumber::lakhFormat(0));
        $this->assertSame('Rs. 500', UrduNumber::lakhFormat(500));
        $this->assertSame('Rs. 1,000', UrduNumber::lakhFormat(1000));
        $this->assertSame('Rs. 50,000', UrduNumber::lakhFormat(50000));
        $this->assertSame('Rs. 4,50,000', UrduNumber::lakhFormat(450000));
        $this->assertSame('Rs. 12,34,567', UrduNumber::lakhFormat(1234567));
        $this->assertSame('Rs. 1,00,00,000', UrduNumber::lakhFormat(10000000));
        $this->assertSame('4,50,000', UrduNumber::lakhFormat(450000, 0, false));
        $this->assertSame('Rs. 4,50,000.50', UrduNumber::lakhFormat(450000.5, 2));
    }

    public function test_amount_in_urdu_words(): void
    {
        $this->assertSame('صفر روپے صرف', UrduNumber::amountInWords(0));
        $this->assertSame('ایک سو روپے صرف', UrduNumber::amountInWords(100));
        $this->assertSame('ایک ہزار روپے صرف', UrduNumber::amountInWords(1000));
        $this->assertSame('چار لاکھ پچاس ہزار روپے صرف', UrduNumber::amountInWords(450000));
        $this->assertSame('ایک کروڑ روپے صرف', UrduNumber::amountInWords(10000000));
        $this->assertSame('ایک سو روپے اور پچاس پیسے صرف', UrduNumber::amountInWords(100.50));
    }

    public function test_urdu_digits(): void
    {
        $this->assertSame('۰', UrduNumber::toUrduDigits(0));
        $this->assertSame('۱۲۳۴۵۶۷۸۹۰', UrduNumber::toUrduDigits('1234567890'));
        $this->assertSame('Rs. ۱,۲۳,۴۵۶', UrduNumber::toUrduDigits('Rs. 1,23,456'));
    }

    public function test_urdu_date(): void
    {
        $date = Carbon::parse('2026-09-30 14:30:00');
        $formatted = UrduNumber::urduDate($date);
        $this->assertStringContainsString('30 ستمبر 2026', $formatted);
    }
}
