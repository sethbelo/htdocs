<?php

namespace App\Services\Printing;

use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Rendu PDF des documents à la charte SURNATUREL PRINT :
 * A4 / A5 (papier à en-tête), A4 paysage, tickets thermiques 80 mm, cartes CR80 et planches de cartes.
 */
class PdfRenderer
{
    public const TICKET_WIDTH_PT = 226.77;

    /** Carte CR80 (85,6 × 54 mm) en points. */
    public const CARD_PT = [242.65, 153.07];

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data, string $filename, string $format = 'a4'): Response
    {
        return $this->make($view, $data, $format)->stream($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename, string $format = 'a4'): Response
    {
        return $this->make($view, $data, $format)->download($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function make(string $view, array $data, string $format = 'a4'): DomPdf
    {
        $data['company'] ??= $this->company();
        $data['format'] = $format;

        $pdf = Pdf::loadView($view, $data)->setOption(['defaultFont' => 'Helvetica']);

        match ($format) {
            'ticket' => $pdf->setPaper([0, 0, self::TICKET_WIDTH_PT, $data['height'] ?? $this->ticketHeight($data['lines'] ?? 10)]),
            'a5' => $pdf->setPaper('a5', 'portrait'),
            'landscape' => $pdf->setPaper('a4', 'landscape'),
            'card' => $pdf->setPaper([0, 0, self::CARD_PT[0], self::CARD_PT[1]]),
            'card-portrait' => $pdf->setPaper([0, 0, self::CARD_PT[1], self::CARD_PT[0]]),
            'custom' => $pdf->setPaper($data['paper']),
            default => $pdf->setPaper('a4', 'portrait'),
        };

        if (in_array($format, ['a4', 'a5', 'landscape'], true)) {
            $this->stampPageNumbers($pdf, $format);
        }

        return $pdf;
    }

    /**
     * « Page n / total » en pied de page (le total n'est connu qu'après le rendu).
     */
    private function stampPageNumbers(DomPdf $pdf, string $format): void
    {
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('Helvetica', 'normal');
        [$size, $right, $bottom] = $format === 'a5' ? [6.3, 26, 37] : [6.9, 34, 41];
        $width = $metrics->getTextWidth('Page 00 / 00', $font, $size);

        $canvas->page_text($canvas->get_width() - $right - $width, $canvas->get_height() - $bottom, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, $size, [0.42, 0.42, 0.5]);
    }

    /**
     * @return array<string, mixed>
     */
    public function company(): array
    {
        $custom = Setting::get('company.logo_path');

        return [
            'name' => Setting::get('company.name', config('app.name')),
            'legal_name' => Setting::get('company.legal_name'),
            'slogan' => Setting::get('company.slogan'),
            'address' => Setting::get('company.address'),
            'city' => Setting::get('company.city'),
            'country' => Setting::get('company.country'),
            'phone' => Setting::get('company.phone'),
            'email' => Setting::get('company.email'),
            'website' => Setting::get('company.website'),
            'rccm' => Setting::get('company.rccm'),
            'id_nat' => Setting::get('company.id_nat'),
            'nif' => Setting::get('company.nif'),
            'bank_details' => Setting::get('company.bank_details'),
            'footer' => Setting::get('company.ticket_footer'),
            'logo' => $custom && Storage::disk('public')->exists($custom)
                ? 'data:'.Storage::disk('public')->mimeType($custom).';base64,'.base64_encode(Storage::disk('public')->get($custom))
                : self::brandImage('logo.png'),
            'logo_light' => self::brandImage('logo-light.png'),
            'mark' => self::brandImage('mark.png'),
        ];
    }

    /**
     * Image de la charte (dossier public/brand) encodée pour Dompdf.
     */
    public static function brandImage(string $file): ?string
    {
        $path = public_path("brand/{$file}");

        return is_file($path) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($path)) : null;
    }

    /**
     * Hauteur estimée d'un ticket en points selon le nombre de lignes à imprimer.
     */
    private function ticketHeight(int $lines): float
    {
        return 700 + $lines * 32;
    }
}
