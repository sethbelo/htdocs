<?php

namespace Tests\Feature;

use App\Livewire\Customers;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_filters_views_and_modals_are_functional(): void
    {
        $user = User::factory()->create();
        $subscribed = Customer::create(['name' => 'Client Abonné', 'phone' => '+243810000001', 'points' => 300, 'is_active' => true]);
        $withoutSubscription = Customer::create(['name' => 'Client Libre', 'phone' => '+243810000002', 'points' => 20, 'is_active' => true]);
        Subscription::create([
            'customer_id' => $subscribed->id, 'plan_name' => 'VIP', 'price' => 100,
            'points_granted' => 100, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'status' => 'active',
        ]);

        Livewire::actingAs($user)->test(Customers::class)
            ->assertSee('Client Abonné')
            ->assertSee('Client Libre')
            ->set('filterStatus', 'subscribed')
            ->assertSee('Client Abonné')
            ->assertDontSee('Client Libre')
            ->set('viewMode', 'list')
            ->assertSee('Achats')
            ->call('openCreateCustomerModal')
            ->assertSet('showCreateCustomerModal', true)
            ->call('viewCustomer', $subscribed->id)
            ->assertSet('showCustomerProfileModal', true)
            ->call('askArchiveCustomer', $withoutSubscription->id)
            ->assertSet('showArchiveModal', true);
    }
}
