<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_main_application_page_renders_without_a_server_error(): void
    {
        $administrator = User::factory()->create(['email_verified_at' => now()]);
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));

        $this->actingAs($administrator);

        foreach ([
            'dashboard',
            'pos',
            'sales',
            'appointments',
            'customers',
            'inventory',
            'invoices',
            'accounting',
            'expenses',
            'purchases',
            'disbursements',
            'equity',
            'planning',
            'reports',
            'admin',
            'profile.show',
        ] as $routeName) {
            $response = $this->get(route($routeName));

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "La route [{$routeName}] a retourné une erreur serveur.",
            );
        }
    }
}
