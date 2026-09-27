<?php

namespace Database\Seeders;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Actions\Documents\CreateDocument;
use App\Enums\DocumentType;
use App\Models\Appointment;
use App\Models\Bom;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\ExpenseClaim;
use App\Models\FinancialAccount;
use App\Models\JobPosition;
use App\Models\LeaveType;
use App\Models\LoyaltyProgram;
use App\Models\MailRecord;
use App\Models\ManufacturingOrder;
use App\Models\Message;
use App\Models\Opportunity;
use App\Models\PayrollPeriod;
use App\Models\Pipeline;
use App\Models\PlanningSlot;
use App\Models\Product;
use App\Models\Project;
use App\Models\SalaryAdvance;
use App\Models\Site;
use App\Models\SiteEvaluation;
use App\Models\SiteReport;
use App\Models\Task;
use App\Models\Timesheet;
use App\Models\Visitor;
use App\Models\Warehouse;
use App\Services\CashService;
use App\Services\ExpenseClaimService;
use App\Services\HrService;
use App\Services\LoyaltyService;
use App\Services\ManufacturingService;
use App\Services\OrderWorkflow;
use App\Services\PayrollService;
use App\Services\PosService;
use App\Services\RentalService;
use App\Services\ReplenishmentService;
use App\Services\SequenceService;
use App\Services\StockService;
use App\Services\VoucherService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Jeu de démonstration réaliste : chaque module contient des données
 * cohérentes, générées en passant par les vrais services métier (les
 * écritures comptables, mouvements de stock et numérotations sont donc
 * identiques à une utilisation réelle).
 */
class DemoSeeder extends Seeder
{
    private int $cid;

    private array $users;

    public function run(array $users): void
    {
        $this->users = $users;
        $this->cid = company_id();
        mt_srand(2026);

        $hr = $this->humanResources();
        [$warehouses, $products, $suppliers] = $this->catalogue();
        $clients = $this->clients();
        $equipment = $this->equipment($warehouses);
        $this->crm($clients);
        $sites = $this->ordersAndOperations($clients, $products, $hr, $warehouses, $equipment);
        $this->attendanceAndPayroll($hr, $sites);
        $this->vouchers($products, $warehouses, $sites, $hr, $suppliers);
        $this->commerce($clients, $products, $equipment);
        $this->services($clients, $hr, $sites);
        $this->finance($hr, $sites);
    }

    // ═════════════════════════════════════════════════════════════════════
    // 1. RESSOURCES HUMAINES
    // ═════════════════════════════════════════════════════════════════════
    private function humanResources(): array
    {
        $departments = [];
        foreach ([['DG', 'Direction générale', '#104A63'], ['ADM', 'Administration & Finance', '#1D6887'], ['COM', 'Commercial', '#3ACECE'], ['ATL', 'Atelier & Fabrication', '#F5B50B'], ['BTP', 'Chantiers & BTP', '#F7313A'], ['LOG', 'Logistique', '#8B5CF6'], ['RH', 'RH', '#10B981']] as [$code, $name, $color]) {
            $departments[$code] = Department::create(['company_id' => $this->cid, 'code' => $code, 'name' => $name, 'color' => $color]);
        }
        $position = fn (string $dept, string $title) => JobPosition::firstOrCreate(['company_id' => $this->cid, 'department_id' => $departments[$dept]->id, 'title' => $title])->id;

        foreach ([['CA', 'Congé annuel', 12, true, true, '#3ACECE'], ['MAL', 'Congé maladie', 30, true, false, '#F7313A'], ['MAT', 'Congé de maternité', 98, true, false, '#8B5CF6'], ['CIR', 'Congé de circonstance', 5, true, false, '#FFCE31'], ['SS', 'Congé sans solde', 0, false, false, '#8A9DAC']] as [$code, $name, $days, $paid, $deducts, $color]) {
            LeaveType::create(['company_id' => $this->cid, 'code' => $code, 'name' => $name, 'days_per_year' => $days, 'is_paid' => $paid, 'deducts_balance' => $deducts, 'color' => $color]);
        }

        $permanent = [
            ['Jean-Pierre', 'Mukendi', 'M', 'DG', 'Directeur Général', 4500, 'direction', 4],
            ['Christelle', 'Ilunga', 'F', 'ADM', 'Responsable administratif', 1800, 'admin', 2],
            ['Patrick', 'Tshibanda', 'M', 'ADM', 'Chef comptable', 1600, 'compta', 3],
            ['Grâce', 'Mbuyi', 'F', 'ADM', 'Caissière principale', 700, 'caisse', 1],
            ['Dorcas', 'Malala', 'F', 'COM', 'Chargée d’affaires', 1200, 'commercial', 2],
            ['Rémy', 'Lukusa', 'M', 'LOG', 'Magasinier', 1000, 'magasin', 0],
            ['Esther', 'Kanku', 'F', 'RH', 'Responsable RH', 1000, 'rh', 0],
            ['Joseph', 'Kalala', 'M', 'ATL', 'Chef d’atelier menuiserie', 1400, 'atelier', 5],
            ['Didier', 'Ngoy', 'M', 'BTP', 'Conducteur de travaux', 1500, 'chantier', 3],
            ['Nadine', 'Kabeya', 'F', 'COM', 'Réceptionniste', 550, 'accueil', 1],
            ['Serge', 'Mutombo', 'M', 'LOG', 'Responsable achats', 1100, 'achats', 2],
            ['Fiston', 'Mbala', 'M', 'ATL', 'Menuisier ébéniste', 650, null, 3],
            ['Alain', 'Kasongo', 'M', 'ATL', 'Tapissier garnisseur', 600, null, 2],
            ['Blaise', 'Nkulu', 'M', 'ATL', 'Peintre finisseur', 550, null, 1],
            ['Cédric', 'Lumbala', 'M', 'BTP', 'Chef d’équipe maçonnerie', 800, null, 4],
        ];
        $employees = ['permanent' => [], 'daily' => []];
        foreach ($permanent as $i => [$first, $last, $gender, $dept, $title, $salary, $login, $dependents]) {
            $employees['permanent'][] = Employee::create([
                'company_id' => $this->cid, 'department_id' => $departments[$dept]->id, 'job_position_id' => $position($dept, $title),
                'user_id' => $login ? $this->users[$login]->id : null, 'matricule' => sprintf('EMP-%03d', $i + 1),
                'first_name' => $first, 'last_name' => $last, 'gender' => $gender, 'birth_date' => now()->subYears(mt_rand(24, 55))->subDays(mt_rand(0, 360)),
                'phone' => '+243 8'.mt_rand(10, 99).' '.mt_rand(100, 999).' '.mt_rand(100, 999), 'email' => $login ? "{$login}@sogeref.cd" : null,
                'address' => ['Gombe', 'Limete', 'Ngaliema', 'Lemba', 'Kintambo', 'Bandalungwa'][mt_rand(0, 5)].', Kinshasa', 'national_id' => 'CD'.mt_rand(10000000, 99999999),
                'cnss_number' => '1'.mt_rand(100000000, 999999999), 'dependents' => $dependents, 'category' => $i < 11 ? 'permanent' : 'contractuel', 'pay_type' => 'monthly',
                'base_salary' => $salary, 'transport_allowance' => $salary >= 1000 ? 0 : 50, 'currency_code' => 'USD', 'payment_method' => $salary >= 1000 ? 'bank' : 'cash',
                'hire_date' => now()->subMonths(mt_rand(8, 96)), 'leave_balance' => mt_rand(4, 18), 'status' => 'active',
            ]);
        }
        // Les deux fiches fournies (Magasinier EMP-006, Responsable RH EMP-007) : 1 000 $ bruts, sans indemnité.
        $employees['permanent'][5]->update(['transport_allowance' => 0, 'dependents' => 0]);
        $employees['permanent'][6]->update(['transport_allowance' => 0, 'dependents' => 0]);

        $daily = [['Papy', 'Mulumba', 'Maçon', 18], ['Héritier', 'Kabasele', 'Maçon', 18], ['Junior', 'Tshimanga', 'Ferrailleur', 20], ['Glody', 'Mpoyi', 'Coffreur', 18], ['Trésor', 'Kazadi', 'Manœuvre', 12], ['Merveille', 'Ilunga', 'Manœuvre', 12], ['Christian', 'Mbayo', 'Manœuvre', 12], ['Jonathan', 'Kapinga', 'Carreleur', 20], ['Emmanuel', 'Luboya', 'Électricien', 22], ['Bienvenu', 'Kanyinda', 'Plombier', 22], ['Dieumerci', 'Ngandu', 'Manœuvre', 12], ['Gédéon', 'Tshilumba', 'Soudeur', 22]];
        foreach ($daily as $i => [$first, $last, $trade, $rate]) {
            $employees['daily'][] = Employee::create([
                'company_id' => $this->cid, 'department_id' => $departments['BTP']->id, 'job_position_id' => $position('BTP', $trade),
                'matricule' => sprintf('JRN-%03d', $i + 1), 'first_name' => $first, 'last_name' => $last, 'gender' => 'M',
                'phone' => '+243 9'.mt_rand(10, 99).' '.mt_rand(100, 999).' '.mt_rand(100, 999), 'category' => 'journalier', 'pay_type' => 'daily',
                'daily_rate' => $rate, 'currency_code' => 'USD', 'payment_method' => 'cash', 'hire_date' => now()->subMonths(mt_rand(1, 20)), 'status' => 'active',
            ]);
        }

        $hr = app(HrService::class);
        $annual = LeaveType::where('code', 'CA')->first();
        $hr->decideLeave($hr->requestLeave($employees['permanent'][2], $annual, now()->addDays(10)->toDateString(), now()->addDays(19)->toDateString(), 'Congé annuel — voyage familial à Lubumbashi'), true, 'Accordé, passation au comptable adjoint.');
        $hr->requestLeave($employees['permanent'][11], $annual, now()->addDays(4)->toDateString(), now()->addDays(8)->toDateString(), 'Mariage d’un proche');
        $hr->requestLeave($employees['permanent'][9], LeaveType::where('code', 'CIR')->first(), now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString(), 'Événement familial');
        $hr->decideLeave($hr->requestLeave($employees['permanent'][13], LeaveType::where('code', 'MAL')->first(), now()->subDays(20)->toDateString(), now()->subDays(17)->toDateString(), 'Certificat médical fourni'), true);

        SalaryAdvance::create(['company_id' => $this->cid, 'employee_id' => $employees['permanent'][11]->id, 'amount' => 120, 'granted_at' => now()->subDays(12), 'note' => 'Frais scolaires']);

        return $employees;
    }

