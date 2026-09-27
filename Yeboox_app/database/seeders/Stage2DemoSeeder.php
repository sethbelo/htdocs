<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Establishment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class Stage2DemoSeeder extends Seeder
{
    public function run(): void
    {
        $establishment = Establishment::first();
        $mainBranch = $establishment->mainBranch;
        $admin = User::where('email', 'admin@yeboox.local')->first();

        // ── Customers ──────────────────────────────
        $customers = [
            ['first_name' => 'Patrick', 'last_name' => 'Lumumba', 'phone' => '+243 815 111 001', 'email' => 'patrick.l@email.cd', 'city' => 'Kinshasa', 'gender' => 'male', 'loyalty_points' => 3200, 'loyalty_level' => 'silver', 'total_purchases' => 850000, 'total_orders' => 45],
            ['first_name' => 'Grace', 'last_name' => 'Mbeki', 'phone' => '+243 815 111 002', 'email' => 'grace.m@email.cd', 'city' => 'Kinshasa', 'gender' => 'female', 'loyalty_points' => 8500, 'loyalty_level' => 'gold', 'total_purchases' => 2100000, 'total_orders' => 120],
            ['first_name' => 'Joseph', 'last_name' => 'Kabila', 'phone' => '+243 815 111 003', 'city' => 'Lubumbashi', 'gender' => 'male', 'loyalty_points' => 500, 'loyalty_level' => 'bronze', 'total_purchases' => 250000, 'total_orders' => 12],
            ['first_name' => 'Marie-Claire', 'last_name' => 'Tshisekedi', 'phone' => '+243 815 111 004', 'email' => 'mc.tshi@email.cd', 'city' => 'Kinshasa', 'gender' => 'female', 'company' => 'MC Consulting SARL', 'nif' => 'A9876543Z', 'loyalty_points' => 12000, 'loyalty_level' => 'platinum', 'total_purchases' => 5500000, 'total_orders' => 230],
            ['first_name' => 'Jean-Baptiste', 'last_name' => 'Mpiana', 'phone' => '+243 815 111 005', 'city' => 'Kinshasa', 'gender' => 'male', 'loyalty_points' => 150, 'loyalty_level' => 'standard'],
            ['first_name' => 'Fifi', 'last_name' => 'Masuka', 'phone' => '+243 815 111 006', 'email' => 'fifi.m@email.cd', 'city' => 'Kinshasa', 'gender' => 'female', 'loyalty_points' => 1800, 'loyalty_level' => 'bronze'],
            ['first_name' => 'Emmanuel', 'last_name' => 'Kolela', 'phone' => '+243 815 111 007', 'city' => 'Matadi', 'gender' => 'male', 'loyalty_points' => 600, 'loyalty_level' => 'bronze'],
            ['first_name' => 'Sylvie', 'last_name' => 'Ngandu', 'phone' => '+243 815 111 008', 'city' => 'Kinshasa', 'gender' => 'female', 'company' => 'Ngandu Traiteur', 'loyalty_points' => 4500, 'loyalty_level' => 'silver'],
        ];

        foreach ($customers as $c) {
            Customer::create(array_merge($c, [
                'establishment_id' => $establishment->id,
                'country' => 'CD',
                'is_active' => true,
                'created_by' => $admin->id,
            ]));
        }

        // ── Suppliers ──────────────────────────────
        $suppliers = [
            ['name' => 'Bralima SA', 'contact_name' => 'Jacques Bongo', 'phone' => '+243 815 200 001', 'email' => 'commandes@bralima.cd', 'city' => 'Kinshasa', 'notes' => 'Fournisseur principal boissons (Primus, Skol)', 'payment_terms_days' => 30],
            ['name' => 'Bracongo SA', 'contact_name' => 'Pierre Mutombo', 'phone' => '+243 815 200 002', 'city' => 'Kinshasa', 'notes' => 'Bières et boissons gazeuses', 'payment_terms_days' => 15],
            ['name' => 'Marché Zando — Mamie Jeanne', 'contact_name' => 'Mamie Jeanne', 'phone' => '+243 815 200 003', 'city' => 'Kinshasa', 'notes' => 'Légumes frais, poissons, viandes', 'payment_terms_days' => 0],
            ['name' => 'Congo Agri Distribution', 'contact_name' => 'Robert Kalala', 'phone' => '+243 815 200 004', 'email' => 'info@congoagri.cd', 'city' => 'Kinshasa', 'nif' => 'B5678901Y', 'rccm' => 'CD/KIN/RCCM/22-A-56789', 'notes' => 'Riz, farine, huile en gros', 'payment_terms_days' => 45],
            ['name' => 'Épicerie Fine Gombe', 'contact_name' => 'Sophie Laurent', 'phone' => '+243 815 200 005', 'city' => 'Kinshasa', 'notes' => 'Produits importés, fromages, vins', 'payment_terms_days' => 7],
        ];

        foreach ($suppliers as $s) {
            Supplier::create(array_merge($s, [
                'establishment_id' => $establishment->id,
                'country' => 'CD',
                'is_active' => true,
                'created_by' => $admin->id,
            ]));
        }

        // ── Warehouses ──────────────────────────────
        $mainWarehouse = Warehouse::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'name' => 'Dépôt Principal — Gombe',
            'address' => '123 Avenue de la Paix, Gombe',
            'is_default' => true,
            'is_active' => true,
        ]);

        Warehouse::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'name' => 'Réserve Cuisine',
            'is_default' => false,
            'is_active' => true,
        ]);

        // ── Stocks (initialize for all products) ──────────────────────────────
        $products = Product::where('establishment_id', $establishment->id)->get();
        foreach ($products as $product) {
            Stock::create([
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $product->id,
                'quantity' => rand(5, 200),
                'alert_quantity' => $product->alert_quantity,
            ]);
        }

        // ── Zones & Tables ──────────────────────────────
        $salle = Zone::create(['establishment_id' => $establishment->id, 'branch_id' => $mainBranch->id, 'name' => 'Salle Principale', 'color' => '#3B82F6', 'sort_order' => 1]);
        $terrasse = Zone::create(['establishment_id' => $establishment->id, 'branch_id' => $mainBranch->id, 'name' => 'Terrasse', 'color' => '#10B981', 'sort_order' => 2]);
        $vip = Zone::create(['establishment_id' => $establishment->id, 'branch_id' => $mainBranch->id, 'name' => 'Salle VIP', 'color' => '#F59E0B', 'sort_order' => 3]);
        $bar = Zone::create(['establishment_id' => $establishment->id, 'branch_id' => $mainBranch->id, 'name' => 'Bar', 'color' => '#8B5CF6', 'sort_order' => 4]);

        $tables = [
            // Salle Principale
            ['zone' => $salle, 'name' => 'Table 1', 'number' => '01', 'capacity' => 4, 'shape' => 'square', 'pos_x' => 50, 'pos_y' => 50],
            ['zone' => $salle, 'name' => 'Table 2', 'number' => '02', 'capacity' => 4, 'shape' => 'square', 'pos_x' => 200, 'pos_y' => 50],
            ['zone' => $salle, 'name' => 'Table 3', 'number' => '03', 'capacity' => 6, 'shape' => 'rectangle', 'pos_x' => 350, 'pos_y' => 50],
            ['zone' => $salle, 'name' => 'Table 4', 'number' => '04', 'capacity' => 2, 'shape' => 'round', 'pos_x' => 50, 'pos_y' => 200],
            ['zone' => $salle, 'name' => 'Table 5', 'number' => '05', 'capacity' => 4, 'shape' => 'square', 'pos_x' => 200, 'pos_y' => 200],
            ['zone' => $salle, 'name' => 'Table 6', 'number' => '06', 'capacity' => 8, 'shape' => 'rectangle', 'pos_x' => 350, 'pos_y' => 200],
            // Terrasse
            ['zone' => $terrasse, 'name' => 'Terrasse 1', 'number' => 'T1', 'capacity' => 4, 'shape' => 'round', 'pos_x' => 50, 'pos_y' => 50],
            ['zone' => $terrasse, 'name' => 'Terrasse 2', 'number' => 'T2', 'capacity' => 4, 'shape' => 'round', 'pos_x' => 200, 'pos_y' => 50],
            ['zone' => $terrasse, 'name' => 'Terrasse 3', 'number' => 'T3', 'capacity' => 6, 'shape' => 'round', 'pos_x' => 350, 'pos_y' => 50],
            // VIP
            ['zone' => $vip, 'name' => 'VIP 1', 'number' => 'V1', 'capacity' => 6, 'shape' => 'rectangle', 'pos_x' => 50, 'pos_y' => 50],
            ['zone' => $vip, 'name' => 'VIP 2', 'number' => 'V2', 'capacity' => 10, 'shape' => 'rectangle', 'pos_x' => 250, 'pos_y' => 50],
            // Bar
            ['zone' => $bar, 'name' => 'Bar 1', 'number' => 'B1', 'capacity' => 2, 'shape' => 'round', 'pos_x' => 50, 'pos_y' => 50],
            ['zone' => $bar, 'name' => 'Bar 2', 'number' => 'B2', 'capacity' => 2, 'shape' => 'round', 'pos_x' => 150, 'pos_y' => 50],
            ['zone' => $bar, 'name' => 'Bar 3', 'number' => 'B3', 'capacity' => 2, 'shape' => 'round', 'pos_x' => 250, 'pos_y' => 50],
        ];

        $statuses = ['free', 'free', 'free', 'occupied', 'free', 'reserved'];
        foreach ($tables as $i => $t) {
            Table::create([
                'establishment_id' => $establishment->id,
                'branch_id' => $mainBranch->id,
                'zone_id' => $t['zone']->id,
                'name' => $t['name'],
                'number' => $t['number'],
                'capacity' => $t['capacity'],
                'shape' => $t['shape'],
                'status' => $statuses[$i % count($statuses)],
                'pos_x' => $t['pos_x'],
                'pos_y' => $t['pos_y'],
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // ── Reservations ──────────────────────────────
        $customerGrace = Customer::where('first_name', 'Grace')->first();
        $table6 = Table::where('number', '06')->first();

        Reservation::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'table_id' => $table6?->id,
            'customer_id' => $customerGrace?->id,
            'guest_name' => 'Grace Mbeki',
            'guest_phone' => '+243 815 111 002',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '19:30',
            'duration_minutes' => 120,
            'party_size' => 6,
            'status' => 'confirmed',
            'special_requests' => 'Table près de la fenêtre, anniversaire',
            'confirmed_by' => $admin->id,
            'created_by' => $admin->id,
        ]);

        Reservation::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'guest_name' => 'M. Kalala',
            'guest_phone' => '+243 815 333 001',
            'reservation_date' => now()->addDays(2)->toDateString(),
            'reservation_time' => '12:00',
            'party_size' => 4,
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        Reservation::create([
            'establishment_id' => $establishment->id,
            'branch_id' => $mainBranch->id,
            'guest_name' => 'Groupe Corporate',
            'guest_phone' => '+243 815 333 002',
            'guest_email' => 'events@corp.cd',
            'reservation_date' => now()->addDays(3)->toDateString(),
            'reservation_time' => '20:00',
            'party_size' => 12,
            'status' => 'pending',
            'special_requests' => 'Menu fixe pour 12 personnes, budget 300.000 FC',
            'created_by' => $admin->id,
        ]);
    }
}
