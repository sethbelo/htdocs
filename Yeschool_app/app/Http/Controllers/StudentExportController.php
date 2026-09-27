<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\School;

class StudentExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $classId = $request->query('class_id');
        $academicYearId = session('selected_academic_year_id') 
            ?? AcademicYear::where('is_active', true)->first()?->id 
            ?? AcademicYear::first()?->id;

        $query = Student::with(['schoolClass', 'enrollments'])
            ->whereHas('enrollments', function($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });

        if ($classId && $classId !== 'all') {
            $query->where('school_class_id', $classId);
        }

        $students = $query->get();
        $school = auth()->user()->school ?? School::first();

        if ($classId && $classId !== 'all') {
            $className = SchoolClass::find($classId)?->name ?? 'Classe';
            $filename = "Liste_Eleves_" . str_replace(' ', '_', $className) . ".csv";
        } else {
            $filename = "Liste_Eleves_Toutes_Classes.csv";
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Matricule', 'Nom', 'Prenom', 'Sexe', 'Date de Naissance', 'Statut', 'Classe'];

        $callback = function() use($students, $columns, $school) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header Information (School details)
            fputcsv($file, ["Établissement :", $school ? $school->name : 'N/A'], ';');
            fputcsv($file, ["Téléphone :", $school ? $school->phone : 'N/A'], ';');
            fputcsv($file, ["Email :", $school ? $school->email : 'N/A'], ';');
            fputcsv($file, ["Adresse :", $school ? $school->address : 'N/A'], ';');
            fputcsv($file, [], ';'); // Separator
            
            fputcsv($file, $columns, ';');

            $grouped = $students->groupBy(function($item) {
                return $item->schoolClass ? $item->schoolClass->name : 'Non assigné';
            });

            foreach ($grouped as $className => $classStudents) {
                fputcsv($file, ["--- CLASSE : $className ---"], ';');
                foreach ($classStudents as $student) {
                    fputcsv($file, [
                        '#STD-' . str_pad($student->id, 4, '0', STR_PAD_LEFT),
                        $student->last_name,
                        $student->first_name,
                        $student->gender === 'M' ? 'Garçon' : 'Fille',
                        $student->date_of_birth,
                        $student->status,
                        $className
                    ], ';');
                }
                fputcsv($file, [], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $classId = $request->query('class_id');
        $academicYearId = session('selected_academic_year_id') 
            ?? AcademicYear::where('is_active', true)->first()?->id 
            ?? AcademicYear::first()?->id;

        $yearName = AcademicYear::find($academicYearId)?->name ?? '';

        $query = Student::with(['schoolClass', 'enrollments'])
            ->whereHas('enrollments', function($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });

        if ($classId && $classId !== 'all') {
            $query->where('school_class_id', $classId);
        }

        $students = $query->get();

        $grouped = $students->groupBy(function($item) {
            return $item->schoolClass ? $item->schoolClass->name : 'Non assigné';
        });

        $title = "Liste des Élèves";
        if ($classId && $classId !== 'all') {
            $className = SchoolClass::find($classId)?->name ?? 'Classe';
            $title .= " - $className";
        }

        $school = auth()->user()->school ?? School::first();

        return view('exports.students-pdf', compact('grouped', 'title', 'yearName', 'school'));
    }
}
