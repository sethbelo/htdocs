<?php

namespace Database\Seeders;

use App\Enums\ClientType;
use App\Enums\MachineStatus;
use App\Enums\MachineType;
use App\Enums\OrderPriority;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Machine;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\PipelineStage;
use App\Models\PrintRoom;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Cash\CashSessionService;
use App\Services\Cash\OrderCheckoutService;
use App\Services\Cash\PaymentData;
use App\Services\Inventory\StockService;
use App\Services\Pos\CartLine;
use App\Services\Pos\PosCheckoutService;
use App\Services\Sales\OrderService;
use App\Services\Support\NumberGenerator;
use App\Support\DemoAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Données de démonstration (environnement local uniquement).
 * Tous les comptes de démo utilisent le mot de passe « password » et le PIN caisse « 1234 ».
 */
class DemoDataSeeder extends Seeder
{
    public function run(NumberGenerator $numbers, StockService $stock, OrderService $orders, CashSessionService $cash, OrderCheckoutService $checkout, PosCheckoutService $pos): void
    {
        $hq = Site::query()->where('code', 'SIE-KIN')->firstOrFail();
        $shop = Site::query()->where('code', 'BTQ-LIM')->firstOrFail();

        $users = [];
        foreach (config('erp.roles') as $role => $definition) {
            $username = DemoAccounts::username($role);
            $users[$role] = User::query()->firstOrCreate(['username' => $username], [
                'name' => $definition['label'],
                'email' => "{$username}@demo.test",
                'password' => 'password',
                'must_change_password' => false,
                'default_site_id' => in_array($role, ['caissier', 'responsable-boutique', 'gerant'], true) ? $shop->id : $hq->id,
            ]);
            $users[$role]->forceFill([
                'pin_hash' => bcrypt('1234'),
                'login_mode' => $definition['login'] ?? 'password',
                'badge_code' => ($definition['login'] ?? null) === 'badge' ? DemoAccounts::badge($role) : null,
            ])->save();
            $users[$role]->syncRoles([$role]);
            $users[$role]->sites()->syncWithoutDetaching([$hq->id, $shop->id]);
        }

        $users['caissier-siege'] = User::query()->firstOrCreate(['username' => 'caissier.siege'], [
            'name' => 'Caissier Siège', 'email' => 'caissier.siege@demo.test', 'password' => 'password', 'must_change_password' => false, 'default_site_id' => $hq->id,
        ]);
        $users['caissier-siege']->forceFill(['pin_hash' => bcrypt('1234'), 'login_mode' => 'pin'])->save();
        $users['caissier-siege']->syncRoles(['caissier']);
        $users['caissier-siege']->sites()->syncWithoutDetaching([$hq->id]);

        Auth::login($users['directeur-general']);

        $supplier = Supplier::query()->firstOrCreate(['code' => 'FRN-0001'], ['name' => 'Papeterie Centrale du Congo', 'phone' => '+243 810 000 001', 'city' => 'Kinshasa']);
        Supplier::query()->firstOrCreate(['code' => 'FRN-0002'], ['name' => 'Encres & Toners Afrique', 'phone' => '+243 820 000 002', 'city' => 'Kinshasa']);

        $unit = fn (string $symbol): ?int => Unit::query()->where('symbol', $symbol)->value('id');
        $category = fn (string $name): ?int => ProductCategory::query()->where('name', $name)->value('id');

        $catalog = [
            ['SRV-CV', 'Cartes de visite (x100)', ProductType::Service, 'Impression numérique', 'pc', 4, 15, false, false],
            ['SRV-FLY', 'Flyers A5 (x100)', ProductType::Service, 'Impression numérique', 'pc', 8, 25, false, false],
            ['SRV-BAN', 'Bâche grand format (m²)', ProductType::Service, 'Grand format', 'm²', 6, 18, false, false],
            ['SRV-BRO', 'Brochure A4 agrafée', ProductType::Service, 'Impression offset', 'pc', 1.2, 3.5, false, false],
            ['PAP-A4-80', 'Rame papier A4 80 g', ProductType::Paper, 'Papiers & supports', 'rame', 3.5, 6, true, true],
            ['PAP-A3-250', 'Papier couché A3 250 g', ProductType::Paper, 'Papiers & supports', 'f', 0.12, 0.3, true, false],
            ['INK-CMYK', 'Toner CMJN laser', ProductType::Ink, 'Encres & toners', 'cart', 45, 70, true, false],
            ['MRC-STY', 'Stylo bille bleu', ProductType::Merchandise, 'Fournitures bureau', 'pc', 0.15, 0.5, true, true],
            ['MRC-CAH', 'Cahier 200 pages', ProductType::Merchandise, 'Papeterie', 'pc', 0.6, 1.5, true, true],
            ['MRC-CLE', 'Classeur A4', ProductType::Merchandise, 'Fournitures bureau', 'pc', 1.1, 2.5, true, true],
            ['MRC-ENV', 'Enveloppes kraft (x50)', ProductType::Merchandise, 'Papeterie', 'bte', 1.8, 4, true, true],
            ['ACC-CLE', 'Clé USB 32 Go', ProductType::Accessory, 'Accessoires', 'pc', 4, 9, true, true],
        ];

        $mainWarehouse = Warehouse::query()->where('code', 'ENT-CEN')->firstOrFail();
        $shopWarehouse = Warehouse::query()->where('code', 'ENT-LIM')->firstOrFail();

        foreach ($catalog as $index => [$sku, $name, $type, $categoryName, $unitSymbol, $cost, $price, $stockable, $sellable]) {
            $product = Product::query()->firstOrCreate(['sku' => $sku], [
                'barcode' => $stockable ? '6000000'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT) : null,
                'name' => $name, 'type' => $type, 'product_category_id' => $category($categoryName), 'unit_id' => $unit($unitSymbol),
                'supplier_id' => $stockable ? $supplier->id : null, 'cost_price' => $cost, 'sale_price' => $price,
                'tax_rate' => 16, 'min_stock' => $stockable ? 10 : 0, 'max_stock' => $stockable ? 500 : null,
                'is_stockable' => $stockable, 'is_sellable_pos' => $sellable, 'is_favorite' => $index % 3 === 0,
            ]);

            if ($stockable && $product->stockLevels()->doesntExist()) {
                $stock->move($product, $mainWarehouse, StockMovementType::In, 200, null, 'Stock initial');
                if ($sellable) {
                    $stock->move($product, $shopWarehouse, StockMovementType::In, $index === 10 ? 6 : 60, null, 'Stock initial');
                }
            }
        }

