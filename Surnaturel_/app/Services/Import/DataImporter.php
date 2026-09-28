<?php

namespace App\Services\Import;

use App\Enums\AccountType;
use App\Enums\ClientType;
use App\Enums\EmploymentStatus;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Exports\TableExport;
use App\Exports\WorkbookExport;
use App\Models\Account;
use App\Models\Client;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Import de données depuis Excel / CSV : modèle téléchargeable, validation ligne par ligne, aperçu,
 * puis création ou mise à jour (clé métier : téléphone, référence, code, matricule…).
 */
class DataImporter
{
    /**
     * Colonnes : clé => [libellé, règles, exemple, aide].
     *
     * @var array<string, array{label: string, permission: string, key: string, columns: array<string, array{0: string, 1: string, 2: string, 3?: string}>}>
     */
    public const TYPES = [
        'clients' => ['label' => 'Clients', 'permission' => 'clients.create', 'key' => 'Téléphone, sinon e-mail, sinon nom', 'columns' => [
            'nom' => ['Nom du client', 'required|string|max:255', 'Jean Mukendi'],
            'societe' => ['Société', 'nullable|string|max:255', 'Mukendi & Fils SARL'],
            'type' => ['Type', 'nullable|string', 'entreprise', 'particulier, entreprise, administration, ong, revendeur'],
            'telephone' => ['Téléphone', 'nullable|string|max:30', '+243 810 000 000'],
            'whatsapp' => ['WhatsApp', 'nullable|string|max:30', '+243 810 000 000'],
            'email' => ['E-mail', 'nullable|email|max:255', 'contact@mukendi.cd'],
            'adresse' => ['Adresse', 'nullable|string|max:255', 'Av. du Commerce 12, Gombe'],
            'ville' => ['Ville', 'nullable|string|max:100', 'Kinshasa'],
            'nif' => ['NIF', 'nullable|string|max:50', 'A1234567B'],
        ]],
        'products' => ['label' => 'Articles', 'permission' => 'products.create', 'key' => 'Référence (SKU)', 'columns' => [
            'reference' => ['Référence', 'required|string|max:50', 'PAP-A4-80'],
            'nom' => ['Désignation', 'required|string|max:255', 'Rame papier A4 80 g'],
            'type' => ['Type', 'nullable|string', 'papier', 'service, matiere, consommable, papier, encre, accessoire, produit_fini, marchandise'],
            'categorie' => ['Catégorie', 'nullable|string|max:100', 'Papiers & supports', 'Créée si elle n’existe pas'],
            'unite' => ['Unité', 'nullable|string|max:10', 'rame'],
            'prix_achat' => ['Prix d’achat', 'nullable|numeric|min:0', '3.5'],
            'prix_vente' => ['Prix de vente', 'nullable|numeric|min:0', '5'],
            'tva' => ['TVA %', 'nullable|numeric|between:0,100', '16'],
            'stock_min' => ['Stock minimum', 'nullable|numeric|min:0', '10'],
            'code_barres' => ['Code-barres', 'nullable|string|max:64', '6001234567890'],
            'vente_caisse' => ['Vendu en caisse', 'nullable|string', 'oui', 'oui / non'],
        ]],
        'suppliers' => ['label' => 'Fournisseurs', 'permission' => 'suppliers.create', 'key' => 'Code, sinon nom', 'columns' => [
            'code' => ['Code', 'nullable|string|max:30', 'FRN-0100', 'Généré si vide'],
            'nom' => ['Raison sociale', 'required|string|max:255', 'Papeterie Centrale'],
            'contact' => ['Contact', 'nullable|string|max:255', 'M. Kabasele'],
            'telephone' => ['Téléphone', 'nullable|string|max:30', '+243 820 000 000'],
            'email' => ['E-mail', 'nullable|email|max:255', 'ventes@papeterie.cd'],
            'adresse' => ['Adresse', 'nullable|string|max:255', 'Bd du 30 Juin'],
            'ville' => ['Ville', 'nullable|string|max:100', 'Kinshasa'],
            'nif' => ['NIF', 'nullable|string|max:50', ''],
            'delai_paiement' => ['Délai de paiement (jours)', 'nullable|integer|min:0|max:365', '30'],
        ]],
        'employees' => ['label' => 'Employés', 'permission' => 'employees.create', 'key' => 'Matricule', 'columns' => [
            'matricule' => ['Matricule', 'required|string|max:30', 'EMP-00100'],
            'prenom' => ['Prénom', 'required|string|max:100', 'Grâce'],
            'nom' => ['Nom', 'required|string|max:100', 'Lukusa'],
            'sexe' => ['Sexe', 'nullable|in:M,F,m,f', 'F', 'M ou F'],
            'date_naissance' => ['Date de naissance', 'nullable|date', '1994-05-12', 'AAAA-MM-JJ'],
            'telephone' => ['Téléphone', 'nullable|string|max:30', '+243 830 000 000'],
            'email' => ['E-mail', 'nullable|email|max:255', 'g.lukusa@surnaturel.cd'],
            'fonction' => ['Fonction', 'nullable|string|max:100', 'Graphiste'],
            'departement' => ['Département (code ou nom)', 'nullable|string|max:100', 'GRA'],
            'date_embauche' => ['Date d’embauche', 'nullable|date', '2025-01-06'],
            'statut' => ['Statut', 'nullable|string', 'permanent', 'permanent, journalier, contrat, prestataire, stagiaire'],
            'salaire_base' => ['Salaire de base', 'nullable|numeric|min:0', '450'],
        ]],
        'departments' => ['label' => 'Départements', 'permission' => 'departments.create', 'key' => 'Code', 'columns' => [
            'code' => ['Code', 'required|string|max:20', 'MKT'],
            'nom' => ['Nom', 'required|string|max:255', 'Marketing'],
        ]],
        'stock' => ['label' => 'Inventaire (stock initial / comptage)', 'permission' => 'inventory.create', 'key' => 'Référence + entrepôt : le stock est ajusté à la quantité comptée', 'columns' => [
            'reference' => ['Référence article', 'required|string|max:50', 'PAP-A4-80'],
            'entrepot' => ['Entrepôt (code ou nom)', 'required|string|max:100', 'ENT-CEN'],
            'quantite' => ['Quantité comptée', 'required|numeric|min:0', '120'],
            'cout_unitaire' => ['Coût unitaire', 'nullable|numeric|min:0', '3.5', 'Facultatif'],
        ]],
        'accounts' => ['label' => 'Plan comptable', 'permission' => 'accounting.update', 'key' => 'Numéro de compte', 'columns' => [
            'code' => ['Numéro de compte', 'required|string|max:20', '6053'],
            'intitule' => ['Intitulé', 'required|string|max:255', 'Fournitures de bureau'],
            'type' => ['Type', 'nullable|string', 'charge', 'actif, passif, capitaux, produit, charge (déduit de la classe si vide)'],
        ]],
    ];

