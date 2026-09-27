<?php

namespace Database\Seeders;

use App\Support\Modules;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions générées depuis config/erp.php (modules × view/manage + actions)
 * et rôles métier SOGEREF. Relançable sans risque (idempotent).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = Modules::permissions();
        foreach ($all as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $modules = array_keys(Modules::all());
        foreach (config('erp.roles') as $name => $definition) {
            $grants = $definition === '*' ? $all : $this->expand($definition, $modules);
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->syncPermissions($grants);
        }
    }

    private function expand(array $definition, array $modules): array
    {
        // « * » couvre tous les modules métier ; le Master système reste réservé
        // aux rôles qui le reçoivent explicitement (extra : system.view…).
        $business = array_values(array_diff($modules, ['system']));
        $view = ($definition['view'] ?? []) === '*' ? $business : ($definition['view'] ?? []);
        $manage = ($definition['manage'] ?? []) === '*' ? $business : ($definition['manage'] ?? []);
        $view = array_unique(array_merge($view, $manage, ['dashboard']));

        return array_values(array_unique(array_merge(
            array_map(fn ($m) => "{$m}.view", $view),
            array_map(fn ($m) => "{$m}.manage", $manage),
            $definition['extra'] ?? [],
        )));
    }
}
