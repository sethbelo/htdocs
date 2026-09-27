<?php

namespace Database\Seeders;

use App\Services\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(PermissionRegistry $registry): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $now = now();
        Permission::query()->insertOrIgnore(array_map(fn (string $name): array => [
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ], $registry->all()));

        foreach (config('erp.roles') as $name => $definition) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($registry->resolve($definition['permissions']));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
