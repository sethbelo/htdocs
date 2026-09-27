<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\HeaderUtils;

/** Generates every administrative PDF with the active Configuration model. */
final class ReportPdfGenerator
{
    public function download(
        string $view,
        array $data,
        string $filename,
        string $generatedBy = 'Système',
        string $orientation = 'landscape',
    ) {
        $storage = storage_path('app/dompdf');
        File::ensureDirectoryExists($storage);

        // The shared context always wins for presentation and company identity.
        $document = DocumentPreferences::reportDocumentContext($generatedBy);
        $document['company'] = [...$document['company'], ...($data['company'] ?? [])];
        unset($data['company']);
        $html = view($view, [...$data, ...$document])->render();

        $options = new Options([
            'defaultFont' => DocumentPreferences::pdfFontFamily($document['preferences']['font_family']),
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'tempDir' => $storage,
            'fontDir' => $storage,
            'fontCache' => $storage,
            'chroot' => [public_path(), storage_path()],
        ]);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', $orientation);
        $pdf->render();

        // Page numbering is placed relative to the canvas for both orientations.
        $canvas = $pdf->getCanvas();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text($canvas->get_width() - 94, $canvas->get_height() - 24, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.35, 0.39, 0.46]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Yeboox-Document-Template' => $document['preferences']['template'],
        ]);
    }
}