    /**
     * Modèle Excel : feuille de saisie (en-têtes + exemple) et feuille d'aide.
     */
    public function template(string $type): WorkbookExport
    {
        $definition = self::TYPES[$type];
        $columns = $definition['columns'];

        return new WorkbookExport([
            new TableExport(array_keys($columns), collect([array_values(array_map(fn (array $c) => $c[2], $columns))]), $definition['label']),
            new TableExport(['Colonne', 'Description', 'Obligatoire', 'Valeurs acceptées'], collect($columns)->map(fn (array $c, string $key) => [$key, $c[0], str_contains($c[1], 'required') ? 'Oui' : 'Non', $c[3] ?? ''])->values(), 'Aide'),
        ]);
    }

    /**
     * Lit un fichier (xlsx, xls, csv) : première feuille, première ligne = en-têtes.
     *
     * @return list<array<string, mixed>>
     */
    public function read(string $type, string $path): array
    {
        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array): void {}
        }, $path);

        $rows = $sheets[0] ?? [];
        if ($rows === []) {
            return [];
        }

        $headings = array_map(fn ($heading) => $this->normalizeHeading((string) $heading), array_shift($rows));
        $columns = self::TYPES[$type]['columns'];
        $known = array_keys($columns);

        return collect($rows)
            ->filter(fn (array $row) => collect($row)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty())
            ->map(function (array $row) use ($headings, $known, $columns): array {
                $assoc = [];
                foreach ($headings as $index => $heading) {
                    if (in_array($heading, $known, true)) {
                        $assoc[$heading] = $this->cast($row[$index] ?? null, $columns[$heading][1]);
                    }
                }

                return $assoc;
            })->values()->all();
    }

    /**
     * Valeurs Excel : dates au format numérique Excel, nombres lus comme flottants (téléphones, codes…).
     */
    private function cast(mixed $value, string $rules): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_contains($rules, 'date') && is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        if (str_contains($rules, 'numeric') || str_contains($rules, 'integer')) {
            return is_string($value) ? str_replace([' ', ','], ['', '.'], trim($value)) : $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return is_string($value) ? trim($value) : (string) $value;
    }

    private function normalizeHeading(string $heading): string
    {
        $slug = Str::slug(Str::ascii($heading), '_');

        return match ($slug) {
            'name', 'designation', 'raison_sociale', 'nom_du_client' => 'nom',
            'phone', 'tel', 'telephone_1' => 'telephone',
            'sku', 'ref', 'reference_article' => 'reference',
            'qte', 'quantity', 'quantite_comptee' => 'quantite',
            'entrepot_code_ou_nom', 'warehouse', 'depot' => 'entrepot',
            'departement_code_ou_nom' => 'departement',
            'numero_de_compte', 'compte' => 'code',
            default => $slug,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<int, list<string>> erreurs par index de ligne
     */
    public function validate(string $type, array $rows): array
    {
        $rules = array_map(fn (array $c) => $c[1], self::TYPES[$type]['columns']);
        $errors = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, $rules, [], array_map(fn (array $c) => $c[0], self::TYPES[$type]['columns']));
            $messages = $validator->errors()->all();

            if ($type === 'stock' && ! $validator->errors()->any()) {
                if (! Product::query()->where('sku', $row['reference'])->exists()) {
                    $messages[] = "Article « {$row['reference']} » introuvable.";
                }
                if (! $this->findWarehouse((string) $row['entrepot'])) {
                    $messages[] = "Entrepôt « {$row['entrepot']} » introuvable.";
                }
            }

            if ($messages) {
                $errors[$index] = $messages;
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(string $type, array $rows, User $user): array
    {
        $errors = $this->validate($type, $rows);
        $result = ['created' => 0, 'updated' => 0, 'skipped' => count($errors)];

        DB::transaction(function () use ($type, $rows, $user, $errors, &$result): void {
            foreach ($rows as $index => $row) {
                if (isset($errors[$index])) {
                    continue;
                }
                $created = match ($type) {
                    'clients' => $this->importClient($row, $user),
                    'products' => $this->importProduct($row),
                    'suppliers' => $this->importSupplier($row),
                    'employees' => $this->importEmployee($row, $user),
                    'departments' => $this->importDepartment($row),
                    'stock' => $this->importStock($row, $user),
                    'accounts' => $this->importAccount($row),
                };
                $result[$created ? 'created' : 'updated']++;
            }
        });

        return $result;
    }

    private function importClient(array $row, User $user): bool
    {
        $phone = $row['telephone'] ?? null;
        $email = $row['email'] ?? null;
        $client = Client::query()
            ->when($phone, fn ($q) => $q->where('phone', $phone), fn ($q) => $email ? $q->where('email', $email) : $q->where('name', $row['nom']))
            ->first();

        $data = array_filter([
            'name' => $row['nom'],
            'company_name' => $row['societe'] ?? null,
            'type' => $this->clientType($row['type'] ?? null, filled($row['societe'] ?? null)),
            'phone' => $phone,
            'whatsapp' => $row['whatsapp'] ?? null,
            'email' => $email,
            'address' => $row['adresse'] ?? null,
            'city' => $row['ville'] ?? null,
            'tax_id' => $row['nif'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($client) {
            $client->update($data);

            return false;
        }

        Client::query()->create($data + [
            'number' => app(NumberGenerator::class)->next(NumberGenerator::Client),
            'segment' => in_array($data['type'], [ClientType::Individual], true) ? 'b2c' : 'b2b',
            'site_id' => $user->default_site_id,
            'sales_rep_id' => $user->id,
        ]);

        return true;
    }

    private function clientType(?string $value, bool $hasCompany): ClientType
    {
        $value = Str::slug(Str::ascii((string) $value), '_');

        return match ($value) {
            'entreprise', 'societe', 'company' => ClientType::Company,
            'administration', 'etat' => ClientType::Administration,
            'ong', 'ngo', 'association' => ClientType::Ngo,
            'revendeur', 'reseller' => ClientType::Reseller,
            'particulier', 'individual' => ClientType::Individual,
            default => $hasCompany ? ClientType::Company : ClientType::Individual,
        };
    }

    private function importProduct(array $row): bool
    {
        $product = Product::query()->withTrashed()->where('sku', $row['reference'])->first();
        $category = filled($row['categorie'] ?? null) ? ProductCategory::query()->firstOrCreate(['name' => $row['categorie']]) : null;
        $unit = filled($row['unite'] ?? null) ? Unit::query()->firstOrCreate(['symbol' => $row['unite']], ['name' => $row['unite']]) : null;

        $data = array_filter([
            'name' => $row['nom'],
            'type' => $this->productType($row['type'] ?? null),
            'product_category_id' => $category?->id,
            'unit_id' => $unit?->id,
            'cost_price' => $row['prix_achat'] ?? null,
            'sale_price' => $row['prix_vente'] ?? null,
            'tax_rate' => $row['tva'] ?? null,
            'min_stock' => $row['stock_min'] ?? null,
            'barcode' => filled($row['code_barres'] ?? null) ? (string) $row['code_barres'] : null,
            'is_sellable_pos' => filled($row['vente_caisse'] ?? null) ? $this->bool($row['vente_caisse']) : null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($product) {
            $product->restore();
            $product->update($data);

            return false;
        }

        Product::query()->create($data + ['sku' => $row['reference'], 'is_stockable' => ($data['type'] ?? null) !== ProductType::Service]);

        return true;
    }

    private function productType(?string $value): ProductType
    {
        return match (Str::slug(Str::ascii((string) $value), '_')) {
            'service', 'prestation' => ProductType::Service,
            'matiere', 'matiere_premiere', 'raw_material' => ProductType::RawMaterial,
            'consommable', 'consumable' => ProductType::Consumable,
            'encre', 'ink', 'toner' => ProductType::Ink,
            'accessoire', 'accessory' => ProductType::Accessory,
            'produit_fini', 'finished_good' => ProductType::FinishedGood,
            'papier', 'paper' => ProductType::Paper,
            default => ProductType::Merchandise,
        };
    }

    private function importSupplier(array $row): bool
    {
        $supplier = Supplier::query()->withTrashed()
            ->when(filled($row['code'] ?? null), fn ($q) => $q->where('code', $row['code']), fn ($q) => $q->where('name', $row['nom']))
            ->first();

        $data = array_filter([
            'name' => $row['nom'],
            'contact_name' => $row['contact'] ?? null,
            'phone' => $row['telephone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['adresse'] ?? null,
            'city' => $row['ville'] ?? null,
            'tax_id' => $row['nif'] ?? null,
            'payment_terms_days' => $row['delai_paiement'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($supplier) {
            $supplier->restore();
            $supplier->update($data);

            return false;
        }

        Supplier::query()->create($data + ['code' => $row['code'] ?: 'FRN-'.str_pad((string) (Supplier::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT)]);

        return true;
    }

    private function importEmployee(array $row, User $user): bool
    {
        $employee = Employee::query()->withTrashed()->where('matricule', $row['matricule'])->first();
        $department = filled($row['departement'] ?? null)
            ? Department::query()->where('code', $row['departement'])->orWhere('name', $row['departement'])->first()
            : null;

        $data = array_filter([
            'first_name' => $row['prenom'],
            'last_name' => $row['nom'],
            'gender' => filled($row['sexe'] ?? null) ? strtoupper((string) $row['sexe']) : null,
            'birth_date' => $row['date_naissance'] ?? null,
            'phone' => $row['telephone'] ?? null,
            'email' => $row['email'] ?? null,
            'job_title' => $row['fonction'] ?? null,
            'department_id' => $department?->id,
            'hired_at' => $row['date_embauche'] ?? null,
            'employment_status' => $this->employmentStatus($row['statut'] ?? null),
            'base_salary' => filled($row['salaire_base'] ?? null) ? (string) $row['salaire_base'] : null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($employee) {
            $employee->restore();
            $employee->update($data);

            return false;
        }

        Employee::query()->create($data + ['matricule' => $row['matricule'], 'site_id' => $user->default_site_id, 'salary_type' => 'monthly', 'is_active' => true]);

        return true;
    }

    private function employmentStatus(?string $value): EmploymentStatus
    {
        return match (Str::slug(Str::ascii((string) $value), '_')) {
            'journalier', 'daily' => EmploymentStatus::Daily,
            'contrat', 'cdd', 'contract' => EmploymentStatus::Contract,
            'prestataire', 'contractor', 'consultant' => EmploymentStatus::Contractor,
            'stagiaire', 'intern' => EmploymentStatus::Intern,
            default => EmploymentStatus::Permanent,
        };
    }

    private function importDepartment(array $row): bool
    {
        $department = Department::query()->where('code', $row['code'])->first();
        if ($department) {
            $department->update(['name' => $row['nom']]);

            return false;
        }
        Department::query()->create(['code' => $row['code'], 'name' => $row['nom']]);

        return true;
    }

    /**
     * Inventaire : le stock de l'article dans l'entrepôt est ajusté à la quantité comptée (mouvement « Ajustement » tracé).
     */
    private function importStock(array $row, User $user): bool
    {
        $product = Product::query()->where('sku', $row['reference'])->firstOrFail();
        $warehouse = $this->findWarehouse((string) $row['entrepot']);
        $current = $product->stockIn($warehouse);
        $delta = round((float) $row['quantite'] - $current, 3);

        if (filled($row['cout_unitaire'] ?? null)) {
            $product->update(['cost_price' => $row['cout_unitaire']]);
        }

        if (abs($delta) > 0.0005) {
            app(StockService::class)->move($product, $warehouse, StockMovementType::Adjustment, $delta, null, 'Inventaire importé (comptage '.number_format((float) $row['quantite'], 3, ',', ' ').')', $user);
        }

        return $current == 0.0;
    }

    private function findWarehouse(string $value): ?Warehouse
    {
        return Warehouse::query()->where('code', $value)->orWhere('name', $value)->first();
    }

    private function importAccount(array $row): bool
    {
        $code = (string) $row['code'];
        $type = match (Str::slug(Str::ascii((string) ($row['type'] ?? '')), '_')) {
            'actif', 'asset' => AccountType::Asset,
            'passif', 'liability' => AccountType::Liability,
            'capitaux', 'capitaux_propres', 'equity' => AccountType::Equity,
            'produit', 'produits', 'income' => AccountType::Income,
            'charge', 'charges', 'expense' => AccountType::Expense,
            default => match ((int) substr($code, 0, 1)) {
                1 => AccountType::Equity, 6 => AccountType::Expense, 7 => AccountType::Income, 4 => AccountType::Liability, default => AccountType::Asset,
            },
        };

        $account = Account::query()->where('code', $code)->first();
        if ($account) {
            $account->update(['name' => $row['intitule'], 'type' => $type]);

            return false;
        }
        Account::query()->create(['code' => $code, 'name' => $row['intitule'], 'type' => $type, 'class' => (int) substr($code, 0, 1)]);

        return true;
    }

    private function bool(mixed $value): bool
    {
        return in_array(Str::lower(Str::ascii(trim((string) $value))), ['1', 'oui', 'yes', 'o', 'vrai', 'true', 'x'], true);
    }

    /**
     * @return Collection<string, array{label: string}>
     */
    public static function allowedFor(User $user): Collection
    {
        return collect(self::TYPES)->filter(fn (array $d) => $user->can($d['permission']));
    }
}