    // ═════════════════════════════════════════════════════════════════════
    // 2. CATALOGUE, ENTREPÔTS & NOMENCLATURES
    // ═════════════════════════════════════════════════════════════════════
    private function catalogue(): array
    {
        $w = [];
        foreach ([['DEP', 'Dépôt central — Gombe', 'central', 'Av. du Port, Gombe'], ['ATL', 'Atelier menuiserie — Limete', 'atelier', '7e rue, Limete industriel'], ['SHW', 'Showroom — Gombe', 'showroom', 'Bd du 30 Juin, Gombe']] as [$code, $name, $type, $address]) {
            $w[$code] = Warehouse::create(['company_id' => $this->cid, 'code' => $code, 'name' => $name, 'type' => $type, 'address' => $address, 'manager_id' => $this->users['magasin']->id]);
        }

        $suppliers = [];
        foreach ([['FRN-00001', 'CILU — Cimenterie de Lukala', 'ventes@cilu.cd', 'Kinshasa'], ['FRN-00002', 'Quincaillerie Kin Marché', 'contact@kinmarche.cd', 'Kinshasa'], ['FRN-00003', 'Bois & Placages du Congo', 'commandes@boiscongo.cd', 'Kinshasa'], ['FRN-00004', 'Tissus Plus Afrique', 'ventes@tissusplus.cd', 'Kinshasa'], ['FRN-00005', 'SOCIMAT — Matériaux de construction', 'info@socimat.cd', 'Kinshasa']] as [$ref, $name, $email, $city]) {
            $suppliers[$ref] = Contact::create(['company_id' => $this->cid, 'type' => 'supplier', 'segment' => 'b2b', 'reference' => $ref, 'company_name' => $name, 'email' => $email, 'phone' => '+243 81 '.mt_rand(100, 999).' '.mt_rand(1000, 9999), 'city' => $city, 'currency_code' => 'USD', 'payment_terms' => 30]);
        }

        $cats = [];
        foreach (['Mobilier fabriqué', 'Menuiserie', 'Bois & panneaux', 'Garnissage', 'Quincaillerie', 'Gros œuvre', 'Finitions', 'Prestations'] as $name) {
            $cats[$name] = Category::create(['company_id' => $this->cid, 'type' => 'product', 'name' => $name]);
        }

        $p = [];
        $make = function (string $sku, string $name, string $type, string $cat, string $unit, float $sale, float $cost, array $extra = []) use (&$p, $cats) {
            return $p[$sku] = Product::create(array_merge([
                'company_id' => $this->cid, 'category_id' => $cats[$cat]->id, 'type' => $type, 'sku' => $sku, 'name' => $name, 'unit' => $unit,
                'sale_price' => $sale, 'purchase_price' => $cost, 'tax_rate' => in_array($type, ['finished', 'product'], true) ? 16 : 0,
                'track_stock' => $type !== 'service', 'is_sellable' => in_array($type, ['finished', 'product', 'service', 'material'], true),
                'is_purchasable' => in_array($type, ['material', 'consumable', 'product'], true), 'is_manufactured' => $type === 'finished',
                'pos_visible' => in_array($type, ['finished', 'product'], true), 'is_active' => true,
            ], $extra));
        };

        // Matières premières & consommables
        $make('MAT-AFR-01', 'Bois massif Afrormosia (planche 4 m)', 'material', 'Bois & panneaux', 'pièce', 0, 38, ['reorder_level' => 40, 'reorder_quantity' => 120, 'default_supplier_id' => $suppliers['FRN-00003']->id, 'description' => 'Planche 4 m × 20 cm × 4 cm, séchée.']);
        $make('MAT-CTP-18', 'Contreplaqué 18 mm (122 × 244)', 'material', 'Bois & panneaux', 'feuille', 0, 42, ['reorder_level' => 25, 'reorder_quantity' => 60, 'default_supplier_id' => $suppliers['FRN-00003']->id]);
        $make('MAT-MDF-16', 'Panneau MDF mélaminé 16 mm', 'material', 'Bois & panneaux', 'feuille', 0, 36, ['reorder_level' => 20, 'reorder_quantity' => 50, 'default_supplier_id' => $suppliers['FRN-00003']->id]);
        $make('MAT-VEL-RH', 'Tissu velours lisse uni rouge Hermès', 'material', 'Garnissage', 'm', 0, 14, ['reorder_level' => 60, 'reorder_quantity' => 150, 'default_supplier_id' => $suppliers['FRN-00004']->id]);
        $make('MAT-MOU-35', 'Mousse haute densité 35 kg/m³', 'material', 'Garnissage', 'm²', 0, 9, ['reorder_level' => 40, 'reorder_quantity' => 100, 'default_supplier_id' => $suppliers['FRN-00004']->id]);
        $make('MAT-PIED-M', 'Piètement métallique chaise (jeu de 4)', 'material', 'Quincaillerie', 'lot', 0, 16, ['reorder_level' => 30, 'reorder_quantity' => 80, 'default_supplier_id' => $suppliers['FRN-00002']->id]);
        $make('MAT-VIS-50', 'Vis bois 5×50 (boîte de 200)', 'consumable', 'Quincaillerie', 'lot', 0, 6, ['reorder_level' => 15, 'reorder_quantity' => 40, 'default_supplier_id' => $suppliers['FRN-00002']->id]);
        $make('MAT-COL-5', 'Colle vinylique D3 (5 kg)', 'consumable', 'Quincaillerie', 'lot', 0, 18, ['reorder_level' => 8, 'reorder_quantity' => 20, 'default_supplier_id' => $suppliers['FRN-00002']->id]);
        $make('MAT-VER-4', 'Vernis polyuréthane (4 L)', 'consumable', 'Finitions', 'litre', 0, 32, ['reorder_level' => 10, 'reorder_quantity' => 24, 'default_supplier_id' => $suppliers['FRN-00002']->id]);
        $make('MAT-CHAR', 'Paumelles & quincaillerie de porte (kit)', 'material', 'Quincaillerie', 'lot', 0, 11, ['reorder_level' => 20, 'reorder_quantity' => 40, 'default_supplier_id' => $suppliers['FRN-00002']->id]);
        $make('MAT-CIM-50', 'Ciment CPJ 42.5 (sac 50 kg)', 'material', 'Gros œuvre', 'sac', 14, 11, ['reorder_level' => 200, 'reorder_quantity' => 600, 'default_supplier_id' => $suppliers['FRN-00001']->id]);
        $make('MAT-HA12', 'Fer à béton HA12 (barre 12 m)', 'material', 'Gros œuvre', 'barre', 16, 12.5, ['reorder_level' => 150, 'reorder_quantity' => 400, 'default_supplier_id' => $suppliers['FRN-00005']->id]);
        $make('MAT-HA10', 'Fer à béton HA10 (barre 12 m)', 'material', 'Gros œuvre', 'barre', 12, 9, ['reorder_level' => 150, 'reorder_quantity' => 400, 'default_supplier_id' => $suppliers['FRN-00005']->id]);
        $make('MAT-SAB', 'Sable de rivière', 'material', 'Gros œuvre', 'm³', 30, 22, ['reorder_level' => 20, 'reorder_quantity' => 60, 'default_supplier_id' => $suppliers['FRN-00005']->id]);
        $make('MAT-GRA', 'Gravier concassé 5/15', 'material', 'Gros œuvre', 'm³', 48, 36, ['reorder_level' => 15, 'reorder_quantity' => 40, 'default_supplier_id' => $suppliers['FRN-00005']->id]);
        $make('MAT-BLO-20', 'Bloc ciment creux 20 × 20 × 40', 'material', 'Gros œuvre', 'pièce', 1.2, 0.85, ['reorder_level' => 2000, 'reorder_quantity' => 6000, 'default_supplier_id' => $suppliers['FRN-00005']->id]);
        $make('MAT-TOL-BAC', 'Tôle bac alu-zinc 0,45 mm', 'material', 'Gros œuvre', 'feuille', 22, 17, ['reorder_level' => 60, 'reorder_quantity' => 150, 'default_supplier_id' => $suppliers['FRN-00005']->id]);
        $make('MAT-PEI-20', 'Peinture acrylique façade (20 L)', 'material', 'Finitions', 'pièce', 95, 72, ['reorder_level' => 12, 'reorder_quantity' => 30, 'default_supplier_id' => $suppliers['FRN-00002']->id]);
        $make('MAT-CAR-60', 'Carreau grès cérame 60 × 60', 'material', 'Finitions', 'm²', 28, 19, ['reorder_level' => 80, 'reorder_quantity' => 250, 'default_supplier_id' => $suppliers['FRN-00005']->id]);

        // Produits finis (fabriqués en atelier)
        $make('CA-MLBN', 'Chaise amovible garnie velours (0,62 × 0,43 × 1,01 m)', 'finished', 'Mobilier fabriqué', 'pièce', 100, 58, ['description' => 'Chaise garnie en tissu velours lisse uni rouge Hermès, accoudoir revêtu de bois massif Afrormosia avec support pied métallique.', 'reorder_level' => 0]);
        $make('TB-REU-12', 'Table de réunion Afrormosia 12 places', 'finished', 'Mobilier fabriqué', 'pièce', 2400, 1250, ['description' => 'Plateau massif 4,20 × 1,40 m, piètement central, passe-câbles intégrés.']);
        $make('BU-DIR-18', 'Bureau de direction 1,80 m avec retour', 'finished', 'Mobilier fabriqué', 'pièce', 1350, 690, ['description' => 'Bureau en L, caisson 3 tiroirs, finition vernis satiné.']);
        $make('AR-HAU-2P', 'Armoire haute 2 portes mélaminée', 'finished', 'Mobilier fabriqué', 'pièce', 520, 260);
        $make('PO-ISO-83', 'Porte isoplane 83 × 204 avec cadre', 'finished', 'Menuiserie', 'pièce', 185, 92, ['description' => 'Porte isoplane prépeinte, huisserie bois dur, quincaillerie incluse.']);
        $make('LI-2P-160', 'Lit 2 places 160 × 200 tête de lit capitonnée', 'finished', 'Mobilier fabriqué', 'pièce', 890, 430);

        // Prestations
        $make('SRV-POSE', 'Pose et installation sur site', 'service', 'Prestations', 'forfait', 250, 0);
        $make('SRV-ETUDE', 'Étude, plans et métré', 'service', 'Prestations', 'forfait', 1500, 0);
        $make('SRV-TRANS', 'Transport et livraison Kinshasa', 'service', 'Prestations', 'forfait', 120, 0);
        $make('SRV-MO-BTP', 'Main-d’œuvre gros œuvre (jour-équipe)', 'service', 'Prestations', 'jour', 420, 0);

        // Nomenclatures (quantités pour 1 unité produite)
        foreach ([
            'CA-MLBN' => [3.5, ['MAT-AFR-01' => .25, 'MAT-VEL-RH' => 1.2, 'MAT-MOU-35' => .6, 'MAT-PIED-M' => 1, 'MAT-VIS-50' => .05, 'MAT-COL-5' => .03, 'MAT-VER-4' => .15]],
            'TB-REU-12' => [32, ['MAT-AFR-01' => 9, 'MAT-CTP-18' => 2, 'MAT-VIS-50' => .5, 'MAT-COL-5' => .4, 'MAT-VER-4' => 2.5]],
            'BU-DIR-18' => [18, ['MAT-AFR-01' => 3, 'MAT-CTP-18' => 3, 'MAT-MDF-16' => 1, 'MAT-VIS-50' => .3, 'MAT-COL-5' => .2, 'MAT-VER-4' => 1.2]],
            'AR-HAU-2P' => [9, ['MAT-MDF-16' => 4, 'MAT-CHAR' => 1, 'MAT-VIS-50' => .2, 'MAT-COL-5' => .1]],
            'PO-ISO-83' => [4, ['MAT-CTP-18' => 1, 'MAT-AFR-01' => .75, 'MAT-CHAR' => 1, 'MAT-VIS-50' => .1, 'MAT-VER-4' => .3]],
            'LI-2P-160' => [14, ['MAT-AFR-01' => 4, 'MAT-CTP-18' => 1.5, 'MAT-VEL-RH' => 2.5, 'MAT-MOU-35' => 1.5, 'MAT-VIS-50' => .2, 'MAT-COL-5' => .2]],
        ] as $sku => [$hours, $lines]) {
            $bom = Bom::create(['company_id' => $this->cid, 'product_id' => $p[$sku]->id, 'name' => 'Nomenclature '.$p[$sku]->name, 'yield_quantity' => 1, 'labour_hours' => $hours]);
            foreach ($lines as $component => $qty) {
                $bom->lines()->create(['product_id' => $p[$component]->id, 'quantity' => $qty]);
            }
        }

        // Stock initial (inventaire d'ouverture)
        $stock = app(StockService::class);
        foreach ([
            'DEP' => ['MAT-CIM-50' => 540, 'MAT-HA12' => 380, 'MAT-HA10' => 120, 'MAT-SAB' => 45, 'MAT-GRA' => 12, 'MAT-BLO-20' => 5200, 'MAT-TOL-BAC' => 90, 'MAT-PEI-20' => 9, 'MAT-CAR-60' => 310, 'MAT-CHAR' => 26],
            'ATL' => ['MAT-AFR-01' => 96, 'MAT-CTP-18' => 21, 'MAT-MDF-16' => 34, 'MAT-VEL-RH' => 64, 'MAT-MOU-35' => 38, 'MAT-PIED-M' => 52, 'MAT-VIS-50' => 22, 'MAT-COL-5' => 6, 'MAT-VER-4' => 14, 'MAT-CHAR' => 12],
            'SHW' => ['CA-MLBN' => 14, 'BU-DIR-18' => 2, 'AR-HAU-2P' => 3, 'PO-ISO-83' => 6, 'LI-2P-160' => 2],
        ] as $code => $items) {
            foreach ($items as $sku => $qty) {
                $stock->receive($p[$sku], $w[$code], $qty, 'in', null, 'INV-OUVERTURE', notes: 'Inventaire d’ouverture');
            }
        }

        return [$w, $p, $suppliers];
    }

