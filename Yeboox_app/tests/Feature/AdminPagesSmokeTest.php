<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_main_admin_pages_render_for_an_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        Setting::create([
            'establishment_id' => $admin->establishment_id,
            'group' => 'modules',
            'key' => 'reservations_enabled',
            'value' => '1',
            'type' => 'boolean',
        ]);
        Setting::create([
            'establishment_id' => $admin->establishment_id,
            'group' => 'modules',
            'key' => 'appointments_enabled',
            'value' => '1',
            'type' => 'boolean',
        ]);

        $routes = [
            'admin.dashboard',
            'admin.products.index',
            'admin.products.create',
            'admin.categories.index',
            'admin.categories.create',
            'admin.customers.index',
            'admin.customers.create',
            'admin.suppliers.index',
            'admin.suppliers.create',
            'admin.users.index',
            'admin.settings.index',
            'admin.stocks.index',
            'admin.tables.index',
            'admin.reservations.index',
            'admin.appointments.index',
            'admin.orders.index',
            'admin.cash-sessions.index',
            'admin.accounting.chart',
            'admin.accounting.journal',
            'admin.accounting.ledger',
            'admin.accounting.expenses',
            'admin.reports.dashboard',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk();
        }
    }
}
