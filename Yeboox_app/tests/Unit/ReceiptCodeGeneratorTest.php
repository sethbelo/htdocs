<?php

namespace Tests\Unit;

use App\Services\ReceiptCodeGenerator;
use PHPUnit\Framework\TestCase;

class ReceiptCodeGeneratorTest extends TestCase
{
    public function test_it_generates_qr_and_barcode_svg_data_uris(): void
    {
        $generator = new ReceiptCodeGenerator;

        $qrCode = $generator->dataUri('qrcode', 'CMD-260827-0001');
        $barcode = $generator->dataUri('barcode', 'CMD-260827-0001');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrCode);
        $this->assertStringContainsString('<svg', base64_decode(substr($qrCode, strpos($qrCode, ',') + 1)));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $barcode);
        $this->assertStringContainsString('CMD-260827-0001', base64_decode(substr($barcode, strpos($barcode, ',') + 1)));
        $this->assertNull($generator->dataUri('none', 'CMD-260827-0001'));
    }
}
