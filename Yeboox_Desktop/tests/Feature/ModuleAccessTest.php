<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_cannot_open_management_modules(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole(Role::create(['name' => 'Caissier']));

        $this->actingAs($cashier)->get(route('inventory'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin'))->assertForbidden();
    }
}