    // ═════════════════════════════════════════════════════════════════════
    // 3. CLIENTS
    // ═════════════════════════════════════════════════════════════════════
    private function clients(): array
    {
        $c = [];
        $i = 0;
        foreach ([
            ['ali', 'b2c', null, 'Ir', 'ALI', 'ir.ali@gmail.com', 'Gombe'],
            ['hotel', 'b2b', 'Hôtel Fleuve Congo', null, null, 'achats@hotelfleuvecongo.cd', 'Gombe'],
            ['banque', 'b2b', 'Banque Commerciale du Kasaï', null, null, 'logistique@bck.cd', 'Gombe'],
            ['ministere', 'b2b', 'Ministère des Infrastructures — Secrétariat général', null, null, 'sg@infrastructures.gouv.cd', 'Gombe'],
            ['clinique', 'b2b', 'Clinique Ngaliema Santé', null, null, 'direction@cliniquengaliema.cd', 'Ngaliema'],
            ['ecole', 'b2b', 'Complexe scolaire Les Bâtisseurs', null, null, 'econome@lesbatisseurs.cd', 'Limete'],
            ['mwamba', 'b2c', null, 'Albert', 'Mwamba', 'albert.mwamba@yahoo.fr', 'Ngaliema'],
            ['kapend', 'b2c', null, 'Sarah', 'Kapend', 'sarah.kapend@gmail.com', 'Lemba'],
            ['onatra', 'b2b', 'SCTP (ex-ONATRA) — Direction technique', null, null, 'technique@sctp.cd', 'Gombe'],
            ['kitenge', 'b2c', null, 'Paul', 'Kitenge', 'p.kitenge@outlook.com', 'Kintambo'],
        ] as [$key, $segment, $company, $first, $last, $email, $city]) {
            $i++;
            $c[$key] = Contact::create([
                'company_id' => $this->cid, 'type' => 'client', 'segment' => $segment, 'reference' => sprintf('CU%s-%05d', now()->format('ym'), $i),
                'company_name' => $company, 'first_name' => $first, 'last_name' => $last, 'email' => $email,
                'phone' => '+243 8'.mt_rand(10, 99).' '.mt_rand(100, 999).' '.mt_rand(100, 999), 'city' => $city, 'currency_code' => 'USD',
                'payment_terms' => $segment === 'b2b' ? 30 : 0, 'discount_rate' => $segment === 'b2b' ? [0, 5, 7.5][mt_rand(0, 2)] : 0,
                'credit_limit' => $segment === 'b2b' ? 25000 : 0, 'tax_number' => $segment === 'b2b' ? 'A'.mt_rand(1000000, 9999999).'K' : null,
                'billing_address' => ['line1' => $city.', Kinshasa', 'country' => 'RDC'], 'owner_id' => $this->users['commercial']->id,
            ]);
        }

        return $c;
    }

