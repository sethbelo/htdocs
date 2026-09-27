<?php

namespace App\Services\Printing;

use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Rendu PDF des documents : A4, A5 et tickets thermiques (80 mm de large, hauteur adaptée au contenu).
 */
class PdfRenderer
{
    public const TICKET_WIDTH_PT = 226.77;

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data, string $filename, string $format = 'a4'): Response
    {
        $data['company'] = $this->company();
        $data['format'] = $format;

        $pdf = Pdf::loadView($view, $data);

        match ($format) {
            'ticket' => $pdf->setPaper([0, 0, self::TICKET_WIDTH_PT, $data['height'] ?? $this->ticketHeight($data['lines'] ?? 10)]),
            'a5' => $pdf->setPaper('a5', 'portrait'),
            'card' => $pdf->setPaper([0, 0, 242.65, 153.07]),
            default => $pdf->setPaper('a4', 'portrait'),
        };

        return $pdf->stream($filename);
    }

    /**
     * @return array<string, mixed>
     */
    public function company(): array
    {
        $logo = Setting::get('company.logo_path');

        return [
            'name' => Setting::get('company.name', config('app.name')),
            'legal_name' => Setting::get('company.legal_name'),
            'address' => Setting::get('company.address'),
            'city' => Setting::get('company.city'),
            'phone' => Setting::get('company.phone'),
            'email' => Setting::get('company.email'),
            'website' => Setting::get('company.website'),
            'rccm' => Setting::get('company.rccm'),
            'id_nat' => Setting::get('company.id_nat'),
            'nif' => Setting::get('company.nif'),
            'footer' => Setting::get('company.ticket_footer'),
            'logo' => $logo && Storage::disk('public')->exists($logo)
                ? 'data:'.Storage::disk('public')->mimeType($logo).';base64,'.base64_encode(Storage::disk('public')->get($logo))
                : null,
        ];
    }

    /**
     * Hauteur estimée d'un ticket en points selon le nombre de lignes à imprimer.
     */
    private function ticketHeight(int $lines): float
    {
        return 680 + $lines * 32;
    }
}
