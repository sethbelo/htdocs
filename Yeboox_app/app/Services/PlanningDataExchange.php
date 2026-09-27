<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Establishment;
use App\Models\Reservation;
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PlanningDataExchange
{
    public function spreadsheet(string $dataset, Establishment $establishment, bool $template = false): Spreadsheet
    {
        [$title, $headers, $rows] = $this->dataset($dataset, $establishment, $template);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(Str::limit($title, 31, ''));
        $sheet->fromArray($headers, null, 'A1');
        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1D4ED8']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->freezePane('A2');
        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setCompany($establishment->name)
            ->setTitle($title);

        return $spreadsheet;
    }

    public function pdf(string $dataset, Establishment $establishment)
    {
        [$title, $headers, $rows] = $this->dataset($dataset, $establishment);
        $template = $establishment->getSetting('pdf_template', 'modern');
        if (! in_array($template, ['modern', 'classic', 'compact'], true)) {
            $template = 'modern';
        }

        $logoData = null;
        if ($establishment->logo_path && is_file(storage_path('app/public/'.$establishment->logo_path))) {
            $path = storage_path('app/public/'.$establishment->logo_path);
            $logoData = 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
        }

        return Pdf::loadView('reports.planning', compact(
            'title', 'headers', 'rows', 'template', 'establishment', 'logoData'
        ))->setPaper('a4', 'landscape');
    }

    public function import(string $dataset, string $path, Establishment $establishment): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);
        if (count($data) < 2) {
            return ['imported' => 0, 'errors' => ['Le fichier ne contient aucune ligne de données.']];
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), array_shift($data));
        $imported = 0;
        $errors = [];

        foreach ($data as $index => $values) {
            if (collect($values)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }
            $row = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
            try {
                $dataset === 'appointments'
                    ? $this->importAppointment($row, $establishment)
                    : $this->importReservation($row, $establishment);
                $imported++;
            } catch (\Throwable $exception) {
                $errors[] = 'Ligne '.($index + 2).' : '.$exception->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => array_slice($errors, 0, 25)];
    }

    private function dataset(string $dataset, Establishment $establishment, bool $template = false): array
    {
        abort_unless(in_array($dataset, ['appointments', 'reservations'], true), 404);

        if ($dataset === 'appointments') {
            $headers = ['Client', 'Téléphone', 'E-mail', 'Service', 'Collaborateur', 'Date', 'Heure', 'Durée (min)', 'Statut', 'Notes', 'Rappel WhatsApp'];
            $rows = $template ? [] : Appointment::forEstablishment($establishment->id)
                ->orderBy('appointment_date')->orderBy('appointment_time')->get()
                ->map(fn (Appointment $item) => [
                    $item->client_name, $item->client_phone, $item->client_email, $item->service_name,
                    $item->staff_name, $item->appointment_date->format('d/m/Y'), substr($item->appointment_time, 0, 5),
                    $item->duration_minutes, $item->status, $item->notes,
                    $item->reminder_sent_at?->format('d/m/Y H:i'),
                ])->all();

            return ['Rendez-vous', $headers, $rows];
        }

        $headers = ['Client', 'Téléphone', 'E-mail', 'Date', 'Heure', 'Durée (min)', 'Personnes', 'Table', 'Statut', 'Demandes particulières'];
        $rows = $template ? [] : Reservation::forEstablishment($establishment->id)->with('table')
            ->orderBy('reservation_date')->orderBy('reservation_time')->get()
            ->map(fn (Reservation $item) => [
                $item->guest_name, $item->guest_phone, $item->guest_email, $item->reservation_date->format('d/m/Y'),
                substr($item->reservation_time, 0, 5), $item->duration_minutes, $item->party_size,
                $item->table?->name, $item->status, $item->special_requests,
            ])->all();

        return ['Réservations', $headers, $rows];
    }

    private function importAppointment(array $row, Establishment $establishment): void
    {
        foreach (['client', 'telephone', 'service', 'date', 'heure'] as $required) {
            throw_if(blank($row[$required] ?? null), \RuntimeException::class, "La colonne {$required} est obligatoire.");
        }
        $status = (string) ($row['statut'] ?? 'scheduled');
        throw_unless(in_array($status, ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'], true), \RuntimeException::class, 'Statut invalide.');

        Appointment::create([
            'establishment_id' => $establishment->id,
            'branch_id' => auth()->user()->branch_id,
            'client_name' => $row['client'],
            'client_phone' => $row['telephone'],
            'client_email' => ($row['e_mail'] ?? null) ?: null,
            'service_name' => $row['service'],
            'staff_name' => ($row['collaborateur'] ?? null) ?: null,
            'appointment_date' => $this->parseDate($row['date']),
            'appointment_time' => $this->parseTime($row['heure']),
            'duration_minutes' => max(15, (int) (($row['duree_min'] ?? null) ?: 60)),
            'status' => $status,
            'notes' => ($row['notes'] ?? null) ?: null,
            'created_by' => auth()->id(),
        ]);
    }

    private function importReservation(array $row, Establishment $establishment): void
    {
        foreach (['client', 'date', 'heure', 'personnes'] as $required) {
            throw_if(blank($row[$required] ?? null), \RuntimeException::class, "La colonne {$required} est obligatoire.");
        }
        $status = (string) ($row['statut'] ?? 'pending');
        throw_unless(in_array($status, ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'], true), \RuntimeException::class, 'Statut invalide.');
        $tableId = null;
        if (filled($row['table'] ?? null)) {
            $tableId = Table::forEstablishment($establishment->id)
                ->where(fn ($query) => $query->where('name', $row['table'])->orWhere('number', $row['table']))
                ->value('id');
        }

        Reservation::create([
            'establishment_id' => $establishment->id,
            'branch_id' => auth()->user()->branch_id,
            'table_id' => $tableId,
            'guest_name' => $row['client'],
            'guest_phone' => ($row['telephone'] ?? null) ?: null,
            'guest_email' => ($row['e_mail'] ?? null) ?: null,
            'reservation_date' => $this->parseDate($row['date']),
            'reservation_time' => $this->parseTime($row['heure']),
            'duration_minutes' => max(15, (int) (($row['duree_min'] ?? null) ?: 120)),
            'party_size' => max(1, (int) $row['personnes']),
            'status' => $status,
            'special_requests' => ($row['demandes_particulieres'] ?? null) ?: null,
            'created_by' => auth()->id(),
        ]);
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    private function parseDate(mixed $value): string
    {
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim((string) $value))->format('Y-m-d');
            } catch (\Throwable) {
            }
        }
        throw new \RuntimeException('Date invalide. Utilisez JJ/MM/AAAA.');
    }

    private function parseTime(mixed $value): string
    {
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i');
        }
        if (preg_match('/^(\d{1,2}):(\d{2})/', trim((string) $value), $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }
        throw new \RuntimeException('Heure invalide. Utilisez HH:MM.');
    }
}
