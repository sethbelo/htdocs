<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class LoginModesAndSpacesTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_cashier_logs_in_with_pin_and_lands_on_the_point_of_sale(): void
    {
        $cashier = User::factory()->withRole('caissier', $this->shop)->withPin('4321')->create(['username' => 'aline', 'login_mode' => 'pin']);

        Livewire::test(Login::class)->call('setMode', 'password')->set('identifier', 'aline')->set('password', 'password')->call('login')
            ->assertHasErrors('identifier');
        $this->assertGuest();

        Livewire::test(Login::class)->call('setMode', 'pin')->set('username', 'aline')->set('pin', '0000')->call('login')->assertHasErrors('username');

        Livewire::test(Login::class)->call('setMode', 'pin')->set('username', 'aline')->set('pin', '4321')->call('login')
            ->assertRedirect(route('pos'));
        $this->assertAuthenticatedAs($cashier);
        $this->assertDatabaseHas('login_histories', ['user_id' => $cashier->id, 'succeeded' => true, 'method' => 'pin']);
    }

    public function test_storekeeper_logs_in_with_badge_and_lands_on_entries_and_exits(): void
    {
        User::factory()->withRole('magasinier')->withPin('2468')->create(['login_mode' => 'badge', 'badge_code' => 'BADGE-042']);

        Livewire::test(Login::class)->call('setMode', 'badge')->set('badge', 'BADGE-042')->set('pin', '2468')->call('login')
            ->assertRedirect(route('inventory.operations'));
    }

    public function test_pin_users_are_not_forced_to_change_a_password(): void
    {
        $cashier = User::factory()->withRole('caissier', $this->shop)->create(['login_mode' => 'pin', 'must_change_password' => true]);

        $this->actingAs($cashier)->get(route('pos'))->assertOk();
    }

    public function test_each_role_is_redirected_to_its_own_space(): void
    {
        $expectations = [
            'directeur-general' => 'dashboard',
            'graphiste' => 'design.board',
            'imprimeur' => 'production.board',
            'comptable' => 'accounting.index',
            'livreur' => 'deliveries.index',
            'responsable-rh' => 'hr.employees',
            'super-admin' => 'settings.users',
        ];

        foreach ($expectations as $role => $route) {
            $this->actingAs($this->userWithRole($role))->get(route('home'))->assertRedirect(route($route));
        }
    }

    public function test_only_the_director_general_sees_every_feature(): void
    {
        $director = $this->userWithRole('directeur-general', $this->shop);
        $allItems = collect(Navigation::groups())->flatMap(fn (array $group) => $group['items'])->count();
        $this->assertSame($allItems + 1, collect(Navigation::for($director))->flatMap(fn (array $group) => $group['items'])->count());
        $this->assertTrue($director->hasConsolidatedView());

        foreach (['super-admin', 'directeur', 'auditeur', 'responsable-financier', 'caissier'] as $role) {
            $user = $this->userWithRole($role);
            $this->assertLessThan($allItems, collect(Navigation::for($user))->flatMap(fn (array $group) => $group['items'])->count(), $role);
        }

        $cashier = $this->userWithRole('caissier');
        $this->actingAs($cashier)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($cashier)->get(route('hr.employees'))->assertForbidden();

        $admin = $this->userWithRole('super-admin');
        $this->actingAs($admin)->get(route('orders.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('settings.users'))->assertOk();
    }
}
