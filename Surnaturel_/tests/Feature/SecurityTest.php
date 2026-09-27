<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Hr\Employees;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_login_accepts_email_or_username_and_records_history(): void
    {
        $user = User::factory()->withRole('commercial', $this->headquarters)->create(['username' => 'jdoe', 'email' => 'jdoe@example.test']);

        Livewire::test(Login::class)->set('identifier', 'jdoe')->set('password', 'password')->call('login')->assertRedirect(route('orders.index'));
        $this->assertAuthenticatedAs($user);
        auth()->logout();

        Livewire::test(Login::class)->set('identifier', 'jdoe@example.test')->set('password', 'wrong')->call('login')->assertHasErrors('identifier');
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'succeeded' => true]);
        $this->assertDatabaseHas('login_histories', ['identifier' => 'jdoe@example.test', 'succeeded' => false]);
    }

    public function test_forced_password_change_blocks_the_application(): void
    {
        $user = User::factory()->withRole('commercial', $this->headquarters)->create(['must_change_password' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.change'));
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $user = User::factory()->withRole('commercial', $this->headquarters)->create(['is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_module_permissions_are_enforced(): void
    {
        $cashier = $this->userWithRole('caissier');

        $this->actingAs($cashier)->get(route('cash.checkout'))->assertOk();
        $this->actingAs($cashier)->get(route('accounting.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('hr.payroll'))->assertForbidden();
        $this->actingAs($cashier)->get(route('settings.roles'))->assertForbidden();
    }

    public function test_users_only_see_orders_of_their_sites(): void
    {
        $order = $this->createOrder();
        $shopManager = $this->userWithRole('responsable-commercial', $this->shop);
        $director = $this->userWithRole('directeur-general', $this->shop);

        $this->actingAs($shopManager)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($shopManager)->get(route('orders.index'))->assertOk()->assertDontSee($order->number);
        $this->actingAs($director)->get(route('orders.show', $order))->assertOk();
    }

    public function test_sensitive_hr_data_is_encrypted_and_restricted(): void
    {
        $employee = Employee::factory()->create(['site_id' => $this->headquarters->id, 'base_salary' => '777', 'bank_account' => 'CD-0001']);

        $raw = \DB::table('employees')->where('id', $employee->id)->first();
        $this->assertNotSame('777', $raw->base_salary);
        $this->assertArrayNotHasKey('base_salary', $employee->toArray());

        $commercialManager = $this->userWithRole('responsable-administratif');
        $this->actingAs($commercialManager);
        Livewire::test(Employees::class)->call('show', $employee->id)->assertDontSee('CD-0001');

        $hr = $this->userWithRole('responsable-rh');
        $this->actingAs($hr);
        Livewire::test(Employees::class)->call('show', $employee->id)->assertSee('CD-0001');
    }

    public function test_private_files_require_authorization(): void
    {
        $order = $this->createOrder();
        $file = $order->files()->create(['disk' => 'local', 'path' => 'x.pdf', 'original_name' => 'x.pdf', 'size' => 1]);

        $this->get(route('files.order', $file))->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole('commercial', $this->shop))->get(route('files.order', $file))->assertForbidden();
    }
}
