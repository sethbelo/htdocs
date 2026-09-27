<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Jeu de données répétable réservé à la vérification du client Flutter POS.
 *
 * updateOrCreate permet de relancer ce seeder après une reconstruction de l'APK
 * sans multiplier les articles, les clients ou le compte de démonstration.
 */
class MobilePosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SystemSeeder::class);

        $cashier = User::updateOrCreate(
            ['email' => 'mobile.pos@yeboox.test'],
            [
                'name' => 'Caissier Mobile Démo',
                'password' => Hash::make('YebooxTest2026!'),
                'email_verified_at' => now(),
            ],
        );
        $cashier->syncRoles([Role::findOrCreate('Caissier')]);

        foreach ([
            ['name' => 'Boissons', 'code' => 'DEMO-BOIS'],
            ['name' => 'Restauration', 'code' => 'DEMO-REST'],
            ['name' => 'Desserts', 'code' => 'DEMO-DESS'],
            ['name' => 'Services', 'code' => 'DEMO-SERV'],
        ] as $category) {
            ProductCategory::updateOrCreate(
                ['name' => $category['name']],
                ['code' => $category['code'], 'is_active' => true],
            );
        }

        $unit = UnitOfMeasure::query()->where('symbol', 'u')->first();
        $products = [
            ['DEMO-BOI-001', 'Café espresso', 'Boissons', 2.50, 0.65, 80, 15],
            ['DEMO-BOI-002', 'Cappuccino', 'Boissons', 4.00, 1.10, 55, 10],
            ['DEMO-BOI-003', 'Eau minérale 500 ml', 'Boissons', 1.50, 0.40, 120, 20],
            ['DEMO-BOI-004', 'Jus de mangue frais', 'Boissons', 3.50, 1.20, 38, 8],
            ['DEMO-BOI-005', 'Limonade maison', 'Boissons', 3.00, 0.90, 24, 6],
            ['DEMO-BOI-006', 'Boisson gazeuse', 'Boissons', 2.00, 0.75, 0, 10],
            ['DEMO-RES-001', 'Burger Yeboox', 'Restauration', 8.50, 3.20, 35, 8],
            ['DEMO-RES-002', 'Pizza poulet', 'Restauration', 12.00, 4.80, 26, 5],
            ['DEMO-RES-003', 'Poulet grillé & frites', 'Restauration', 10.00, 4.00, 30, 6],
            ['DEMO-RES-004', 'Frites croustillantes', 'Restauration', 3.00, 0.90, 60, 12],
            ['DEMO-RES-005', 'Sandwich club', 'Restauration', 6.50, 2.10, 42, 8],
            ['DEMO-RES-006', 'Salade fraîche', 'Restauration', 5.50, 1.80, 28, 5],
            ['DEMO-RES-007', 'Brochettes de bœuf', 'Restauration', 9.00, 3.60, 22, 5],
            ['DEMO-RES-008', 'Poisson braisé', 'Restauration', 14.00, 6.20, 18, 4],
            ['DEMO-DES-001', 'Gâteau au chocolat', 'Desserts', 4.50, 1.45, 20, 5],
            ['DEMO-DES-002', 'Glace vanille', 'Desserts', 3.50, 1.10, 32, 6],
            ['DEMO-DES-003', 'Salade de fruits', 'Desserts', 4.00, 1.30, 16, 4],
            ['DEMO-DES-004', 'Crêpe au miel', 'Desserts', 4.25, 1.15, 25, 5],
        ];

        foreach ($products as [$sku, $name, $category, $price, $cost, $stock, $alert]) {
            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'category' => $category,
                    'unit_of_measure_id' => $unit?->id,
                    'type' => 'product',
                    'price' => $price,
                    'sales_tax_rate' => 16,
                    'tax_included' => true,
                    'cost' => $cost,
                    'stock_qty' => $stock,
                    'alert_qty' => $alert,
                    'is_active' => true,
                    'internal_notes' => 'Article de démonstration pour Yeboox K POS.',
                ],
            );
        }

        foreach ([
            ['DEMO-SRV-001', 'Livraison locale', 2.00],
            ['DEMO-SRV-002', 'Réservation espace VIP', 15.00],
        ] as [$sku, $name, $price]) {
            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'category' => 'Services',
                    'unit_of_measure_id' => $unit?->id,
                    'type' => 'service',
                    'price' => $price,
                    'sales_tax_rate' => 16,
                    'tax_included' => true,
                    'cost' => 0,
                    'stock_qty' => 0,
                    'alert_qty' => 0,
                    'is_active' => true,
                    'internal_notes' => 'Service de démonstration pour Yeboox K POS.',
                ],
            );
        }

        foreach ([
            ['Client Comptoir', '+243810000101', 'comptoir@yeboox.test', 0],
            ['Amina Kalala', '+243810000102', 'amina@yeboox.test', 120],
            ['Patrick Ilunga', '+243810000103', 'patrick@yeboox.test', 75],
            ['Grâce Mulumba', '+243810000104', 'grace.client@yeboox.test', 240],
            ['Jean Mbuyi', '+243810000105', 'jean@yeboox.test', 35],
            ['Sarah Kanku', '+243810000106', 'sarah.client@yeboox.test', 310],
        ] as [$name, $phone, $email, $points]) {
            Customer::updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $name,
                    'email' => $email,
                    'points' => $points,
                    'is_active' => true,
                ],
            );
        }

        $this->setWhenEmpty('pos_banks', ['Rawbank', 'EquityBCDC', 'Ecobank', 'TMB']);
        $this->setWhenEmpty('pos_mobile_providers', ['M-Pesa', 'Airtel Money', 'Orange Money', 'Afrimoney']);
        $this->setWhenEmpty('pos_yepay_providers', ['YePay Wallet']);
        $this->setWhenEmpty('restaurant_tables', [
            [
                'name' => 'Salle principale',
                'tables' => collect(range(1, 10))->map(fn (int $number) => ['name' => "Table {$number}"])->all(),
            ],
            [
                'name' => 'Terrasse',
                'tables' => collect(range(1, 6))->map(fn (int $number) => ['name' => "Terrasse {$number}"])->all(),
            ],
            [
                'name' => 'Espace VIP',
                'tables' => collect(range(1, 4))->map(fn (int $number) => ['name' => "VIP {$number}"])->all(),
            ],
        ]);
    }

    private function setWhenEmpty(string $key, array $value): void
    {
        if (blank(Setting::get($key))) {
            Setting::set($key, json_encode($value, JSON_UNESCAPED_UNICODE));
        }
    }
}
