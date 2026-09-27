<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::ALL as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // La migration ajoute uniquement les droits attendus aux rôles système.
        // Les rôles personnalisés et leurs choix existants restent intacts.
        Role::query()->where('name', 'Super administrateur')->where('guard_name', 'web')->first()?->givePermissionTo(PermissionCatalog::ALL);
        Role::query()->where('name', 'Gérant')->where('guard_name', 'web')->first()?->givePermissionTo(PermissionCatalog::MANAGER);
        Role::query()->where('name', 'Caissier')->where('guard_name', 'web')->first()?->givePermissionTo(PermissionCatalog::CASHIER);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Conserver les permissions évite de rendre des rôles invalides lors
        // d'un retour de version sur une base déjà utilisée en production.
    }
};
