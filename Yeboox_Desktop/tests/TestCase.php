<?php

namespace Tests;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests qui créent un rôle système retrouvent le même contrat que
        // l'installation réelle, où SystemSeeder lui attribue ses permissions.
        Role::created(function (Role $role): void {
            $permissions = match ($role->name) {
                'Super administrateur' => PermissionCatalog::ALL,
                'Gérant' => PermissionCatalog::MANAGER,
                'Caissier' => PermissionCatalog::CASHIER,
                default => [],
            };

            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            if ($permissions !== []) {
                $role->givePermissionTo($permissions);
            }
        });
    }
}
