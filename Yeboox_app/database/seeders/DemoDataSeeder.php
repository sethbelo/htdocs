<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Establishment;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $cdf = Currency::where('code', 'CDF')->first();
        $usd = Currency::where('code', 'USD')->first();

        // ── Establishment: Restaurant ──────────────────────────────
        $restaurant = Establishment::create([
            'name' => 'Yeboox Restaurant & Bar',
            'type' => 'restaurant',
            'rccm' => 'CD/KIN/RCCM/24-B-12345',
            'id_nat' => '01-J3456-N78901',
            'nif' => 'A1234567Z',
            'address' => '123 Avenue de la Paix, Gombe',
            'city' => 'Kinshasa',
            'country' => 'CD',
            'phone' => '+243 812 345 678',
            'email' => 'contact@yeboox.local',
            'default_currency_id' => $cdf->id,
            'timezone' => 'Africa/Kinshasa',
            'fiscal_year_start' => '2026-01-01',
            'is_active' => true,
        ]);

        // ── Branches ──────────────────────────────
        $mainBranch = Branch::create([
            'establishment_id' => $restaurant->id,
            'name' => 'Succursale Gombe',
            'address' => '123 Avenue de la Paix, Gombe',
            'phone' => '+243 812 345 678',
            'is_main' => true,
            'is_active' => true,
        ]);

        $secondBranch = Branch::create([
            'establishment_id' => $restaurant->id,
            'name' => 'Succursale Lingwala',
            'address' => '45 Rue des Fleurs, Lingwala',
            'phone' => '+243 812 345 679',
            'is_main' => false,
            'is_active' => true,
        ]);

        // ── Exchange Rates ──────────────────────────────
        ExchangeRate::create([
            'establishment_id' => $restaurant->id,
            'from_currency_id' => $usd->id,
            'to_currency_id' => $cdf->id,
            'buy_rate' => 2800.00,
            'sell_rate' => 2850.00,
            'effective_date' => now()->toDateString(),
        ]);

        // ── Users ──────────────────────────────
        $admin = User::create([
            'name' => 'Administrateur Yeboox',
            'email' => 'admin@yeboox.local',
            'username' => 'admin',
            'password' => Hash::make('Yeboox@2026'),
            'establishment_id' => $restaurant->id,
            'branch_id' => $mainBranch->id,
            'pin_code' => Hash::make('1234'),
            'status' => 'active',
            'must_change_password' => true,
            'locale' => 'fr',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $gerant = User::create([
            'name' => 'Jean-Pierre Mukendi',
            'email' => 'gerant@yeboox.local',
            'username' => 'gerant',
            'password' => Hash::make('Yeboox@2026'),
            'establishment_id' => $restaurant->id,
            'branch_id' => $mainBranch->id,
            'pin_code' => Hash::make('5678'),
            'status' => 'active',
            'must_change_password' => true,
            'locale' => 'fr',
            'email_verified_at' => now(),
        ]);
        $gerant->assignRole('gerant');

        $vendeur = User::create([
            'name' => 'Marie Kabongo',
            'email' => 'vendeur@yeboox.local',
            'username' => 'vendeur',
            'password' => Hash::make('Yeboox@2026'),
            'establishment_id' => $restaurant->id,
            'branch_id' => $mainBranch->id,
            'pin_code' => Hash::make('9012'),
            'status' => 'active',
            'must_change_password' => true,
            'locale' => 'fr',
            'email_verified_at' => now(),
        ]);
        $vendeur->assignRole('vendeur');

        // ── Taxes ──────────────────────────────
        Tax::create([
            'establishment_id' => $restaurant->id,
            'name' => 'TVA 16%',
            'rate' => 16.0000,
            'type' => 'percentage',
            'is_included' => false,
            'applies_to' => 'all',
            'is_active' => true,
        ]);

        Tax::create([
            'establishment_id' => $restaurant->id,
            'name' => 'Service 10%',
            'rate' => 10.0000,
            'type' => 'percentage',
            'is_included' => true,
            'applies_to' => 'all',
            'is_active' => true,
        ]);

        // ── Units ──────────────────────────────
        $piece = Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Pièce', 'abbreviation' => 'pcs']);
        $litre = Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Litre', 'abbreviation' => 'L']);
        $kg = Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Kilogramme', 'abbreviation' => 'kg']);
        Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Gramme', 'abbreviation' => 'g', 'base_unit_id' => $kg->id, 'conversion_factor' => 0.001]);
        Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Centilitre', 'abbreviation' => 'cl', 'base_unit_id' => $litre->id, 'conversion_factor' => 0.01]);
        Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Bouteille', 'abbreviation' => 'btl']);
        Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Verre', 'abbreviation' => 'verre']);
        Unit::create(['establishment_id' => $restaurant->id, 'name' => 'Portion', 'abbreviation' => 'port']);

        // ── Categories ──────────────────────────────
        $categories = [
            ['name' => 'Entrées', 'color' => '#10B981', 'icon' => 'leaf', 'slug' => 'entrees'],
            ['name' => 'Plats Principaux', 'color' => '#F59E0B', 'icon' => 'fire', 'slug' => 'plats-principaux'],
            ['name' => 'Grillades', 'color' => '#EF4444', 'icon' => 'fire', 'slug' => 'grillades'],
            ['name' => 'Poissons', 'color' => '#3B82F6', 'icon' => 'sparkles', 'slug' => 'poissons'],
            ['name' => 'Accompagnements', 'color' => '#8B5CF6', 'icon' => 'plus-circle', 'slug' => 'accompagnements'],
            ['name' => 'Desserts', 'color' => '#EC4899', 'icon' => 'cake', 'slug' => 'desserts'],
            ['name' => 'Boissons Chaudes', 'color' => '#92400E', 'icon' => 'cup-soda', 'slug' => 'boissons-chaudes'],
            ['name' => 'Boissons Froides', 'color' => '#06B6D4', 'icon' => 'beaker', 'slug' => 'boissons-froides'],
            ['name' => 'Bières', 'color' => '#D97706', 'icon' => 'beaker', 'slug' => 'bieres'],
            ['name' => 'Cocktails', 'color' => '#7C3AED', 'icon' => 'sparkles', 'slug' => 'cocktails'],
            ['name' => 'Vins', 'color' => '#BE123C', 'icon' => 'sparkles', 'slug' => 'vins'],
            ['name' => 'Snacks', 'color' => '#EA580C', 'icon' => 'squares-2x2', 'slug' => 'snacks'],
        ];

        $categoryModels = [];
        foreach ($categories as $i => $cat) {
            $categoryModels[$cat['slug']] = Category::create([
                'establishment_id' => $restaurant->id,
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'color' => $cat['color'],
                'icon' => $cat['icon'],
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // ── Products (30 items) ──────────────────────────────
        $products = [
            // Entrées
            ['name' => 'Salade César', 'category' => 'entrees', 'price' => 8500, 'cost' => 2500],
            ['name' => 'Soupe du jour', 'category' => 'entrees', 'price' => 6000, 'cost' => 1500],
            ['name' => 'Samossa (x5)', 'category' => 'entrees', 'price' => 5000, 'cost' => 1200],

            // Plats principaux
            ['name' => 'Poulet Braisé', 'category' => 'plats-principaux', 'price' => 15000, 'cost' => 5000],
            ['name' => 'Riz aux légumes', 'category' => 'plats-principaux', 'price' => 8000, 'cost' => 2000],
            ['name' => 'Fufu & Pondu', 'category' => 'plats-principaux', 'price' => 12000, 'cost' => 3500],
            ['name' => 'Spaghetti Bolognaise', 'category' => 'plats-principaux', 'price' => 10000, 'cost' => 3000],

            // Grillades
            ['name' => 'Brochettes de Bœuf (x6)', 'category' => 'grillades', 'price' => 18000, 'cost' => 7000],
            ['name' => 'Côtelettes d\'Agneau', 'category' => 'grillades', 'price' => 25000, 'cost' => 12000],
            ['name' => 'Poulet Grillé Entier', 'category' => 'grillades', 'price' => 22000, 'cost' => 8000],

            // Poissons
            ['name' => 'Capitaine Grillé', 'category' => 'poissons', 'price' => 20000, 'cost' => 8000],
            ['name' => 'Tilapia Frit', 'category' => 'poissons', 'price' => 15000, 'cost' => 5000],

            // Accompagnements
            ['name' => 'Frites', 'category' => 'accompagnements', 'price' => 4000, 'cost' => 1000],
            ['name' => 'Riz Nature', 'category' => 'accompagnements', 'price' => 3000, 'cost' => 800],
            ['name' => 'Banane Plantain', 'category' => 'accompagnements', 'price' => 3500, 'cost' => 900],

            // Desserts
            ['name' => 'Gâteau au Chocolat', 'category' => 'desserts', 'price' => 7000, 'cost' => 2000],
            ['name' => 'Crème Brûlée', 'category' => 'desserts', 'price' => 6500, 'cost' => 1800],

            // Boissons Chaudes
            ['name' => 'Café Espresso', 'category' => 'boissons-chaudes', 'price' => 3000, 'cost' => 500],
            ['name' => 'Thé Vert', 'category' => 'boissons-chaudes', 'price' => 2500, 'cost' => 400],

            // Boissons Froides
            ['name' => 'Coca-Cola 33cl', 'category' => 'boissons-froides', 'price' => 2500, 'cost' => 1000],
            ['name' => 'Jus d\'Orange Frais', 'category' => 'boissons-froides', 'price' => 4000, 'cost' => 1200],
            ['name' => 'Eau Minérale 50cl', 'category' => 'boissons-froides', 'price' => 1500, 'cost' => 500],

            // Bières
            ['name' => 'Primus 65cl', 'category' => 'bieres', 'price' => 3500, 'cost' => 1500],
            ['name' => 'Skol 50cl', 'category' => 'bieres', 'price' => 3000, 'cost' => 1200],
            ['name' => 'Heineken 33cl', 'category' => 'bieres', 'price' => 4500, 'cost' => 2000],

            // Cocktails
            ['name' => 'Mojito', 'category' => 'cocktails', 'price' => 8000, 'cost' => 2500],
            ['name' => 'Piña Colada', 'category' => 'cocktails', 'price' => 9000, 'cost' => 3000],
            ['name' => 'Caipirinha', 'category' => 'cocktails', 'price' => 8500, 'cost' => 2800],

            // Vins
            ['name' => 'Vin Rouge (Verre)', 'category' => 'vins', 'price' => 7000, 'cost' => 3000],

            // Snacks
            ['name' => 'Sandwich Club', 'category' => 'snacks', 'price' => 7500, 'cost' => 2500],
        ];

        foreach ($products as $i => $p) {
            Product::create([
                'establishment_id' => $restaurant->id,
                'category_id' => $categoryModels[$p['category']]->id,
                'name' => $p['name'],
                'slug' => \Str::slug($p['name']),
                'selling_price' => $p['price'],
                'cost_price' => $p['cost'],
                'type' => 'simple',
                'unit_id' => $piece->id,
                'is_available' => true,
                'is_active' => true,
                'sort_order' => $i,
                'created_by' => $admin->id,
            ]);
        }
    }
}
