<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Filament\Pages\ProHRDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class HRIdCardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
    }

    /**
     * Test ID Card tab switching, teacher selection and color customization.
     */
    public function test_id_card_generation_tab_and_customization(): void
    {
        DB::beginTransaction();

        try {
            // 1. Setup Auth
            $user = User::create([
                'name' => 'HR Manager ID Cards',
                'email' => 'hr.manager.idcards@example.com',
                'password' => bcrypt('password'),
            ]);

            $role = Role::firstOrCreate(['name' => 'RH']);
            $user->assignRole($role);

            $this->actingAs($user);

            // 2. Setup a teacher
            $teacher = Teacher::create([
                'first_name' => 'Marc',
                'last_name' => 'Kabila',
                'email' => 'marc.kabila@example.com',
                'phone' => '0812345678',
                'status' => 'Actif',
                'folder_number' => 'DOS-2026-MCBKBL',
            ]);

            // 3. Test Livewire component
            Livewire::test(ProHRDirectory::class)
                ->assertSet('currentTab', 'annuaire')
                // Switch tab
                ->set('currentTab', 'idcard')
                ->assertSet('currentTab', 'idcard')
                // Set selected personnel ID
                ->set('selectedPersonnelForCardId', $teacher->id)
                ->assertSet('selectedPersonnelForCardId', $teacher->id)
                // Test theme switching
                ->assertSet('cardColorTheme', 'tan')
                ->set('cardColorTheme', 'navy')
                ->assertSet('cardColorTheme', 'navy')
                ->set('cardColorTheme', 'emerald')
                ->assertSet('cardColorTheme', 'emerald')
                ->set('cardColorTheme', 'royal')
                ->assertSet('cardColorTheme', 'royal');

        } finally {
            DB::rollBack();
        }
    }
}
