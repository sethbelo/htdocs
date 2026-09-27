<?php

namespace Tests\Feature;

use App\Livewire\Pos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_role_name_without_permission_does_not_grant_module_access(): void
    {
        $role = Role::create(['name' => 'Responsable limité']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('inventory'))->assertForbidden();
    }

    public function test_a_custom_role_can_access_only_the_explicitly_granted_module(): void
    {
        $role = Role::create(['name' => 'Magasinier']);
        $role->givePermissionTo(Permission::findOrCreate('manage inventory'));
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('inventory'))->assertOk();
        $this->actingAs($user)->get(route('accounting'))->assertForbidden();
    }

    public function test_register_actions_require_the_dedicated_permission_server_side(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage POS');

        Livewire::actingAs($user)->test(Pos::class)
            ->set('openingBalance', 0)
            ->call('openRegister')
            ->assertForbidden();
    }

    public function test_super_administrator_is_authorized_through_the_global_gate(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::create(['name' => 'Super administrateur']));

        $this->assertTrue($user->can('permission inexistante réservée au super administrateur'));
        $this->actingAs($user)->get(route('admin'))->assertOk();
    }
}