    // ═════════════════════════════════════════════════════════════════════
    // 4. PARC MATÉRIEL
    // ═════════════════════════════════════════════════════════════════════
    private function equipment(array $w): array
    {
        $e = [];
        foreach ([
            ['EQ-BET-01', 'Bétonnière 350 L thermique', 'Engins de chantier', 3200, true, 45],
            ['EQ-BET-02', 'Bétonnière 250 L électrique', 'Engins de chantier', 1900, true, 35],
            ['EQ-VIB-01', 'Aiguille vibrante béton', 'Engins de chantier', 650, true, 15],
            ['EQ-ECH-01', 'Échafaudage de façade (lot 50 m²)', 'Échafaudage', 4800, true, 60],
            ['EQ-ECH-02', 'Échafaudage roulant 6 m', 'Échafaudage', 1400, true, 25],
            ['EQ-GRP-01', 'Groupe électrogène 20 kVA', 'Énergie', 7800, true, 80],
            ['EQ-DAM-01', 'Dame sauteuse', 'Compactage', 2300, true, 40],
            ['EQ-SCI-01', 'Scie à panneaux verticale', 'Atelier', 5600, false, 0],
            ['EQ-RAB-01', 'Raboteuse-dégauchisseuse', 'Atelier', 4200, false, 0],
            ['EQ-CMP-01', 'Compresseur 100 L + pistolet', 'Atelier', 900, true, 20],
            ['EQ-PON-01', 'Ponceuse à bande', 'Atelier', 750, false, 0],
            ['EQ-NIV-01', 'Niveau laser rotatif', 'Topographie', 1100, true, 30],
        ] as [$code, $name, $cat, $value, $rentable, $price]) {
            $e[$code] = Equipment::create([
                'company_id' => $this->cid, 'code' => $code, 'name' => $name, 'category' => $cat, 'serial_number' => 'SN-'.mt_rand(100000, 999999),
                'purchase_value' => $value, 'is_rentable' => $rentable, 'daily_rent_price' => $price, 'status' => 'available',
                'warehouse_id' => $cat === 'Atelier' ? $w['ATL']->id : $w['DEP']->id,
            ]);
        }
        $e['EQ-GRP-01']->update(['status' => 'maintenance', 'notes' => 'Révision des 500 h en cours.']);

        return $e;
    }

