<?php

namespace Tests\Feature;

use App\Livewire\Admin\Appointments\Index as AppointmentsIndex;
use App\Livewire\Admin\Reservations\Index as ReservationsIndex;
use App\Models\Appointment;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PlanningDataExchangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_and_pdf_exports_are_downloadable_for_both_plannings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->enablePlanningModules($admin);
        $this->actingAs($admin);

        foreach (['appointments', 'reservations'] as $dataset) {
            $excel = $this->get(route('admin.planning-data.export', [$dataset, 'xlsx']));
            $excel->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->assertStringStartsWith('PK', $excel->streamedContent());

            $template = $this->get(route('admin.planning-data.export', [$dataset, 'template']));
            $template->assertOk();
            $this->assertStringStartsWith('PK', $template->streamedContent());
        }

        foreach (['modern', 'classic', 'compact'] as $pdfTemplate) {
            Setting::updateOrCreate([
                'establishment_id' => $admin->establishment_id,
                'branch_id' => null,
                'group' => 'documents',
                'key' => 'pdf_template',
            ], ['value' => $pdfTemplate, 'type' => 'string']);

            $pdf = $this->get(route('admin.planning-data.export', ['appointments', 'pdf']));
            $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        }
    }

    public function test_excel_import_creates_appointments_and_reservations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->enablePlanningModules($admin);
        $this->actingAs($admin);

        Livewire::test(AppointmentsIndex::class)
            ->set('importFile', $this->excelUpload('appointments-import.xlsx', [
                ['Client', 'Téléphone', 'E-mail', 'Service', 'Collaborateur', 'Date', 'Heure', 'Durée (min)', 'Statut', 'Notes', 'Rappel WhatsApp'],
                ['Client Excel', '0811111111', 'excel@example.test', 'Coiffure', 'Sarah', '30/08/2026', '09:30', 45, 'scheduled', 'Import test', ''],
            ]))
            ->call('importExcel')
            ->assertHasNoErrors();

        Livewire::test(ReservationsIndex::class)
            ->set('importFile', $this->excelUpload('reservations-import.xlsx', [
                ['Client', 'Téléphone', 'E-mail', 'Date', 'Heure', 'Durée (min)', 'Personnes', 'Table', 'Statut', 'Demandes particulières'],
                ['Groupe Excel', '0822222222', '', '31/08/2026', '18:00', 120, 5, '', 'pending', 'Près de la fenêtre'],
            ]))
            ->call('importExcel')
            ->assertHasNoErrors();

        $this->assertTrue(Appointment::where('client_name', 'Client Excel')->exists());
        $this->assertTrue(Reservation::where('guest_name', 'Groupe Excel')->exists());
    }

    private function enablePlanningModules(User $admin): void
    {
        foreach (['appointments_enabled', 'reservations_enabled'] as $key) {
            Setting::create([
                'establishment_id' => $admin->establishment_id,
                'group' => 'modules',
                'key' => $key,
                'value' => '1',
                'type' => 'boolean',
            ]);
        }
    }

    private function excelUpload(string $name, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'yeboox-xlsx-');
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent($name, file_get_contents($path));
    }
}
