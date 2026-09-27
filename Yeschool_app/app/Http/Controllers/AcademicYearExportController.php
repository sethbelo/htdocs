<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Enrollment;

class AcademicYearExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $status = $request->query('status');
        $query = AcademicYear::orderByDesc('start_date');

        if ($status && $status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $years = $query->get();
        $school = auth()->user()->school ?? School::first();

        $filename = "Liste_Annees_Scolaires.csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Année Scolaire', 'Date de Début', 'Date de Fin', 'Statut', 'Nombre d\'Inscrits'];

        $callback = function() use($years, $columns, $school) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, ["Établissement :", $school ? $school->name : 'N/A'], ';');
            fputcsv($file, ["Téléphone :", $school ? $school->phone : 'N/A'], ';');
            fputcsv($file, ["Email :", $school ? $school->email : 'N/A'], ';');
            fputcsv($file, ["Adresse :", $school ? $school->address : 'N/A'], ';');
            fputcsv($file, [], ';');
            
            fputcsv($file, $columns, ';');

            foreach ($years as $y) {
                $count = Enrollment::where('academic_year_id', $y->id)->count();
                fputcsv($file, [
                    $y->name,
                    $y->start_date ? \Carbon\Carbon::parse($y->start_date)->format('Y-m-d') : '',
                    $y->end_date ? \Carbon\Carbon::parse($y->end_date)->format('Y-m-d') : '',
                    $y->is_active ? 'Active (Actuelle)' : 'Inactive',
                    $count
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $status = $request->query('status');
        $query = AcademicYear::orderByDesc('start_date');

        if ($status && $status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $years = $query->get();
        $school = auth()->user()->school ?? School::first();

        foreach ($years as $y) {
            $y->students_count = Enrollment::where('academic_year_id', $y->id)->count();
        }

        $title = "Liste des Années Scolaires";
        if ($status && $status !== 'all') {
            $title .= $status === 'active' ? " (Actives uniquement)" : " (Inactives uniquement)";
        }

        return view('exports.years-pdf', compact('years', 'title', 'school'));
    }
}
