<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'cash' => ['name' => 'Cash', 'icon' => 'banknotes', 'syscohada_code' => '5711'],
            'bank' => ['name' => 'Banque', 'icon' => 'building-library', 'syscohada_code' => '5211'],
            'mobile_money' => ['name' => 'Mobile Money', 'icon' => 'device-phone-mobile', 'syscohada_code' => '5218'],
            'yepay' => ['name' => 'YePay', 'icon' => 'sparkles', 'syscohada_code' => '5218'],
            'points' => ['name' => 'Points fidélité', 'icon' => 'star', 'syscohada_code' => '467'],
        ] as $code => $attributes) {
            PaymentMethod::updateOrCreate(['code' => $code], [...$attributes, 'is_active' => true]);
        }

        $permissions = PermissionCatalog::ALL;
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::findOrCreate('Super administrateur');
        $manager = Role::findOrCreate('Gérant');
        $cashier = Role::findOrCreate('Caissier');
        $superAdmin->syncPermissions(Permission::all());
        $manager->syncPermissions(PermissionCatalog::MANAGER);
        $cashier->syncPermissions(PermissionCatalog::CASHIER);
    }
}
