<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Timesheet;
use App\Models\LeaveRequest;
use Livewire\Livewire;
use App\Filament\Pages\ProHR;
use App\Filament\Pages\ProHRAbsences;
use App\Filament\Pages\ProHRDirectory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AttendanceExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the actual sqlite database file for testing to bypass historical migrations issues.
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
    }

    /**
     * Test export modal opens and PDF generation can be called from General HR Page.
     */
    public function test_pdf_export_properties_and_generation(): void
    {
        DB::beginTransaction();

        try {
            // 1. Create a user and role
            $user = User::create([
                'name' => 'Test HR Admin',
                'email' => 'test.hr.admin@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Create a teacher
            $teacher = Teacher::create([
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont.test@example.com',
                'phone' => '0102030405',
                'status' => 'Actif',
                'salary' => 3000,
                'annual_leave_allowance' => 30,
                'rtt_allowance' => 10,
            ]);

            // 3. Create an attendance record
            Timesheet::create([
                'teacher_id' => $teacher->id,
                'date' => '2026-07-20',
                'check_in' => '08:00',
                'check_out' => '17:00',
                'total_hours' => 8.0,
            ]);

            // 4. Create a leave request
            LeaveRequest::create([
                'teacher_id' => $teacher->id,
                'start_date' => '2026-07-21',
                'end_date' => '2026-07-21',
                'type' => 'RTT',
                'status' => 'Approuvé',
                'reason' => 'Repos RTT',
            ]);

            // 5. Test Livewire component initialization & PDF download
            $component = Livewire::test(ProHR::class)
                ->set('export_teacher_id', $teacher->id)
                ->set('export_start_date', '2026-07-20')
                ->set('export_end_date', '2026-07-21')
                ->set('export_include_absences', true)
                ->set('export_only_absences', false);

            // Call the export PDF method
            $response = $component->call('exportAttendancePdf');

            // Assert response download is returned
            $response->assertFileDownloaded();
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test export modal opens and PDF generation can be called from Absences Page.
     */
    public function test_pdf_export_from_absences_page(): void
    {
        DB::beginTransaction();

        try {
            // 1. Create a user and role
            $user = User::create([
                'name' => 'Test HR Admin',
                'email' => 'test.hr.admin.absences@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Create a teacher
            $teacher = Teacher::create([
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont.absences@example.com',
                'phone' => '0102030405',
                'status' => 'Actif',
                'salary' => 3000,
                'annual_leave_allowance' => 30,
                'rtt_allowance' => 10,
            ]);

            // 3. Create an attendance record
            Timesheet::create([
                'teacher_id' => $teacher->id,
                'date' => '2026-07-20',
                'check_in' => '08:00',
                'check_out' => '17:00',
                'total_hours' => 8.0,
            ]);

            // 4. Test Livewire component initialization & PDF download
            $component = Livewire::test(ProHRAbsences::class)
                ->set('export_teacher_id', $teacher->id)
                ->set('export_start_date', '2026-07-20')
                ->set('export_end_date', '2026-07-21')
                ->set('export_include_absences', true)
                ->set('export_only_absences', false);

            // Call the export PDF method
            $response = $component->call('exportAttendancePdf');

            // Assert response download is returned
            $response->assertFileDownloaded();
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test export PDF from directory page.
     */
    public function test_pdf_export_from_directory_page(): void
    {
        DB::beginTransaction();

        try {
            // 1. Create a user and role
            $user = User::create([
                'name' => 'Test HR Admin',
                'email' => 'test.hr.admin.dir@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Create a teacher
            $teacher = Teacher::create([
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont.dir@example.com',
                'phone' => '0102030405',
                'status' => 'Actif',
                'salary' => 3000,
                'annual_leave_allowance' => 30,
                'rtt_allowance' => 10,
            ]);

            // 3. Test Livewire component initialization & PDF download
            $component = Livewire::test(ProHRDirectory::class)
                ->set('filter_personnel_status', 'Actif');

            // Call the export PDF method
            $response = $component->call('exportPersonnelPdf');

            // Assert response download is returned
            $response->assertFileDownloaded();
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test wizard form step navigation and conditional data saving.
     */
    public function test_wizard_form_saving(): void
    {
        DB::beginTransaction();

        try {
            // 1. Create a user and role
            $user = User::create([
                'name' => 'Test HR Admin 2',
                'email' => 'test.hr.admin.wizard@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Test Livewire component form wizard steps and saving
            $component = Livewire::test(ProHRDirectory::class)
                ->assertSet('form_step', 1)
                // Step 1: Personal Info
                ->set('form_first_name', 'Marc')
                ->set('form_last_name', 'Mbala')
                ->set('form_email', 'marc.mbala@example.com')
                ->set('form_phone', '+243812345678')
                ->set('form_date_of_birth', '1990-05-15')
                ->set('form_place_of_birth', 'Kinshasa')
                // Go to step 2
                ->set('form_step', 2)
                ->set('form_civil_status', 'Marié')
                ->set('form_spouse_name', 'Helene Mbala')
                ->set('form_children_count', 2)
                ->set('form_children_names', 'Alice, Bob')
                // Go to step 3
                ->set('form_step', 3)
                ->set('form_job_type', 'Temps plein')
                ->set('form_folder_number', 'DOS-2026-999')
                ->set('form_academic_qualification', 'Master en Mathématiques')
                ->set('form_manager_name', 'Directeur Jean')
                ->set('form_specialty', 'Enseignant')
                ->set('form_salary', 3200)
                ->set('form_status', 'Actif')
                ->set('form_job', 'Professeur Adjoint')
                ->set('form_acting_as', 'Titulaire')
                // Save
                ->call('savePersonnel');

            // Assert the record exists in database
            $this->assertDatabaseHas('teachers', [
                'first_name' => 'Marc',
                'last_name' => 'Mbala',
                'civil_status' => 'Marié',
                'spouse_name' => 'Helene Mbala',
                'children_count' => 2,
                'children_names' => 'Alice, Bob',
                'job_type' => 'Temps plein',
                'folder_number' => 'DOS-2026-999',
                'academic_qualification' => 'Master en Mathématiques',
                'manager_name' => 'Directeur Jean',
                'specialty' => 'Enseignant',
                'salary' => 3200,
                'status' => 'Actif',
                'place_of_birth' => 'Kinshasa',
                'acting_as' => 'Titulaire',
                'job' => 'Professeur Adjoint',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test validation blocks transition to next step if required fields are missing.
     */
    public function test_wizard_step_validation_block(): void
    {
        DB::beginTransaction();

        try {
            // 1. Create a user and role
            $user = User::create([
                'name' => 'Test HR Admin 3',
                'email' => 'test.hr.admin.wizard.val@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Test Livewire component form wizard step validation
            Livewire::test(ProHRDirectory::class)
                ->assertSet('form_step', 1)
                // Try to move to step 2 with empty name
                ->set('form_first_name', '')
                ->set('form_last_name', '')
                ->call('goToStep', 2)
                // Assert it fails validation and step remains 1
                ->assertHasErrors(['form_first_name', 'form_last_name'])
                ->assertSet('form_step', 1);
        } finally {
            DB::rollBack();
        }
    }
}
