<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Filament\Pages\ProHRDirectory;
use App\Filament\Pages\ProHRDepartments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class HRDepartmentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the actual sqlite database file for testing to bypass historical migrations issues.
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
    }

    /**
     * Test department seeder and teacher association.
     */
    public function test_department_seeding_and_teacher_association(): void
    {
        DB::beginTransaction();

        try {
            // 1. Setup Auth
            $user = User::create([
                'name' => 'HR Manager Departments',
                'email' => 'hr.manager.departments@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Render ProHRDirectory which auto-seeds default departments
            $component = Livewire::test(ProHRDirectory::class);

            // 3. Verify defaults exist
            $this->assertDatabaseHas('departments', ['name' => 'Enseignant']);
            $this->assertDatabaseHas('departments', ['name' => 'RH']);
            $this->assertDatabaseHas('departments', ['name' => 'Nettoyage']);

            $ensDept = Department::where('name', 'Enseignant')->first();

            // 4. Create teacher associated to Enseignant department
            $teacher = Livewire::test(ProHRDirectory::class)
                ->call('createPersonnel')
                ->set('form_first_name', 'Sophie')
                ->set('form_last_name', 'Marlin')
                ->set('form_specialty', 'Français')
                ->set('form_status', 'Actif')
                ->set('form_department_id', $ensDept->id)
                ->call('savePersonnel');

            $this->assertDatabaseHas('teachers', [
                'first_name' => 'Sophie',
                'last_name' => 'Marlin',
                'department_id' => $ensDept->id,
            ]);

            // 5. Check grouping in loadData
            $component = Livewire::test(ProHRDirectory::class);
            $personnelList = $component->get('personnelList');

            $this->assertArrayHasKey('Enseignant', $personnelList);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test Department CRUD page.
     */
    public function test_departments_crud_page(): void
    {
        DB::beginTransaction();

        try {
            // 1. Setup Auth
            $user = User::create([
                'name' => 'HR Manager Depts CRUD',
                'email' => 'hr.manager.depts.crud@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Setup teacher for manager
            $teacher = Teacher::create([
                'first_name' => 'Jacques',
                'last_name' => 'Dupond',
                'email' => 'jacques.dupond@example.com',
                'phone' => '0708091020',
                'status' => 'Actif',
            ]);

            // 3. Test Create Department
            Livewire::test(ProHRDepartments::class)
                ->call('createDepartment')
                ->set('form_name', 'Recherche & Dév')
                ->set('form_description', 'Département de R&D pédagogique')
                ->set('form_manager_id', $teacher->id)
                ->call('saveDepartment');

            $this->assertDatabaseHas('departments', [
                'name' => 'Recherche & Dév',
                'description' => 'Département de R&D pédagogique',
                'manager_id' => $teacher->id,
            ]);

            $deptId = Department::where('name', 'Recherche & Dév')->first()->id;

            // 4. Test Update
            Livewire::test(ProHRDepartments::class)
                ->call('editDepartment', $deptId)
                ->set('form_description', 'Nouveau descriptif de R&D')
                ->call('saveDepartment');

            $this->assertDatabaseHas('departments', [
                'id' => $deptId,
                'description' => 'Nouveau descriptif de R&D',
            ]);

            // 5. Test Delete
            Livewire::test(ProHRDepartments::class)
                ->call('promptDelete', $deptId)
                ->call('deleteDepartment');

            $this->assertDatabaseMissing('departments', [
                'id' => $deptId,
            ]);
        } finally {
            DB::rollBack();
        }
    }
}
