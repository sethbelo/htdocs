<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\HrConflict;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Filament\Pages\ProHRConflicts;
use App\Filament\Pages\ProHRAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class HRModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the actual sqlite database file for testing to bypass historical migrations issues.
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
    }

    /**
     * Test Conflict Management CRUD and state lifecycle.
     */
    public function test_conflicts_crud(): void
    {
        DB::beginTransaction();

        try {
            // 1. Setup Auth
            $user = User::create([
                'name' => 'HR Manager Test',
                'email' => 'hr.manager.conflict@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Setup a teacher
            $teacher = Teacher::create([
                'first_name' => 'Bernard',
                'last_name' => 'Lupin',
                'email' => 'bernard.lupin@example.com',
                'phone' => '0708091011',
                'status' => 'Actif',
                'salary' => 2800,
            ]);

            // 3. Test Livewire component creation & validation
            $component = Livewire::test(ProHRConflicts::class)
                ->call('createConflict')
                ->set('form_employee_involved_id', $teacher->id)
                ->set('form_title', 'Retard répété')
                ->set('form_description', 'L\'employé est arrivé en retard à plusieurs reprises cette semaine.')
                ->set('form_severity', 'Moyenne')
                ->set('form_status', 'En cours')
                ->call('saveConflict');

            $this->assertDatabaseHas('hr_conflicts', [
                'employee_involved_id' => $teacher->id,
                'title' => 'Retard répété',
                'severity' => 'Moyenne',
                'status' => 'En cours',
            ]);

            $conflictId = HrConflict::where('title', 'Retard répété')->first()->id;

            // 4. Test Resolution update
            Livewire::test(ProHRConflicts::class)
                ->call('editConflict', $conflictId)
                ->set('form_status', 'Résolu')
                ->set('form_resolution_notes', 'Avertissement écrit envoyé.')
                ->call('saveConflict');

            $this->assertDatabaseHas('hr_conflicts', [
                'id' => $conflictId,
                'status' => 'Résolu',
                'resolution_notes' => 'Avertissement écrit envoyé.',
            ]);

            // 5. Test deletion
            Livewire::test(ProHRConflicts::class)
                ->call('promptDelete', $conflictId)
                ->call('deleteConflict');

            $this->assertDatabaseMissing('hr_conflicts', [
                'id' => $conflictId,
            ]);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test HR Analytics and PDF export.
     */
    public function test_analytics_and_pdf_export(): void
    {
        DB::beginTransaction();

        try {
            // 1. Setup Auth
            $user = User::create([
                'name' => 'HR Manager Test 2',
                'email' => 'hr.manager.analytics@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Setup teachers for data stats
            Teacher::create([
                'first_name' => 'Bernard 2',
                'last_name' => 'Lupin 2',
                'email' => 'bernard.lupin.2@example.com',
                'phone' => '0708091012',
                'status' => 'Actif',
                'salary' => 2500,
                'specialty' => 'Mathématiques',
            ]);

            // 3. Test Livewire component stats compilation
            $component = Livewire::test(ProHRAnalytics::class);
            
            $stats = $component->get('stats');
            $this->assertGreaterThanOrEqual(1, $stats['active_employees']);
            $this->assertGreaterThanOrEqual(2500.0, $stats['total_salary']);

            $component->set('report_title', 'Rapport Trimestriel')
                ->set('report_content', '<p>Bilan positif</p>');

            // 4. Test PDF Export
            $response = $component->call('exportReportPdf');
            $response->assertFileDownloaded();
        } finally {
            DB::rollBack();
        }
    }
}
