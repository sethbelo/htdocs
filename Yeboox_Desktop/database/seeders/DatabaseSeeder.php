<?php

namespace Database\Seeders;

use App\Models\AccountingRecord;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SystemSeeder::class);

        $superAdminRole = Role::findByName('Super administrateur', 'web');
        $gerantRole = Role::findByName('Gérant', 'web');
        $caissierRole = Role::findByName('Caissier', 'web');
        $company = Company::query()->with('pointsOfSale')->first();
        $point = $company?->pointsOfSale->first();
        $tenantIdentity = [
            'company_id' => $company?->id,
            'current_establishment_id' => $point?->establishment_id,
            'current_point_of_sale_id' => $point?->id,
            'is_active' => true,
        ];

        // L'assistant de première utilisation fournit déjà l'administrateur.
        // Le compte par défaut n'est créé que lors d'un db:seed de développement.
        $adminUser = User::role('Super administrateur')->first();
        if (! $adminUser) {
            $adminUser = User::create([
                ...$tenantIdentity,
                'name' => 'Super Admin',
                'email' => 'admin@spa.com',
                'password' => Hash::make('password'),
            ]);
            $adminUser->assignRole($superAdminRole);
        }
        if ($point) {
            $adminUser->forceFill($tenantIdentity + ['is_master_admin' => true, 'is_company_admin' => true])->save();
            $adminUser->pointsOfSale()->syncWithoutDetaching([$point->id => ['access_level' => 'administrator']]);
        }

        $gerantUser = User::create([
            ...$tenantIdentity,
            'name' => 'Gérant Spa',
            'email' => 'gerant@spa.com',
            'password' => Hash::make('password'),
        ]);
        $gerantUser->assignRole($gerantRole);

        $caissierUser = User::create([
            ...$tenantIdentity,
            'name' => 'Caissier Principal',
            'email' => 'caissier@spa.com',
            'password' => Hash::make('password'),
        ]);
        $caissierUser->assignRole($caissierRole);

        // Employés supplémentaires pour le planning / coiffure
        $employee1 = User::create([
            ...$tenantIdentity,
            'name' => 'Sarah Lopez (Coiffeuse)',
            'email' => 'sarah@spa.com',
            'password' => Hash::make('password'),
        ]);
        $employee1->assignRole($caissierRole);

        $employee2 = User::create([
            ...$tenantIdentity,
            'name' => 'Grace Kabulo (Massothérapeute)',
            'email' => 'grace@spa.com',
            'password' => Hash::make('password'),
        ]);
        $employee2->assignRole($caissierRole);

        if ($point) {
            $gerantUser->pointsOfSale()->attach($point->id, ['access_level' => 'manager']);
            foreach ([$caissierUser, $employee1, $employee2] as $operator) {
                $operator->pointsOfSale()->attach($point->id, ['access_level' => 'operator']);
            }
        }

        // 3. Clients de démonstration
        $customer1 = Customer::create([
            'name' => 'Marie Kabeya',
            'phone' => '+243812345678',
            'email' => 'marie@gmail.com',
            'points' => 300, // assez pour échanger contre 5 USD
        ]);

        $customer2 = Customer::create([
            'name' => 'Alain Mukendi',
            'phone' => '+243998765432',
            'email' => 'alain.m@gmail.com',
            'points' => 150,
        ]);

        $customer3 = Customer::create([
            'name' => 'Patricia Mpia',
            'phone' => '+243899999999',
            'email' => 'patricia@gmail.com',
            'points' => 50,
        ]);

        // 4. Produits et Services (Inventaire)
        // Services du Salon
        $service1 = Product::create([
            'name' => 'Coupe Dames Tactile & Brushing',
            'sku' => 'SRV-COIF-01',
            'category' => 'Salon de coiffure',
            'type' => 'service',
            'price' => 25.00,
            'cost' => 5.00,
        ]);

        $service2 = Product::create([
            'name' => 'Massage Suédois Relaxant (1H)',
            'sku' => 'SRV-SPA-01',
            'category' => 'Spa',
            'type' => 'service',
            'price' => 50.00,
            'cost' => 10.00,
        ]);

        $service3 = Product::create([
            'name' => 'Soin du Visage HydraFacial',
            'sku' => 'SRV-SPA-02',
            'category' => 'Spa',
            'type' => 'service',
            'price' => 75.00,
            'cost' => 15.00,
        ]);

        $newSpaServices = [
            ['name' => 'Manucure avec vernis permanent', 'price' => 30.00],
            ['name' => 'Pédicure simple avec vernis permanent', 'price' => 50.00],
            ['name' => 'Pedi Mani express', 'price' => 60.00],
            ['name' => 'Pose vernis simple', 'price' => 10.00],
            ['name' => 'toutes les full set', 'price' => 60.00],
            ['name' => 'Remplissage full', 'price' => 40.00],
            ['name' => 'Remplissage ovely', 'price' => 40.00],
            ['name' => 'baby boomer', 'price' => 70.00],
            ['name' => 'Full set + french', 'price' => 70.00],
            ['name' => 'Gel, Acrylique Remover', 'price' => 15.00],
            ['name' => 'posé tips+ vers permanent', 'price' => 35.00],
            // Items with 0 price:
            ['name' => 'Manucure', 'price' => 0.00],
            ['name' => 'Pédicure Simple, Milk, Champagne, Chocolat', 'price' => 0.00],
            ['name' => 'Pédicure, Manucure Express', 'price' => 0.00],
            ['name' => 'Full Set Gel + Acrylique', 'price' => 0.00],
            ['name' => 'Full Set Scrupture', 'price' => 0.00],
            ['name' => 'Gel Acrylique', 'price' => 0.00],
            ['name' => 'Full Set Nail Art', 'price' => 0.00],
            ['name' => 'Full Set Remplissage', 'price' => 0.00],
            ['name' => 'Lovely Gel Acrylique', 'price' => 0.00],
            ['name' => 'Remplissage Full Set', 'price' => 0.00],
            ['name' => 'Remplissage Lovely', 'price' => 0.00],
            ['name' => 'Pose Americain', 'price' => 0.00],
            ['name' => 'Baby boomer/French', 'price' => 0.00],
        ];

        foreach ($newSpaServices as $index => $serviceData) {
            Product::create([
                'name' => $serviceData['name'],
                'sku' => 'SRV-SPA-'.str_pad($index + 3, 3, '0', STR_PAD_LEFT),
                'category' => 'Spa',
                'type' => 'service',
                'price' => $serviceData['price'],
                'cost' => 0.00,
            ]);
        }

        // Produits du Bar
        $prod1 = Product::create([
            'name' => 'Jus de fruits frais pressés',
            'sku' => 'BAR-DRINK-01',
            'category' => 'Bar',
            'type' => 'product',
            'price' => 5.00,
            'cost' => 1.50,
            'stock_qty' => 50,
            'alert_qty' => 10,
        ]);

        $prod2 = Product::create([
            'name' => 'Eau de source (500ml)',
            'sku' => 'BAR-DRINK-02',
            'category' => 'Bar',
            'type' => 'product',
            'price' => 2.00,
            'cost' => 0.50,
            'stock_qty' => 100,
            'alert_qty' => 20,
        ]);

        $prod3 = Product::create([
            'name' => 'Cocktail détox concombre-menthe',
            'sku' => 'BAR-DRINK-03',
            'category' => 'Bar',
            'type' => 'product',
            'price' => 8.00,
            'cost' => 2.00,
            'stock_qty' => 30,
            'alert_qty' => 5,
        ]);

        // 5. Abonnements
        Subscription::create([
            'customer_id' => $customer1->id,
            'plan_name' => 'Abonnement Premium Spa Mensuel',
            'price' => 150.00,
            'points_granted' => 150,
            'starts_at' => Carbon::now()->subDays(10),
            'ends_at' => Carbon::now()->addDays(20),
            'status' => 'active',
        ]);

        Subscription::create([
            'customer_id' => $customer2->id,
            'plan_name' => 'Formule Gym & Fitness 3 Mois',
            'price' => 300.00,
            'points_granted' => 300,
            'starts_at' => Carbon::now()->subDays(45),
            'ends_at' => Carbon::now()->addDays(45),
            'status' => 'active',
        ]);

        // 6. Rendez-vous de démonstration
        Appointment::create([
            'customer_id' => $customer1->id,
            'employee_id' => $employee1->id,
            'service_id' => $service1->id,
            'date_time' => Carbon::tomorrow()->setHour(10)->setMinute(0)->setSecond(0),
            'status' => 'confirmed',
            'notes' => 'Brushing classique sur cheveux longs',
        ]);

        Appointment::create([
            'customer_id' => $customer2->id,
            'employee_id' => $employee2->id,
            'service_id' => $service2->id,
            'date_time' => Carbon::tomorrow()->setHour(14)->setMinute(30)->setSecond(0),
            'status' => 'pending',
            'notes' => 'Ressent des douleurs au niveau du bas du dos',
        ]);

        // 7. Session de caisse de démonstration
        $session = RegisterSession::create([
            'user_id' => $caissierUser->id,
            'opened_at' => Carbon::now()->subHours(4),
            'opening_balance' => 100.00,
            'status' => 'open',
        ]);

        // 8. Écritures comptables initiales (SYSCOHADA)
        // Recette abonnement
        AccountingRecord::create([
            'record_date' => Carbon::now()->subDays(10)->toDateString(),
            'type' => 'income',
            'syscohada_code' => '706', // Prest. de services
            'category' => 'vente',
            'amount' => 150.00,
            'description' => 'Abonnement Premium Spa Mensuel - Marie Kabeya',
            'user_id' => $gerantUser->id,
        ]);

        // Dépense achat stock
        AccountingRecord::create([
            'record_date' => Carbon::now()->subDays(5)->toDateString(),
            'type' => 'expense',
            'syscohada_code' => '601', // Achat de marchandises
            'category' => 'achat_stock',
            'amount' => 45.00,
            'description' => 'Achat lot de jus et eau de source pour le Bar',
            'user_id' => $gerantUser->id,
        ]);

        // Dépense Loyer
        AccountingRecord::create([
            'record_date' => Carbon::now()->subDays(1)->toDateString(),
            'type' => 'expense',
            'syscohada_code' => '613', // Locations et charges connexes
            'category' => 'loyer',
            'amount' => 500.00,
            'description' => 'Loyer mensuel local Spa & Salon',
            'user_id' => $adminUser->id,
        ]);
    }
}