        $printRoom = PrintRoom::query()->where('code', 'SAL-01')->firstOrFail();
        foreach ([['MAC-01', 'Konica Minolta C4065', MachineType::DigitalPrinter, 'Konica Minolta', 'bizhub PRESS C4065'], ['MAC-02', 'Heidelberg SM 52', MachineType::Offset, 'Heidelberg', 'Speedmaster SM 52'], ['MAC-03', 'Traceur Roland 1,6 m', MachineType::Plotter, 'Roland', 'TrueVIS VG3'], ['MAC-04', 'Massicot Ideal 4850', MachineType::Guillotine, 'Ideal', '4850'], ['MAC-05', 'Plastifieuse GMP', MachineType::Laminator, 'GMP', 'Excelam 655']] as $i => [$code, $name, $type, $brand, $model]) {
            Machine::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'type' => $type, 'brand' => $brand, 'model' => $model, 'serial_number' => strtoupper(fake()->bothify('SN-####-????')),
                'site_id' => $hq->id, 'print_room_id' => $printRoom->id, 'status' => $i === 4 ? MachineStatus::Maintenance : MachineStatus::Operational,
                'counter' => fake()->numberBetween(10000, 900000), 'last_maintenance_at' => now()->subDays(60), 'next_maintenance_at' => now()->addDays($i === 1 ? 3 : 30),
            ]);
        }

        $clients = collect([
            ['Jean Mukendi', null, ClientType::Individual, 'b2c'],
            ['Service achats', 'Ministère du Plan', ClientType::Administration, 'b2b'],
            ['Direction communication', 'ONG Espoir Kinshasa', ClientType::Ngo, 'b2b'],
            ['Grâce Lukusa', 'Lukusa Distribution', ClientType::Reseller, 'b2b'],
            ['Patrick Ilunga', 'Brasserie du Fleuve', ClientType::Company, 'b2b'],
        ])->map(fn (array $c): Client => Client::query()->firstOrCreate(['name' => $c[0], 'company_name' => $c[1]], [
            'number' => $numbers->next(NumberGenerator::Client), 'type' => $c[2], 'segment' => $c[3], 'phone' => fake()->numerify('+243 8## ### ###'),
            'whatsapp' => fake()->numerify('+243 8## ### ###'), 'email' => fake()->safeEmail(), 'city' => 'Kinshasa', 'site_id' => $hq->id,
            'sales_rep_id' => $users['commercial']->id, 'allow_credit' => $c[3] === 'b2b', 'credit_limit' => $c[3] === 'b2b' ? 2000 : 0,
        ]));

        foreach (PipelineStage::query()->orderBy('position')->get() as $stage) {
            Opportunity::factory()->count(2)->create(['pipeline_stage_id' => $stage->id, 'sales_rep_id' => $users['commercial']->id, 'site_id' => $hq->id]);
        }

        if ($hq->printRooms()->exists() && Order::query()->doesntExist()) {
            $services = Product::query()->where('type', ProductType::Service)->get()->keyBy('sku');
            $specs = [
                ['SRV-CV', 5, ['format' => '85×55', 'paper' => 'Couché mat', 'grammage' => 350, 'colors' => 'Quadri', 'print_sides' => 'recto_verso', 'lamination' => true, 'cutting' => 'Coins arrondis']],
                ['SRV-FLY', 10, ['format' => 'A5', 'paper' => 'Couché brillant', 'grammage' => 135, 'colors' => 'Quadri', 'print_sides' => 'recto']],
                ['SRV-BAN', 6, ['format' => 'Sur mesure', 'width' => 300, 'height' => 200, 'paper' => 'Bâche 510 g', 'colors' => 'Quadri', 'finishing' => 'Œillets tous les 50 cm']],
                ['SRV-BRO', 200, ['format' => 'A4', 'paper' => 'Couché mat', 'grammage' => 115, 'colors' => 'Quadri', 'print_sides' => 'recto_verso', 'binding' => 'Agrafage', 'pages' => 16]],
            ];

            $register = CashRegister::query()->where('code', 'CS-SIE-01')->firstOrFail();
            $session = $cash->open($register, $users['caissier-siege'], 100, '1234');

            foreach ($clients as $i => $client) {
                [$sku, $qty, $spec] = $specs[$i % count($specs)];
                $service = $services[$sku];
                $order = $orders->create([
                    'client_id' => $client->id, 'site_id' => $hq->id, 'due_at' => now()->addDays($i + 1),
                    'priority' => $i === 1 ? OrderPriority::Urgent : OrderPriority::Normal, 'requires_design' => $i !== 3,
                    'deposit_rate' => 50, 'technical_notes' => 'Fichiers client fournis par e-mail.',
                ], [['product_id' => $service->id, 'description' => $service->name, 'quantity' => $qty, 'unit_price' => $service->sale_price, 'tax_rate' => 16, 'discount' => 0] + $spec], $users['commercial']);

                if ($i < 4) {
                    Auth::login($users['caissier-siege']);
                    $checkout->checkout($order, PaymentData::collection([['method' => $i % 2 ? PaymentMethod::MobileMoney->value : PaymentMethod::Cash->value, 'amount' => $i === 2 ? $order->deposit_required : $order->total, 'reference' => $i % 2 ? 'MM-'.fake()->numerify('#########') : null]]), $session, $users['caissier-siege']);
                    Auth::login($users['directeur-general']);
                }
            }

            $shopRegister = CashRegister::query()->where('code', 'CS-LIM-01')->firstOrFail();
            Auth::login($users['caissier']);
            $shopSession = $cash->open($shopRegister, $users['caissier'], 50, '1234');
            $merch = Product::query()->where('is_sellable_pos', true)->where('sku', '!=', 'MRC-ENV')->get();
            foreach (range(1, 6) as $n) {
                $lines = $merch->random(2)->map(fn (Product $p): CartLine => new CartLine($p->id, rand(1, 3)))->values()->all();
                $total = collect($lines)->sum(fn (CartLine $l): float => $l->quantity * (float) $merch->firstWhere('id', $l->productId)->sale_price * 1.16);
                $pos->checkout($lines, [new PaymentData(PaymentMethod::Cash, ceil($total))], $shopSession, $users['caissier'], $n % 2 ? $clients->first() : null);
            }
            Auth::login($users['directeur-general']);
        }

        $departments = Department::query()->pluck('id', 'code');
        foreach ([['Marie', 'Kabila', 'Graphiste senior', 'GRA', 'graphiste'], ['Paul', 'Tshisekedi', 'Opérateur offset', 'IMP', 'imprimeur'], ['Aline', 'Mbuyi', 'Caissière', 'BTQ', 'caissier'], ['David', 'Kasongo', 'Commercial terrain', 'COM', 'commercial'], ['Ruth', 'Nsimba', 'Comptable', 'FIN', 'comptable']] as $i => [$first, $last, $title, $dept, $role]) {
            Employee::query()->firstOrCreate(['matricule' => 'EMP-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT)], [
                'first_name' => $first, 'last_name' => $last, 'job_title' => $title, 'department_id' => $departments[$dept] ?? null,
                'site_id' => $hq->id, 'user_id' => $users[$role]->id, 'employment_status' => 'permanent', 'hired_at' => now()->subYears($i + 1),
                'salary_type' => 'monthly', 'base_salary' => (string) (400 + $i * 60), 'phone' => fake()->numerify('+243 8## ### ###'),
            ]);
        }

        Auth::logout();
    }
}
