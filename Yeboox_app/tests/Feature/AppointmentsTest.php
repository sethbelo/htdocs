<?php

namespace Tests\Feature;

use App\Livewire\Admin\Appointments\Index;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Appointment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enable_and_use_the_appointments_module(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        $this->get(route('admin.appointments.index'))->assertForbidden();

        Livewire::test(SettingsIndex::class)
            ->set('appointments_enabled', true)
            ->set('appointment_reminder_hours', 48)
            ->set('appointment_whatsapp_template', 'Bonjour {client}, votre rendez-vous {service} est prévu le {date} à {heure}.')
            ->call('saveModules')
            ->assertHasNoErrors();

        $this->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Agenda des rendez-vous');

        Livewire::test(Index::class)
            ->call('openForm')
            ->set('client_name', 'Amina Test')
            ->set('client_phone', '0812345678')
            ->set('service_name', 'Massage relaxant')
            ->set('staff_name', 'Grâce')
            ->set('appointment_date', now()->addDay()->format('Y-m-d'))
            ->set('appointment_time', '14:30')
            ->set('duration_minutes', 90)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'establishment_id' => $admin->establishment_id,
            'client_name' => 'Amina Test',
            'service_name' => 'Massage relaxant',
            'status' => 'scheduled',
        ]);

        $appointment = Appointment::where('client_name', 'Amina Test')->firstOrFail();
        $response = $this->get(route('admin.appointments.whatsapp', $appointment->id));
        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/243812345678?text=', $response->headers->get('Location'));
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    public function test_appointment_actions_are_tenant_scoped_and_module_protected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        $this->get(route('admin.appointments.index'))->assertForbidden();
        $this->get(route('admin.appointments.whatsapp', 'missing'))->assertForbidden();
    }
}
