<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\HtmlString;

final class DocumentCode
{
    private const CODE39 = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];

    public static function render(string $type, string $value, int $size = 104): HtmlString
    {
        $value = trim($value);

        if ($type === 'qr' && $value !== '') {
            $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd);

            return new HtmlString((new Writer($renderer))->writeString($value));
        }

        if ($type === 'barcode' && $value !== '') {
            return new HtmlString(self::code39($value));
        }

        return new HtmlString('');
    }

    private static function code39(string $value): string
    {
        $encoded = '*'.preg_replace('/[^0-9A-Z. $\/+%-]/', '-', mb_strtoupper($value)).'*';
        $x = 8;
        $bars = '';

        foreach (mb_str_split($encoded) as $character) {
            $pattern = self::CODE39[$character] ?? self::CODE39['-'];
            foreach (str_split($pattern) as $index => $width) {
                $barWidth = $width === 'w' ? 3 : 1;
                if ($index % 2 === 0) {
                    $bars .= sprintf('<rect x="%d" y="2" width="%d" height="52" fill="#000"/>', $x, $barWidth);
                }
                $x += $barWidth;
            }
            $x += 1;
        }

        $safeValue = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Code-barres %s" viewBox="0 0 %d 72" width="100%%" height="72"><rect width="100%%" height="100%%" fill="#fff"/>%s<text x="50%%" y="68" text-anchor="middle" font-family="monospace" font-size="10" fill="#000">%s</text></svg>',
            $safeValue,
            $x + 8,
            $bars,
            $safeValue,
        );
    }
}
