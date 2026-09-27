<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Dashboard\Index;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_sales_data_and_updates_its_period(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        $expectedRevenue = (float) Order::query()
            ->where('establishment_id', $admin->establishment_id)
            ->where('is_paid', true)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('created_at', [today(), now()])
            ->sum('total');

        Livewire::test(Index::class)
            ->assertSet('selectedRevenue', $expectedRevenue)
            ->assertSet('periodLabel', 'Aujourd’hui')
            ->assertSet('salesChart', fn (array $chart) => count($chart) === 8)
            ->set('period', 'week')
            ->assertSet('periodLabel', 'Cette semaine')
            ->assertSet('salesChart', fn (array $chart) => count($chart) === 7)
            ->call('refreshDashboard')
            ->assertDispatched('dashboard-refreshed');
    }

    public function test_dashboard_preview_actions_link_to_real_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.products.create'), false)
            ->assertSee(route('admin.customers.create'), false)
            ->assertSee(route('admin.reports.dashboard'), false)
            ->assertSee(route('admin.stocks.index'), false)
            ->assertSee(route('admin.settings.index'), false)
            ->assertDontSee('href="#"', false);
    }

    public function test_authenticated_dashboard_session_can_use_offline_sync_api(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)
            ->getJson(route('api.sync.status'))
            ->assertOk()
            ->assertJsonStructure(['online', 'server_time']);
    }
}
