<?php

namespace App\Http\Controllers;

use App\Services\PlanningDataExchange;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanningDataExportController extends Controller
{
    public function __invoke(string $dataset, string $format, PlanningDataExchange $exchange): Response|StreamedResponse
    {
        abort_unless(in_array($dataset, ['appointments', 'reservations'], true), 404);
        abort_unless(in_array($format, ['xlsx', 'pdf', 'template'], true), 404);
        abort_unless(auth()->user()->can($dataset.'.export') || ($format === 'template' && auth()->user()->can($dataset.'.import')), 403);

        $establishment = auth()->user()->establishment;
        $date = now()->format('Y-m-d');
        $label = $dataset === 'appointments' ? 'rendez-vous' : 'reservations';

        if ($format === 'pdf') {
            return $exchange->pdf($dataset, $establishment)->download("{$label}-{$date}.pdf");
        }

        $spreadsheet = $exchange->spreadsheet($dataset, $establishment, $format === 'template');
        $suffix = $format === 'template' ? 'modele-import' : $date;

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            "{$label}-{$suffix}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
