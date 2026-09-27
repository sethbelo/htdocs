<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define Permissions ──────────────────────────────

        $permissions = [
            // Dashboard
            'dashboard.view',
            'dashboard.export',

            // Products
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'products.import', 'products.export',

            // Categories
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',

            // Variants
            'variants.view', 'variants.create', 'variants.edit', 'variants.delete',

            // Addons
            'addons.view', 'addons.create', 'addons.edit', 'addons.delete',

            // Units
            'units.view', 'units.create', 'units.edit', 'units.delete',

            // Stocks
            'stocks.view', 'stocks.adjust', 'stocks.transfer', 'stocks.count',
            'stocks.import', 'stocks.export',

            // Warehouses
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',

            // Suppliers
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',

            // Purchases
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete',
            'purchases.approve', 'purchases.receive',

            // Customers
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'customers.import', 'customers.export',

            // Loyalty
            'loyalty.view', 'loyalty.manage', 'loyalty.adjust_points',

            // POS
            'pos.access', 'pos.sell', 'pos.apply_discount', 'pos.void_item',
            'pos.void_order', 'pos.refund',

            // Orders
            'orders.view', 'orders.create', 'orders.edit', 'orders.cancel',

            // Tables
            'tables.view', 'tables.manage', 'tables.transfer',

            // Kitchen
            'kitchen.view', 'kitchen.manage',

            // Cash Registers
            'cash_registers.view', 'cash_registers.create', 'cash_registers.edit',
            'cash_registers.delete',

            // Cash Sessions
            'cash_sessions.open', 'cash_sessions.close', 'cash_sessions.validate',
            'cash_sessions.view_all',

            // Payments
            'payments.view', 'payments.create', 'payments.refund',

            // Invoices
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.print',
            'invoices.send', 'invoices.void',

            // Sales
            'sales.view', 'sales.view_all', 'sales.export',

            // Expenses
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            'expenses.approve',

            // Promotions
            'promotions.view', 'promotions.create', 'promotions.edit', 'promotions.delete',

            // Accounting
            'accounting.view', 'accounting.manage', 'accounting.close_period',
            'accounting.export',

            // Taxes
            'taxes.view', 'taxes.create', 'taxes.edit', 'taxes.delete',

            // Currencies
            'currencies.view', 'currencies.manage', 'exchange_rates.manage',

            // Reports
            'reports.view', 'reports.export', 'reports.schedule',

            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'users.assign_roles',

            // Roles & Permissions
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',

            // Establishments
            'establishments.view', 'establishments.create', 'establishments.edit',
            'establishments.delete',

            // Branches
            'branches.view', 'branches.create', 'branches.edit', 'branches.delete',

            // Settings
            'settings.view', 'settings.edit',

            // Audit
            'audit.view',

            // Sync
            'sync.manage', 'sync.view',

            // Backup
            'backup.create', 'backup.restore',

            // Printers & Devices
            'devices.view', 'devices.manage',

            // Floor Plan
            'floor_plan.view', 'floor_plan.edit',

            // Recipes
            'recipes.view', 'recipes.create', 'recipes.edit', 'recipes.delete',

            // Reservations
            'reservations.view', 'reservations.create', 'reservations.edit',
            'reservations.delete', 'reservations.import', 'reservations.export',

            // Appointments
            'appointments.view', 'appointments.create', 'appointments.edit',
            'appointments.delete', 'appointments.remind', 'appointments.import', 'appointments.export',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Create Roles ──────────────────────────────

        // Admin — full access
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Gérant — operational & supervisory access
        $gerant = Role::firstOrCreate(['name' => 'gerant', 'guard_name' => 'web']);
        $gerantPermissions = Permission::whereNotIn('name', [
            // Gérant cannot manage critical system settings
            'settings.edit',
            'establishments.create', 'establishments.edit', 'establishments.delete',
            'roles.create', 'roles.edit', 'roles.delete',
            'users.assign_roles',
            'backup.create', 'backup.restore',
            'sync.manage',
            'accounting.close_period',
        ])->pluck('name');
        $gerant->syncPermissions($gerantPermissions);

        // Vendeur — POS only access
        $vendeur = Role::firstOrCreate(['name' => 'vendeur', 'guard_name' => 'web']);
        $vendeur->syncPermissions([
            'pos.access', 'pos.sell', 'pos.apply_discount',
            'orders.view', 'orders.create', 'orders.edit',
            'tables.view', 'tables.transfer',
            'kitchen.view',
            'cash_sessions.open', 'cash_sessions.close',
            'payments.create',
            'invoices.view', 'invoices.print',
            'sales.view',
            'customers.view', 'customers.create',
            'products.view',
            'categories.view',
            'floor_plan.view',
            'reservations.view', 'reservations.create',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'appointments.remind',
        ]);
    }
}