    // ═════════════════════════════════════════════════════════════════════
    // 5. CRM & FIDÉLITÉ
    // ═════════════════════════════════════════════════════════════════════
    private function crm(array $clients): void
    {
        $pipeline = Pipeline::create(['company_id' => $this->cid, 'name' => 'Pipeline commercial', 'is_default' => true]);
        $stages = collect([['Nouvelle demande', '#8A9DAC', 10], ['Visite / métré', '#3ACECE', 30], ['Devis envoyé', '#1D6887', 50], ['Négociation', '#FFCE31', 75], ['Gagné', '#10B981', 100], ['Perdu', '#F7313A', 0]])
            ->map(fn ($s, $i) => $pipeline->stages()->create(['name' => $s[0], 'color' => $s[1], 'probability' => $s[2], 'position' => $i, 'is_won' => $i === 4, 'is_lost' => $i === 5]));

        foreach ([
            ['Aménagement du hall — Hôtel Fleuve Congo', 'hotel', 18500, 3], ['Mobilier des salles de classe (240 places)', 'ecole', 26400, 2],
            ['Réhabilitation du bloc administratif', 'onatra', 64000, 1], ['Cuisine équipée sur mesure', 'kapend', 7800, 2],
            ['Portes et placards — villa Kintambo', 'kitenge', 5400, 0], ['Agence bancaire de Matete — aménagement', 'banque', 42000, 3],
            ['Salle d’attente et accueil clinique', 'clinique', 12600, 1], ['Rayonnages des archives', 'ministere', 9800, 4],
        ] as $i => [$title, $client, $amount, $stage]) {
            Opportunity::create(['company_id' => $this->cid, 'contact_id' => $clients[$client]->id, 'pipeline_id' => $pipeline->id, 'stage_id' => $stages[$stage]->id, 'owner_id' => $this->users['commercial']->id, 'title' => $title, 'amount' => $amount, 'currency_code' => 'USD', 'expected_close_at' => now()->addDays(10 + $i * 7), 'position' => $i]);
        }

        LoyaltyProgram::create(['company_id' => $this->cid, 'name' => 'Club SOGEREF Particuliers', 'segment' => 'b2c', 'points_per_unit' => 1, 'point_value' => .05, 'min_redeem_points' => 200, 'tiers' => [['name' => 'Bronze', 'threshold' => 0, 'discount' => 0], ['name' => 'Argent', 'threshold' => 2000, 'discount' => 3], ['name' => 'Or', 'threshold' => 8000, 'discount' => 5]]]);
        LoyaltyProgram::create(['company_id' => $this->cid, 'name' => 'Partenaires SOGEREF Entreprises', 'segment' => 'b2b', 'points_per_unit' => .5, 'point_value' => .1, 'min_redeem_points' => 500, 'tiers' => [['name' => 'Partenaire', 'threshold' => 0, 'discount' => 0], ['name' => 'Privilège', 'threshold' => 15000, 'discount' => 4], ['name' => 'Stratégique', 'threshold' => 50000, 'discount' => 7]]]);
        $loyalty = app(LoyaltyService::class);
        foreach (['ali' => 2600, 'mwamba' => 9100, 'kapend' => 450, 'hotel' => 21000, 'banque' => 56000] as $client => $spent) {
            $loyalty->earn($loyalty->cardFor($clients[$client]), $spent);
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // 6. COMMANDES, ATELIER, PROJETS & CHANTIERS
    // ═════════════════════════════════════════════════════════════════════
    private function ordersAndOperations(array $c, array $p, array $hr, array $w, array $eq): array
    {
        $documents = app(CreateDocument::class);
        $workflow = app(OrderWorkflow::class);
        $cash = app(CashService::class);
        $manufacturing = app(ManufacturingService::class);
        $account = FinancialAccount::where('company_id', $this->cid)->where('type', 'cash')->first();
        $session = $cash->open($account, (float) $account->opening_balance);

        $quote = function (Contact $contact, array $lines, int $daysAgo, string $notes = '') use ($documents) {
            return $documents->handle([
                'company_id' => $this->cid, 'contact_id' => $contact->id, 'created_by' => $this->users['commercial']->id, 'type' => DocumentType::Quote,
                'issue_date' => now()->subDays($daysAgo)->toDateString(), 'valid_until' => now()->subDays($daysAgo)->addDays(30)->toDateString(),
                'currency_code' => 'USD', 'exchange_rate' => 1, 'notes' => $notes, 'terms' => erp_setting('quote_terms'), 'template' => 'moderne',
            ], collect($lines)->map(fn ($l, $i) => ['product_id' => $l[0]->id, 'description' => $l[0]->name, 'quantity' => $l[1], 'unit' => $l[0]->unit, 'unit_price' => $l[2] ?? $l[0]->sale_price, 'tax_rate' => $l[3] ?? 16, 'position' => $i])->all());
        };
        $bank = FinancialAccount::where('company_id', $this->cid)->where('type', 'bank')->first();
        $advance = function ($order, string $target, float $payRatio = 1.0, array $launch = []) use ($workflow, $cash, $session, $bank) {
            $flow = ['devis', 'valide', 'facture', 'paye', 'launch'];
            foreach ($flow as $step) {
                if ($order->stage === $target || ($step === 'launch' && $target === 'paye')) {
                    break;
                }
                match ($step) {
                    'devis' => $workflow->validate($order),
                    'valide' => $workflow->invoice($order),
                    // Petits montants en caisse, gros acomptes B2B par virement bancaire.
                    'facture' => $order->invoice()->first()->total > 3000
                        ? tap($cash->receive($order->invoice()->first(), round($order->invoice()->first()->total * $payRatio, 2), 'bank_transfer', $bank, 'VIR-'.mt_rand(100000, 999999)), fn () => $workflow->syncPayment($order->fresh()))
                        : $cash->collect($session, $order->invoice()->first(), round($order->invoice()->first()->total * $payRatio, 2), 'cash'),
                    'paye' => $workflow->launch($order, $launch),
                    default => null,
                };
                $order->refresh();
            }

            return $order;
        };

        // CMD 1 — 40 chaises amovibles pour l'hôtel : en fabrication (acompte 60 %)
        $o1 = $workflow->openFromQuote($quote($c['hotel'], [[$p['CA-MLBN'], 40], [$p['SRV-TRANS'], 1]], 24, 'Livraison échelonnée selon la capacité de stockage. Fixation à la charge de SOGEREF Sarl.'), 'fabrication', 'Chaises amovibles velours — salle de banquet');
        $o1->update(['delivery_date' => now()->addDays(12), 'priority' => 'haute']);
        $advance($o1, 'fabrication', .6, ['responsible_id' => $hr['permanent'][7]->id]);
        $mo1 = $o1->fresh()->manufacturingOrder;
        $manufacturing->start($mo1);
        $mo1->update(['progress' => 55]);
        $mo1->operations()->orderBy('position')->limit(2)->update(['status' => 'done', 'actual_hours' => 5]);
        $mo1->operations()->orderBy('position')->skip(2)->limit(1)->first()?->update(['status' => 'in_progress']);

        // CMD 2 — Villa R+1 à Ngaliema : chantier en exécution
        $o2 = $workflow->openFromQuote($quote($c['mwamba'], [[$p['SRV-ETUDE'], 1], [$p['MAT-CIM-50'], 420], [$p['MAT-HA12'], 260], [$p['MAT-BLO-20'], 4800], [$p['MAT-SAB'], 38], [$p['MAT-GRA'], 26], [$p['MAT-TOL-BAC'], 95], [$p['SRV-MO-BTP'], 75]], 70, 'Construction d’une villa R+1 de 4 chambres, 2 salons, 3 salles d’eau — parcelle 20 × 25 m.'), 'construction', 'Construction villa R+1 — Ngaliema, Binza Pigeon');
        $o2->update(['delivery_date' => now()->addDays(95), 'priority' => 'normale']);
        $advance($o2, 'execution', .5, ['site_manager_id' => $hr['permanent'][8]->id, 'address' => 'Av. des Cocotiers n° 14, Binza Pigeon', 'city' => 'Ngaliema']);

        // CMD 3 — Mobilier de direction pour la banque : encaissé, prêt à lancer
        $o3 = $workflow->openFromQuote($quote($c['banque'], [[$p['BU-DIR-18'], 3], [$p['AR-HAU-2P'], 6], [$p['TB-REU-12'], 1], [$p['SRV-POSE'], 1]], 12), 'fabrication', 'Mobilier des bureaux de direction — siège BCK');
        $o3->update(['delivery_date' => now()->addDays(30)]);
        $advance($o3, 'paye', .5);

        // CMD 4 — Réhabilitation bureaux SCTP : facturé, en attente de paiement
        $o4 = $workflow->openFromQuote($quote($c['onatra'], [[$p['SRV-ETUDE'], 1], [$p['MAT-CAR-60'], 180], [$p['MAT-PEI-20'], 14], [$p['PO-ISO-83'], 12], [$p['SRV-MO-BTP'], 18]], 9, 'Réhabilitation de 6 bureaux : carrelage, peinture, remplacement des portes.'), 'construction', 'Réhabilitation des bureaux — Direction technique');
        $advance($o4, 'facture');

        // CMD 5 — Table de réunion clinique : devis validé
        $o5 = $workflow->openFromQuote($quote($c['clinique'], [[$p['TB-REU-12'], 1], [$p['CA-MLBN'], 12], [$p['SRV-TRANS'], 1]], 6), 'fabrication', 'Salle de conseil — table 12 places et chaises');
        $advance($o5, 'valide');

        // CMD 6 — Portes isoplanes : devis en attente
        $workflow->openFromQuote($quote($c['kitenge'], [[$p['PO-ISO-83'], 7], [$p['SRV-POSE'], 1]], 2), 'fabrication', 'Portes intérieures — villa Kintambo');
        $workflow->openFromQuote($quote($c['ecole'], [[$p['CA-MLBN'], 60, 92], [$p['SRV-TRANS'], 2]], 1, 'Tarif éducation : remise commerciale appliquée.'), 'fabrication', 'Chaises de la salle polyvalente');

        // CMD 7 — Vente simple de matériaux à Ir ALI : livrée et clôturée
        $o7 = $workflow->openFromQuote($quote($c['ali'], [[$p['MAT-CIM-50'], 40], [$p['MAT-HA10'], 30]], 16), 'vente', 'Matériaux — ciment et fer');
        $advance($o7, 'livre', 1.0);
        $workflow->close($o7->fresh());

        // CMD 8 — Lit capitonné : fabrication terminée, livré (solde à encaisser)
        $o8 = $workflow->openFromQuote($quote($c['kapend'], [[$p['LI-2P-160'], 1], [$p['SRV-TRANS'], 1]], 30), 'fabrication', 'Lit 2 places capitonné velours');
        $advance($o8, 'fabrication', .5, ['responsible_id' => $hr['permanent'][12]->id]);
        $mo8 = $o8->fresh()->manufacturingOrder;
        $manufacturing->start($mo8);
        $manufacturing->finish($mo8);
        $workflow->deliver($o8->fresh(), 'Livré et monté chez la cliente.');

        // Clôture de la session de caisse de démonstration.
        $cash->close($session, $cash->expected($session), 'Clôture de démonstration');

        // Chantier supplémentaire (hors commande) : clôture de l'entrepôt de Limete
        $site2 = Site::create(['company_id' => $this->cid, 'code' => app(SequenceService::class)->next('site'), 'name' => 'Clôture et portail — entrepôt de Limete', 'address' => '7e rue, Limete industriel', 'city' => 'Limete', 'description' => 'Mur de clôture de 180 m en blocs de 20 et portail coulissant métallique.', 'status' => 'in_progress', 'start_date' => now()->subDays(25), 'planned_end_date' => now()->addDays(20), 'budget' => 28000, 'progress' => 45, 'manager_id' => $hr['permanent'][14]->id]);
        $site1 = $o2->fresh()->site;
        $site1->update(['status' => 'in_progress', 'progress' => 32, 'start_date' => now()->subDays(40)]);

        // Magasin de chantier pour la villa
        Warehouse::create(['company_id' => $this->cid, 'code' => 'CH-NGL', 'name' => 'Magasin chantier Ngaliema', 'type' => 'chantier', 'site_id' => $site1->id, 'address' => $site1->address]);

        // Équipes de chantier
        $site1->members()->attach(collect(array_slice($hr['daily'], 0, 8))->mapWithKeys(fn ($e) => [$e->id => ['role' => $e->position?->title, 'joined_at' => now()->subDays(40)]])->all());
        $site1->members()->attach($hr['permanent'][14]->id, ['role' => 'Chef d’équipe', 'joined_at' => now()->subDays(40)]);
        $site2->members()->attach(collect(array_slice($hr['daily'], 7, 5))->mapWithKeys(fn ($e) => [$e->id => ['role' => $e->position?->title, 'joined_at' => now()->subDays(25)]])->all());

        // Matériel affecté
        foreach ([[$site1, 'EQ-BET-01'], [$site1, 'EQ-VIB-01'], [$site1, 'EQ-ECH-01'], [$site2, 'EQ-BET-02'], [$site2, 'EQ-DAM-01']] as [$site, $code]) {
            $site->equipmentAssignments()->create(['equipment_id' => $eq[$code]->id, 'assigned_at' => now()->subDays(mt_rand(10, 30))]);
            $eq[$code]->update(['status' => 'assigned', 'site_id' => $site->id]);
        }

        // Rapports journaliers & évaluations
        $works = ['Coulage de la dalle du rez-de-chaussée (zone A).', 'Élévation des murs en blocs de 20 — façade nord.', 'Ferraillage des poteaux du niveau R+1.', 'Pose des coffrages de la poutre de chaînage.', 'Réception de 120 sacs de ciment et contrôle qualité.', 'Décoffrage et cure du béton, nettoyage de la zone.'];
        foreach ([$site1, $site2] as $site) {
            foreach (range(1, 6) as $n) {
                SiteReport::create(['site_id' => $site->id, 'user_id' => $this->users['chantier']->id, 'report_date' => now()->subWeekdays($n * 2), 'weather' => ['ensoleille', 'nuageux', 'pluie'][mt_rand(0, 2)], 'workforce' => mt_rand(6, 11), 'progress' => max(5, $site->progress - $n * 4), 'work_done' => $works[($n + $site->id) % 6], 'issues' => $n === 3 ? 'Retard de livraison du gravier (fournisseur SOCIMAT) — 1 jour perdu.' : null, 'next_steps' => 'Poursuite selon le planning ; commande de fer HA12 à anticiper.']);
            }
            SiteEvaluation::create(['site_id' => $site->id, 'user_id' => $this->users['direction']->id, 'evaluated_at' => now()->subDays(7), 'scores' => ['qualite' => 4, 'delais' => $site->id === $site1->id ? 3 : 4, 'securite' => 4, 'budget' => 4, 'proprete' => 3], 'score' => $site->id === $site1->id ? 3.6 : 3.8, 'comment' => 'Bonne organisation ; renforcer le port des EPI et le rangement en fin de journée.']);
        }

        // Projet & tâches de la villa (déjà créé par le workflow)
        $project = $o2->fresh()->project;
        foreach ([['Plans d’exécution et permis', 'done'], ['Implantation et terrassement', 'done'], ['Fondations et soubassement', 'done'], ['Dalle du rez-de-chaussée', 'in_progress'], ['Élévation R+1', 'todo'], ['Charpente et toiture', 'todo'], ['Réseaux plomberie / électricité', 'todo'], ['Finitions et réception', 'todo']] as $i => [$title, $status]) {
            Task::create(['company_id' => $this->cid, 'project_id' => $project->id, 'assigned_to' => $this->users['chantier']->id, 'title' => $title, 'status' => $status, 'priority' => $i === 3 ? 'haute' : 'normale', 'due_date' => now()->addDays(($i - 3) * 12), 'estimated_hours' => [16, 24, 60, 48, 90, 50, 40, 36][$i], 'position' => $i]);
        }
        $project->update(['progress' => $project->computedProgress()]);

        $atelierProject = Project::create(['company_id' => $this->cid, 'contact_id' => $c['hotel']->id, 'sales_order_id' => $o1->id, 'manager_id' => $this->users['atelier']->id, 'code' => app(SequenceService::class)->next('project'), 'name' => 'Suivi fabrication — Hôtel Fleuve Congo', 'status' => 'active', 'start_date' => now()->subDays(18), 'end_date' => now()->addDays(12), 'budget' => $o1->total, 'color' => '#F5B50B']);
        foreach ([['Validation échantillon tissu', 'done'], ['Débit des accoudoirs Afrormosia', 'done'], ['Garnissage série 1 (20 chaises)', 'in_progress'], ['Garnissage série 2 (20 chaises)', 'todo'], ['Contrôle qualité et emballage', 'todo'], ['Livraison et fixation sur site', 'todo']] as $i => [$title, $status]) {
            Task::create(['company_id' => $this->cid, 'project_id' => $atelierProject->id, 'assigned_to' => $this->users['atelier']->id, 'title' => $title, 'status' => $status, 'due_date' => now()->addDays($i * 2 - 2), 'estimated_hours' => 8, 'position' => $i]);
        }
        $atelierProject->update(['progress' => $atelierProject->computedProgress()]);

        return [$site1, $site2];
    }

    // ═════════════════════════════════════════════════════════════════════
    // 7. PRÉSENCES & PAIE
    // ═════════════════════════════════════════════════════════════════════
    private function attendanceAndPayroll(array $hr, array $sites): void
    {
        $service = app(HrService::class);
        $statuses = ['present', 'present', 'present', 'present', 'present', 'present', 'late', 'half', 'absent'];
        foreach ($sites as $site) {
            for ($d = 1; $d <= 20; $d++) {
                $date = now()->subWeekdays($d);
                if ($date->isSunday() || $date->lt($site->start_date)) {
                    continue;
                }
                $sheet = $service->sheetFor($site, $date->toDateString());
                foreach ($sheet->entries as $entry) {
                    $status = $statuses[mt_rand(0, count($statuses) - 1)];
                    $entry->update(['status' => $status, 'check_in' => $status === 'late' ? '08:40' : '07:30', 'check_out' => $status === 'half' ? '12:00' : '16:30', 'overtime_hours' => mt_rand(0, 9) === 0 ? 2 : 0]);
                }
                if ($d > 1) {
                    $service->validateSheet($sheet);
                }
            }
        }

        $payroll = app(PayrollService::class);
        $seq = app(SequenceService::class);
        // Paie mensuelle du mois précédent : calculée, validée et payée.
        $previous = now()->subMonthNoOverflow();
        $monthly = PayrollPeriod::create(['company_id' => $this->cid, 'reference' => $seq->next('payroll'), 'name' => 'Salaires '.$previous->translatedFormat('F Y'), 'pay_type' => 'monthly', 'start_date' => $previous->copy()->startOfMonth(), 'end_date' => $previous->copy()->endOfMonth(), 'exchange_rate' => 2847]);
        $payroll->compute($monthly);
        $payroll->validate($monthly->fresh());
        $payroll->markPaid($monthly->fresh(), FinancialAccount::where('company_id', $this->cid)->where('type', 'bank')->value('id'));

        // Paie mensuelle du mois courant : brouillon calculé.
        $current = PayrollPeriod::create(['company_id' => $this->cid, 'reference' => $seq->next('payroll'), 'name' => 'Salaires '.now()->translatedFormat('F Y'), 'pay_type' => 'monthly', 'start_date' => now()->startOfMonth(), 'end_date' => now()->endOfMonth(), 'exchange_rate' => 2847]);
        $payroll->compute($current);

        // Journaliers : quinzaine par chantier (à partir des listes validées).
        foreach ($sites as $site) {
            $period = PayrollPeriod::create(['company_id' => $this->cid, 'reference' => $seq->next('payroll'), 'name' => 'Journaliers '.$site->name.' — quinzaine du '.now()->subDays(15)->format('d/m'), 'pay_type' => 'daily', 'site_id' => $site->id, 'start_date' => now()->subDays(15)->toDateString(), 'end_date' => now()->subDay()->toDateString(), 'exchange_rate' => 2847]);
            $payroll->compute($period);
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // 8. BONS (achat, décaissement, sortie matériel)
    // ═════════════════════════════════════════════════════════════════════
    private function vouchers(array $p, array $w, array $sites, array $hr, array $suppliers): void
    {
        $vouchers = app(VoucherService::class);
        $cashAccount = FinancialAccount::where('company_id', $this->cid)->where('type', 'cash')->value('id');
        [$site1, $site2] = $sites;

        // Sortie matière pour l'OF des chaises (générée par l'atelier), approuvée et exécutée.
        $mo = ManufacturingOrder::where('company_id', $this->cid)->where('status', 'in_progress')->first();
        if ($mo) {
            $v = app(ManufacturingService::class)->requestMaterials($mo);
            Auth::setUser($this->users['direction']);
            $vouchers->approve($v);
            Auth::setUser($this->users['magasin']);
            // Sortie partielle réaliste : seules les matières disponibles en atelier.
            $v->update(['warehouse_id' => $w['ATL']->id]);
            try {
                $vouchers->execute($v->fresh('lines'));
            } catch (\DomainException) {
            }
            Auth::setUser($this->users['master']);
        }

        // Sorties de matériaux vers le chantier de la villa
        $s1 = $vouchers->create('sortie', ['voucher_date' => now()->subDays(6)->toDateString(), 'warehouse_id' => $w['DEP']->id, 'site_id' => $site1->id, 'beneficiary' => 'Didier Ngoy — conducteur de travaux', 'purpose' => 'Approvisionnement dalle RDC — villa Ngaliema'], [
            ['product_id' => $p['MAT-CIM-50']->id, 'description' => $p['MAT-CIM-50']->name, 'quantity' => 120, 'unit' => 'sac', 'unit_price' => 11],
            ['product_id' => $p['MAT-HA12']->id, 'description' => $p['MAT-HA12']->name, 'quantity' => 80, 'unit' => 'barre', 'unit_price' => 12.5],
            ['product_id' => $p['MAT-SAB']->id, 'description' => $p['MAT-SAB']->name, 'quantity' => 12, 'unit' => 'm³', 'unit_price' => 22],
        ], submit: true);
        $vouchers->approve($s1);
        $vouchers->execute($s1);
        $s2 = $vouchers->create('sortie', ['voucher_date' => now()->toDateString(), 'warehouse_id' => $w['DEP']->id, 'site_id' => $site2->id, 'beneficiary' => 'Cédric Lumbala — chef d’équipe', 'purpose' => 'Blocs et ciment — mur de clôture tronçon 3'], [
            ['product_id' => $p['MAT-BLO-20']->id, 'description' => $p['MAT-BLO-20']->name, 'quantity' => 900, 'unit' => 'pièce', 'unit_price' => .85],
            ['product_id' => $p['MAT-CIM-50']->id, 'description' => $p['MAT-CIM-50']->name, 'quantity' => 45, 'unit' => 'sac', 'unit_price' => 11],
        ], submit: true);

        // Décaissements
        $d1 = $vouchers->create('decaissement', ['voucher_date' => now()->subDays(3)->toDateString(), 'site_id' => $site1->id, 'employee_id' => $hr['permanent'][8]->id, 'beneficiary' => 'Didier Ngoy', 'purpose' => 'Location d’un camion-benne pour évacuation des déblais', 'amount' => 180, 'payment_method' => 'cash', 'financial_account_id' => $cashAccount], [['description' => 'Camion-benne 10 T — 1 journée', 'quantity' => 1, 'unit' => 'jour', 'unit_price' => 180]], submit: true);
        $vouchers->approve($d1);
        $vouchers->execute($d1, $cashAccount);
        $vouchers->create('decaissement', ['voucher_date' => now()->toDateString(), 'site_id' => $site2->id, 'beneficiary' => 'Cédric Lumbala', 'purpose' => 'Carburant groupe électrogène et petits consommables', 'amount' => 95, 'payment_method' => 'cash'], [['description' => 'Gasoil 40 L', 'quantity' => 40, 'unit' => 'litre', 'unit_price' => 1.8], ['description' => 'Disques de meulage', 'quantity' => 5, 'unit' => 'pièce', 'unit_price' => 4.6]], submit: true);
        $vouchers->create('decaissement', ['voucher_date' => now()->toDateString(), 'employee_id' => $hr['permanent'][11]->id, 'beneficiary' => 'Fiston Mbala', 'purpose' => 'Avance sur frais de mission — prise de mesures à Kinkole', 'amount' => 60, 'payment_method' => 'cash'], [['description' => 'Transport aller-retour + repas', 'quantity' => 1, 'unit' => 'forfait', 'unit_price' => 60]]);

        // Bons d'achat
        $a1 = $vouchers->create('achat', ['voucher_date' => now()->subDays(2)->toDateString(), 'contact_id' => $suppliers['FRN-00004']->id, 'warehouse_id' => $w['ATL']->id, 'purpose' => 'Réassort tissu et mousse pour la commande de l’hôtel'], [
            ['product_id' => $p['MAT-VEL-RH']->id, 'description' => $p['MAT-VEL-RH']->name, 'quantity' => 40, 'unit' => 'm', 'unit_price' => 14],
            ['product_id' => $p['MAT-MOU-35']->id, 'description' => $p['MAT-MOU-35']->name, 'quantity' => 20, 'unit' => 'm²', 'unit_price' => 9],
        ], submit: true);
        $vouchers->approve($a1);
        $vouchers->create('achat', ['voucher_date' => now()->toDateString(), 'contact_id' => $suppliers['FRN-00002']->id, 'warehouse_id' => $w['ATL']->id, 'purpose' => 'Outillage : lames de scie et forets'], [['description' => 'Lames de scie circulaire Ø 250', 'quantity' => 4, 'unit' => 'pièce', 'unit_price' => 28], ['description' => 'Coffret de forets bois', 'quantity' => 2, 'unit' => 'lot', 'unit_price' => 35]]);
    }

    // ═════════════════════════════════════════════════════════════════════
    // 9. POINT DE VENTE, LOCATION & ACHATS
    // ═════════════════════════════════════════════════════════════════════
    private function commerce(array $c, array $p, array $eq): void
    {
        // Ventes comptoir du showroom (session de la caissière, clôturée hier)
        Auth::setUser($this->users['caisse']);
        $cash = app(CashService::class);
        $pos = app(PosService::class);
        $session = $cash->open(FinancialAccount::where('company_id', $this->cid)->where('name', 'like', 'Caisse showroom%')->first(), 650, 'pos');
        $pos->checkout($session, [['product_id' => $p['CA-MLBN']->id, 'quantity' => 4, 'unit_price' => 100]], $c['mwamba'], 'cash', 500);
        $pos->checkout($session, [['product_id' => $p['PO-ISO-83']->id, 'quantity' => 2, 'unit_price' => 185]], null, 'mobile_money', 0);
        $pos->checkout($session, [['product_id' => $p['AR-HAU-2P']->id, 'quantity' => 1, 'unit_price' => 520], ['product_id' => $p['CA-MLBN']->id, 'quantity' => 2, 'unit_price' => 100]], $c['ali'], 'card', 0);
        $cash->close($session, $cash->expected($session) - 5, 'Écart de 5 $ constaté (rendu monnaie).');
        Auth::setUser($this->users['master']);

        // Location de matériel
        $rentals = app(RentalService::class);
        $r1 = $rentals->create(['contact_id' => $c['ali']->id, 'start_date' => now()->subDays(3)->toDateString(), 'end_date' => now()->addDays(4)->toDateString(), 'deposit' => 300, 'notes' => 'Chantier personnel du client — Gombe'], [$eq['EQ-ECH-02']->id, $eq['EQ-NIV-01']->id]);
        $rentals->start($r1);
        $r2 = $rentals->create(['contact_id' => $c['ecole']->id, 'start_date' => now()->subDays(14)->toDateString(), 'end_date' => now()->subDays(10)->toDateString(), 'deposit' => 200], [$eq['EQ-CMP-01']->id]);
        $rentals->start($r2);
        $rentals->return($r2);
        $rentals->invoice($r2->fresh('lines.equipment'));
        $rentals->create(['contact_id' => $c['kitenge']->id, 'start_date' => now()->addDays(5)->toDateString(), 'end_date' => now()->addDays(7)->toDateString(), 'deposit' => 150], [$eq['EQ-NIV-01']->id]);

        // Réapprovisionnement automatique + réception d'une commande fournisseur
        $replenishment = app(ReplenishmentService::class);
        $orders = $replenishment->generate();
        $first = $orders->first();
        if ($first) {
            $replenishment->receive($first->fresh('items.product'), Warehouse::where('company_id', $this->cid)->where('type', 'central')->first(), $first->items->mapWithKeys(fn ($i) => [$i->id => $i->quantity])->all());
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // 10. SERVICES : RENDEZ-VOUS, PLANIFICATION, RÉCEPTION, TEMPS
    // ═════════════════════════════════════════════════════════════════════
    private function services(array $c, array $hr, array $sites): void
    {
        foreach ([
            [0, '09:00', 60, 'Présentation du catalogue mobilier', 'showroom', 'hotel', 'commercial', 'confirmed'],
            [0, '11:30', 90, 'Visite de chantier avec le client', 'visite_chantier', 'mwamba', 'chantier', 'scheduled'],
            [0, '15:00', 45, 'Prise de mesures — cuisine sur mesure', 'mesure', 'kapend', 'atelier', 'scheduled'],
            [1, '10:00', 60, 'Négociation aménagement agence Matete', 'reunion', 'banque', 'direction', 'confirmed'],
            [2, '14:00', 120, 'Livraison et montage — lit capitonné', 'livraison', 'kapend', 'atelier', 'scheduled'],
            [3, '09:30', 60, 'Réunion de lancement — clinique', 'reunion', 'clinique', 'commercial', 'scheduled'],
            [-1, '10:00', 60, 'Présentation des échantillons de tissus', 'showroom', 'hotel', 'commercial', 'done'],
            [-2, '16:00', 30, 'Rendez-vous non honoré', 'showroom', 'kitenge', 'commercial', 'no_show'],
        ] as [$day, $time, $minutes, $title, $type, $client, $user, $status]) {
            $start = Carbon::parse(now()->addDays($day)->toDateString().' '.$time);
            Appointment::create(['company_id' => $this->cid, 'contact_id' => $c[$client]->id, 'user_id' => $this->users[$user]->id, 'title' => $title, 'type' => $type, 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes($minutes), 'location' => $type === 'visite_chantier' ? 'Chantier Ngaliema' : ($type === 'showroom' ? 'Showroom Gombe' : 'Siège SOGEREF'), 'status' => $status]);
        }

        // Planification de la semaine
        foreach (range(0, 5) as $d) {
            $date = now()->startOfWeek()->addDays($d);
            foreach (array_slice($hr['daily'], 0, 6) as $e) {
                PlanningSlot::create(['company_id' => $this->cid, 'employee_id' => $e->id, 'site_id' => $sites[0]->id, 'slot_date' => $date, 'shift' => 'journee', 'role' => $e->position?->title]);
            }
            foreach (array_slice($hr['permanent'], 11, 3) as $e) {
                PlanningSlot::create(['company_id' => $this->cid, 'employee_id' => $e->id, 'manufacturing_order_id' => ManufacturingOrder::where('company_id', $this->cid)->where('status', 'in_progress')->value('id'), 'slot_date' => $date, 'shift' => $d === 5 ? 'matin' : 'journee', 'role' => 'Atelier']);
            }
        }

        // Réception : visiteurs du jour et courrier
        foreach ([['Me Blaise Kabongo', 'Cabinet Kabongo & Associés', 'Signature du contrat de bail', 'direction', 150, null], ['Mme Rose Ilunga', 'Hôtel Fleuve Congo', 'Choix des finitions — chaises', 'commercial', 90, 30], ['M. Kevin Tshala', 'SOCIMAT', 'Livraison de gravier — bon de livraison', 'magasin', 40, 15], ['Mlle Divine Ngalula', null, 'Dépôt de candidature — poste de menuisière', 'rh', 20, null]] as $i => [$name, $org, $purpose, $host, $minutesAgo, $stayed]) {
            Visitor::create(['company_id' => $this->cid, 'host_id' => $this->users[$host]->id, 'full_name' => $name, 'organization' => $org, 'phone' => '+243 8'.mt_rand(10, 99).' '.mt_rand(100, 999).' '.mt_rand(100, 999), 'id_document' => 'Carte d’électeur', 'purpose' => $purpose, 'badge_number' => 'V-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), 'checked_in_at' => now()->subMinutes($minutesAgo), 'checked_out_at' => $stayed ? now()->subMinutes($minutesAgo - $stayed) : null]);
        }
        $seq = app(SequenceService::class);
        foreach ([['in', 'Direction générale des impôts', 'Avis de contrôle fiscal — exercice 2025', 'ADM', 'forwarded'], ['in', 'Hôtel Fleuve Congo', 'Bon de commande n° HFC-2026-118', 'COM', 'processed'], ['out', 'Ministère des Infrastructures', 'Offre technique — réhabilitation bâtiment B', 'COM', 'registered'], ['in', 'CNSS — Direction provinciale', 'Relevé des cotisations du trimestre', 'RH', 'registered']] as $i => [$direction, $who, $subject, $dept, $status]) {
            MailRecord::create(['company_id' => $this->cid, 'direction' => $direction, 'reference' => $seq->next($direction === 'in' ? 'mail_in' : 'mail_out'), 'record_date' => now()->subDays($i), 'correspondent' => $who, 'subject' => $subject, 'department_id' => Department::where('code', $dept)->value('id'), 'handled_by' => $this->users['accueil']->id, 'status' => $status]);
        }

        // Feuilles de temps
        $projects = Project::where('company_id', $this->cid)->get();
        foreach (['chantier', 'atelier', 'commercial'] as $login) {
            foreach (range(0, 6) as $d) {
                $date = now()->subWeekdays($d);
                $project = $projects[$d % $projects->count()];
                Timesheet::create(['company_id' => $this->cid, 'user_id' => $this->users[$login]->id, 'project_id' => $project->id, 'task_id' => $project->tasks()->inRandomOrder()->value('id'), 'work_date' => $date, 'hours' => [8, 7.5, 6, 8, 4][mt_rand(0, 4)], 'description' => 'Suivi et exécution des travaux', 'billable' => $login !== 'commercial', 'status' => $d > 2 ? 'approved' : 'submitted']);
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // 11. FINANCE : NOTES DE FRAIS, DÉPENSES, MESSAGERIE
    // ═════════════════════════════════════════════════════════════════════
    private function finance(array $hr, array $sites): void
    {
        $seq = app(SequenceService::class);
        $service = app(ExpenseClaimService::class);
        foreach ([
            [$hr['permanent'][8], 'chantier', 'Frais de chantier — semaine 38', $sites[0]->id, [['transport', 'Taxi-bus aller/retour chantier (5 jours)', 25], ['repas', 'Repas équipe — coulage dalle', 48], ['chantier', 'Petits consommables (fil à ligature)', 17]], 'paid'],
            [$hr['permanent'][4], 'commercial', 'Déplacements clients — septembre', null, [['carburant', 'Carburant véhicule de service', 60], ['communication', 'Recharge unités téléphone', 15]], 'submitted'],
            [$hr['permanent'][7], 'atelier', 'Achats urgents atelier', null, [['fournitures', 'Papier abrasif et colle contact', 34]], 'approved'],
        ] as [$employee, $login, $title, $siteId, $lines, $status]) {
            $claim = ExpenseClaim::create(['company_id' => $this->cid, 'employee_id' => $employee->id, 'user_id' => $this->users[$login]->id, 'site_id' => $siteId, 'number' => $seq->next('expense_claim'), 'title' => $title]);
            foreach ($lines as $i => [$cat, $desc, $amount]) {
                $claim->lines()->create(['spent_at' => now()->subDays(8 - $i), 'category' => $cat, 'description' => $desc, 'amount' => $amount]);
            }
            $service->submit($claim);
            if (in_array($status, ['approved', 'paid'], true)) {
                $service->decide($claim, true);
            }
            if ($status === 'paid') {
                $service->reimburse($claim->fresh(), FinancialAccount::where('company_id', $this->cid)->where('type', 'cash')->value('id'));
            }
        }

        $accounting = app(SynchronizeAccounting::class);
        $bank = FinancialAccount::where('company_id', $this->cid)->where('type', 'bank')->first();
        foreach ([['Loyer atelier de Limete — septembre', 1800, 12, '622'], ['Électricité SNEL — atelier', 420, 9, '605'], ['Assurance flotte véhicules', 650, 20, '625'], ['Internet fibre — siège', 180, 5, '628']] as $i => [$label, $amount, $days, $code]) {
            $expense = Expense::create(['company_id' => $this->cid, 'created_by' => $this->users['compta']->id, 'financial_account_id' => $bank->id, 'reference' => sprintf('DEP-%05d', $i + 1), 'account_code' => $code, 'description' => $label, 'amount' => $amount, 'tax_amount' => 0, 'currency_code' => 'USD', 'exchange_rate' => 1, 'expense_date' => now()->subDays($days), 'status' => 'approved', 'payment_method' => 'bank_transfer']);
            $accounting->expense($expense);
        }

        $conversation = Conversation::create(['company_id' => $this->cid, 'created_by' => $this->users['direction']->id, 'last_message_at' => now()]);
        $conversation->participants()->attach([$this->users['direction']->id, $this->users['atelier']->id, $this->users['master']->id]);
        Message::create(['conversation_id' => $conversation->id, 'user_id' => $this->users['direction']->id, 'body' => 'Joseph, où en est la série des chaises pour l’hôtel ? Le client souhaite une première livraison vendredi.']);
        Message::create(['conversation_id' => $conversation->id, 'user_id' => $this->users['atelier']->id, 'body' => 'Bonjour DG, 20 chaises sont garnies, le vernis sèche. Livraison de la première série possible jeudi après-midi.']);
    }
}
