<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\School;
use App\Models\AcademicYear;

class ClassExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $level = $request->query('level');
        $query = SchoolClass::with('headTeacher');

        if ($level && $level !== 'all') {
            $query->where('level', $level);
        }

        $classes = $query->get();
        $school = auth()->user()->school ?? School::first();

        $filename = "Liste_Classes.csv";
        if ($level && $level !== 'all') {
            $filename = "Liste_Classes_" . str_replace(' ', '_', $level) . ".csv";
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Classe', 'Niveau/Cycle', 'Enseignant Principal', 'Capacité Max', 'Élèves Inscrits', 'Statut'];

        $callback = function() use($classes, $columns, $school) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header (School details)
            fputcsv($file, ["Établissement :", $school ? $school->name : 'N/A'], ';');
            fputcsv($file, ["Téléphone :", $school ? $school->phone : 'N/A'], ';');
            fputcsv($file, ["Email :", $school ? $school->email : 'N/A'], ';');
            fputcsv($file, ["Adresse :", $school ? $school->address : 'N/A'], ';');
            fputcsv($file, [], ';');
            
            fputcsv($file, $columns, ';');

            foreach ($classes as $c) {
                $headTeacherName = $c->headTeacher 
                    ? $c->headTeacher->first_name . ' ' . $c->headTeacher->last_name 
                    : 'Non assigné';

                fputcsv($file, [
                    $c->name,
                    $c->level,
                    $headTeacherName,
                    $c->capacity,
                    $c->enrolled_students ?? 0,
                    $c->status === 'Active' ? 'Actif' : $c->status
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $level = $request->query('level');
        $query = SchoolClass::with('headTeacher');

        if ($level && $level !== 'all') {
            $query->where('level', $level);
        }

        $classes = $query->get();
        $school = auth()->user()->school ?? School::first();

        $title = "Liste des Classes & Niveaux";
        if ($level && $level !== 'all') {
            $title .= " - Niveau $level";
        }

        return view('exports.classes-pdf', compact('classes', 'title', 'school'));
    }
}
