<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ReceiptCodeGenerator
{
    public function dataUri(string $type, string $value): ?string
    {
        return match ($type) {
            'qrcode' => $this->qrCode($value),
            'barcode' => $this->barcode($value),
            default => null,
        };
    }

    private function qrCode(string $value): string
    {
        $renderer = new ImageRenderer(new RendererStyle(180, 1), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($value);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function barcode(string $value): string
    {
        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '*' => 'nwnnwnwnn',
            '$' => 'nwnwnwnnn', '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn',
        ];

        $encoded = '*'.strtoupper($value).'*';
        $x = 8;
        $bars = '';
        foreach (str_split($encoded) as $character) {
            $pattern = $patterns[$character] ?? $patterns['-'];
            foreach (str_split($pattern) as $index => $widthType) {
                $width = $widthType === 'w' ? 3 : 1;
                if ($index % 2 === 0) {
                    $bars .= sprintf('<rect x="%d" y="4" width="%d" height="42" fill="#000"/>', $x, $width);
                }
                $x += $width;
            }
            $x += 2;
        }

        $safeValue = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="62" viewBox="0 0 %d 62"><rect width="100%%" height="100%%" fill="#fff"/>%s<text x="50%%" y="58" text-anchor="middle" font-family="monospace" font-size="9" fill="#000">%s</text></svg>',
            $x + 8,
            $x + 8,
            $bars,
            $safeValue
        );

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
