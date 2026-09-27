<?php

namespace App\Services\Support;

use Picqer\Barcode\Renderers\PngRenderer;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Génération de QR Codes et codes-barres (Code 128) pour tickets, cartes fidélité et étiquettes.
 */
class Codes
{
    public static function qrSvg(string $content, int $size = 120): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(0)->errorCorrection('M')->generate($content);
    }

    public static function qrDataUri(string $content, int $size = 120): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::qrSvg($content, $size));
    }

    public static function barcodeSvg(string $content, int $widthFactor = 2, int $height = 40): string
    {
        $barcode = (new TypeCode128)->getBarcode($content);

        return (new SvgRenderer)->setForegroundColor([0, 0, 0])->render($barcode, $barcode->getWidth() * $widthFactor, $height);
    }

    public static function barcodeDataUri(string $content, int $widthFactor = 2, int $height = 40): string
    {
        if (extension_loaded('gd') || extension_loaded('imagick')) {
            $barcode = (new TypeCode128)->getBarcode($content);

            return 'data:image/png;base64,'.base64_encode((new PngRenderer)->render($barcode, $barcode->getWidth() * $widthFactor, $height));
        }

        return 'data:image/svg+xml;base64,'.base64_encode(self::barcodeSvg($content, $widthFactor, $height));
    }
}
