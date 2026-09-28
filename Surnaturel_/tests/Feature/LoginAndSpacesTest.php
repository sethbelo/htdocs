<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class LoginAndSpacesTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_users_log_in_with_their_username_or_email_and_password(): void
    {
        $cashier = User::factory()->withRole('caissier', $this->shop)->create(['username' => 'aline', 'email' => 'aline@surnaturel.cd']);

        Livewire::test(Login::class)->set('identifier', 'aline')->set('password', 'mauvais')->call('login')->assertHasErrors('identifier');
        $this->assertGuest();

        Livewire::test(Login::class)->set('identifier', 'ALINE@surnaturel.cd')->set('password', 'password')->call('login')->assertRedirect(route('pos'));
        $this->assertAuthenticatedAs($cashier);
        $this->assertDatabaseHas('login_histories', ['user_id' => $cashier->id, 'succeeded' => true, 'method' => 'password']);

        auth()->logout();
        Livewire::test(Login::class)->set('identifier', 'aline')->set('password', 'password')->call('login')->assertRedirect(route('pos'));
    }

    public function test_storekeeper_lands_on_entries_and_exits_and_inactive_accounts_are_refused(): void
    {
        User::factory()->withRole('magasinier')->create(['username' => 'stock.lim']);
        Livewire::test(Login::class)->set('identifier', 'stock.lim')->set('password', 'password')->call('login')->assertRedirect(route('inventory.operations'));
        auth()->logout();

        User::factory()->withRole('comptable')->create(['username' => 'ancien', 'is_active' => false]);
        Livewire::test(Login::class)->set('identifier', 'ancien')->set('password', 'password')->call('login')->assertHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        User::factory()->withRole('comptable')->create(['username' => 'cible']);

        foreach (range(1, 5) as $attempt) {
            Livewire::test(Login::class)->set('identifier', 'cible')->set('password', 'faux-'.$attempt)->call('login');
        }

        Livewire::test(Login::class)->set('identifier', 'cible')->set('password', 'password')->call('login')->assertHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_each_role_is_redirected_to_its_own_space(): void
    {
        $expectations = [
            'directeur-general' => 'launcher',
            'graphiste' => 'design.board',
            'imprimeur' => 'production.board',
            'comptable' => 'accounting.index',
            'livreur' => 'deliveries.index',
            'responsable-rh' => 'hr.employees',
            'agent-marketing' => 'followups.index',
            'responsable-entrepot' => 'inventory.report',
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

    public function test_the_full_screen_launcher_only_shows_the_applications_of_each_role(): void
    {
        $all = collect(Navigation::groups())->flatMap(fn (array $group) => $group['items']);

        foreach (['caissier', 'magasinier', 'graphiste', 'comptable', 'agent-marketing', 'directeur-general'] as $role) {
            $user = $this->userWithRole($role);
            $response = $this->actingAs($user)->get(route('launcher'))->assertOk();

            foreach ($all as $item) {
                $link = 'href="'.route($item['route']).'"';
                $user->can($item['permission'])
                    ? $response->assertSee($link, false)
                    : $response->assertDontSee($link, false);
            }
        }

        $cashier = $this->userWithRole('caissier');
        $this->actingAs($cashier)->get(route('launcher'))->assertSee('Point de vente')->assertDontSee('Comptabilité')->assertDontSee('Visa des paies');
    }
}
