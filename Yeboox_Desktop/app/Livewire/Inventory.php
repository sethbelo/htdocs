<?php

namespace App\Livewire;

use App\Models\AccountingRecord;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\UnitOfMeasure;
use App\Support\DisplayCurrency;
use App\Support\PriceGridCatalog;
use App\Support\ProductSpreadsheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Inventory extends Component
{
    use WithFileUploads, WithPagination;

    #[Session]
    public string $activeSection = 'overview';

    #[Session]
    public string $productView = 'list';

    public $productId;

    public $name = '';

    public $sku = '';

    public $barcode = '';

    public $categoryName = '';

    public $unitOfMeasureId = '';

    public $type = 'product';

    public $price = '';

    public $salesTaxRate = 0;

    public bool $taxIncluded = true;

    public $cost = 0;

    public string $priceEntryCurrency = DisplayCurrency::CDF;

    public $stockQty = 0;

    public $alertQty = 5;

    public $internalNotes = '';

    public array $comboItems = [];

    public bool $isActive = true;

    public $image;

    public $currentImagePath;

    public $categoryId;

    public $categoryFormName = '';

    public $categoryCode = '';

    public bool $categoryIsActive = true;

    public $unitId;

    public $unitName = '';

    public $unitSymbol = '';

    public $unitType = 'unit';

    public bool $unitIsActive = true;

    public $selectedProductId;

    public $operationMode = 'adjustment';

    public $adjustType = 'in';

    public $adjustQty = 1;

    public $adjustDesc = '';

    public $operationReference = '';

    public $operationUnitCost = '';

    public array $inventoryCounts = [];

    public $search = '';

    public $filterCategory = '';

    public $filterType = '';

    public $filterStockStatus = '';

    public $movementType = '';

    public $movementReason = '';

    public $movementProductId = '';

    public $movementStartDate;

    public $movementEndDate;

    public $sortField = 'name';

    public $sortDirection = 'asc';

    public $perPage = 12;

    public bool $showProductModal = false;

    public bool $showCategoryModal = false;

    public bool $showUnitModal = false;

    public bool $showOperationModal = false;

    public bool $showPhysicalInventoryModal = false;

    public bool $showDeleteModal = false;

    public $productToDelete;

    public bool $showImportModal = false;

    public $importFile;

    public bool $updateExistingProducts = true;

    public string $importStep = 'upload';

    public ?string $importToken = null;

    public array $importPreview = [];

    public array $importErrors = [];

    public array $importSummary = [];

    public array $importResult = [];

    #[Session]
    public string $priceGridTemplate = PriceGridCatalog::DEFAULT_TEMPLATE;

    public string $priceGridCategory = '';

    public string $priceGridCurrency = DisplayCurrency::CDF;

    public bool $priceGridShowSku = true;

    public bool $priceGridShowTax = true;

    public function mount(): void
    {
        $this->movementStartDate = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->movementEndDate = Carbon::today()->format('Y-m-d');
        $this->priceEntryCurrency = $this->defaultPriceEntryCurrency();
        $this->priceGridCurrency = DisplayCurrency::code();
        $this->taxIncluded = $this->defaultTaxIncluded();
        $this->setFormDefaults();
    }

    public function setSection(string $section): void
    {
        if (! in_array($section, ['overview', 'products', 'price_grid', 'categories', 'operations', 'movements', 'physical', 'analysis'], true)) {
            return;
        }
        $this->activeSection = $section;
        $this->resetPage();
        $this->resetPage('movementPage');
        $this->resetPage('operationPage');
        $this->resetPage('physicalPage');
    }

    public function selectPriceGridTemplate(string $template): void
    {
        if (! PriceGridCatalog::hasTemplate($template)) {
            return;
        }

        $this->priceGridTemplate = $template;
    }

    public function setProductView(string $view): void
    {
        if (! in_array($view, ['list', 'grid'], true)) {
            return;
        }

        $this->productView = $view;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStockStatus(): void
    {
        $this->resetPage();
    }

    public function updatingMovementType(): void
    {
        $this->resetMovementPages();
    }

    public function updatingMovementReason(): void
    {
        $this->resetMovementPages();
    }

    public function updatingMovementProductId(): void
    {
        $this->resetMovementPages();
    }

    public function updatingMovementStartDate(): void
    {
        $this->resetMovementPages();
    }

    public function updatingMovementEndDate(): void
    {
        $this->resetMovementPages();
    }

    private function resetMovementPages(): void
    {
        $this->resetPage('movementPage');
        $this->resetPage('operationPage');
        $this->resetPage('physicalPage');
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['name', 'sku', 'category', 'type', 'price', 'cost', 'stock_qty'], true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateProduct(): void
    {
        $this->resetProductForm();
        $this->generateSku();
        $this->showProductModal = true;
    }

    public function openImportModal(): void
    {
        $this->resetProductImport();
        $this->showImportModal = true;
    }

    public function updatedImportFile(): void
    {
        $this->resetProductImport(false);
    }

    public function prepareProductImport(ProductSpreadsheet $spreadsheets): void
    {
        $this->resetValidation('importFile');
        $this->validate([
            'importFile' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ], [
            'importFile.required' => 'Sélectionnez le fichier Excel à analyser.',
            'importFile.extensions' => 'Formats acceptés : XLSX, XLS ou CSV.',
            'importFile.max' => 'Le fichier ne peut pas dépasser 10 Mo.',
        ]);

        try {
            $rows = $spreadsheets->read($this->importFile->getRealPath());
        } catch (\Throwable $exception) {
            report($exception);
            $message = $exception instanceof \InvalidArgumentException
                ? $exception->getMessage()
                : 'Le fichier est illisible ou endommagé. Utilisez le modèle Excel Yeboox.';
            $this->addError('importFile', $message);

            return;
        }

        $errors = [];
        $validRows = [];
        $seenSkus = [];
        $seenBarcodes = [];
        $usedSkus = Product::whereNotNull('sku')->pluck('sku')->mapWithKeys(
            fn (string $sku) => [Str::upper($sku) => true]
        )->all();
        $categoryCodes = ProductCategory::pluck('code', 'name');

        // Les références fournies sont réservées en priorité dans tout le fichier.
        foreach ($rows as $row) {
            if ($row['sku'] !== '') {
                $usedSkus[$row['sku']] = true;
            }
        }

        // Une référence absente est complétée avant le contrôle des doublons.
        foreach ($rows as &$row) {
            $row['sku_generated'] = $row['sku'] === '';
            if ($row['sku_generated']) {
                $row['sku'] = $this->nextImportSku($row['type'], $row['category'], $categoryCodes, $usedSkus);
                $usedSkus[$row['sku']] = true;
            }
        }
        unset($row);

        $skus = collect($rows)->pluck('sku')->filter()->unique()->values();
        $existingSkus = Product::whereIn('sku', $skus)->pluck('id', 'sku');
        $barcodes = collect($rows)->pluck('barcode')->filter()->unique()->values();
        $existingBarcodes = Product::whereIn('barcode', $barcodes)->pluck('sku', 'barcode');
        $units = UnitOfMeasure::where('is_active', true)->get()
            ->flatMap(fn (UnitOfMeasure $unit) => [Str::lower($unit->symbol) => $unit->id, Str::lower($unit->name) => $unit->id]);
        $defaultUnitId = UnitOfMeasure::where('is_active', true)->where('symbol', 'u')->value('id')
            ?: UnitOfMeasure::where('is_active', true)->value('id');

        foreach ($rows as $row) {
            $rowErrors = [];
            if (mb_strlen($row['sku']) > 80) {
                $rowErrors[] = 'Le SKU ne peut pas dépasser 80 caractères.';
            } elseif (isset($seenSkus[$row['sku']])) {
                $rowErrors[] = 'SKU répété dans le fichier.';
            }
            $seenSkus[$row['sku']] = true;
            if ($row['barcode'] !== '') {
                if (! preg_match('/^[0-9A-Z. $\/+%\-]{4,80}$/', $row['barcode'])) {
                    $rowErrors[] = 'Code-barres / QR invalide.';
                } elseif (isset($seenBarcodes[$row['barcode']])) {
                    $rowErrors[] = 'Code-barres / QR répété dans le fichier.';
                } elseif ($existingBarcodes->has($row['barcode']) && $existingBarcodes->get($row['barcode']) !== $row['sku']) {
                    $rowErrors[] = 'Ce code-barres / QR appartient déjà à un autre article.';
                }
                $seenBarcodes[$row['barcode']] = true;
            }
            if ($row['name'] === '' || mb_strlen($row['name']) > 255) {
                $rowErrors[] = 'Nom obligatoire (255 caractères maximum).';
            }
            if (! in_array($row['type'], ['product', 'service'], true)) {
                $rowErrors[] = 'Type invalide : utilisez Bien ou Service.';
            }
            if ($row['category'] === '' || mb_strlen($row['category']) > 120) {
                $rowErrors[] = 'Catégorie obligatoire (120 caractères maximum).';
            }
            foreach (['price' => 'prix', 'cost' => 'coût', 'tax' => 'TVA', 'stock' => 'stock', 'alert' => 'seuil'] as $key => $label) {
                if ($row[$key] === null || $row[$key] < 0) {
                    $rowErrors[] = ucfirst($label).' invalide.';
                }
            }
            if ($row['tax'] !== null && $row['tax'] > 100) {
                $rowErrors[] = 'La TVA doit être comprise entre 0 et 100.';
            }
            if ($row['active'] === null) {
                $rowErrors[] = 'Valeur Actif invalide.';
            }
            if (mb_strlen($row['notes']) > 5000) {
                $rowErrors[] = 'Notes internes trop longues.';
            }
            $unitId = $row['unit'] !== '' ? $units->get(Str::lower($row['unit'])) : $defaultUnitId;
            if ($row['type'] === 'product' && ! $unitId) {
                $rowErrors[] = 'Unité inconnue ou absente.';
            }
            if ($existingSkus->has($row['sku']) && ! $this->updateExistingProducts) {
                $rowErrors[] = 'Ce SKU existe déjà.';
            }

            if ($rowErrors !== []) {
                $errors[] = ['line' => $row['line'], 'sku' => $row['sku'] ?: '—', 'messages' => $rowErrors];

                continue;
            }

            $row['unit_id'] = $row['type'] === 'product' ? $unitId : null;
            $row['existing'] = $existingSkus->has($row['sku']);
            $validRows[] = $row;
        }

        $this->importToken = (string) Str::uuid();
        session()->put($this->importSessionKey(), [
            'token' => $this->importToken,
            'rows' => $validRows,
            'update_existing' => $this->updateExistingProducts,
        ]);
        $this->importPreview = array_slice($validRows, 0, 25);
        $this->importErrors = array_slice($errors, 0, 50);
        $this->importSummary = [
            'total' => count($rows),
            'valid' => count($validRows),
            'errors' => count($errors),
            'create' => collect($validRows)->where('existing', false)->count(),
            'update' => collect($validRows)->where('existing', true)->count(),
        ];
        $this->importStep = 'preview';
    }

    public function importProducts(): void
    {
        $prepared = session()->get($this->importSessionKey());
        if (! is_array($prepared) || ! hash_equals((string) ($prepared['token'] ?? ''), (string) $this->importToken)) {
            $this->addError('importFile', 'L’aperçu a expiré. Analysez de nouveau le fichier.');

            return;
        }

        $rows = collect($prepared['rows'] ?? []);
        if ($rows->isEmpty()) {
            $this->addError('importFile', 'Aucune ligne valide à importer.');

            return;
        }

        $batchReference = 'IMP-'.now()->format('Ymd-His');
        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($rows, $prepared, $batchReference, &$created, &$updated): void {
            $categories = ProductCategory::whereIn('name', $rows->pluck('category')->unique())->get()->keyBy('name');
            foreach ($rows->pluck('category')->unique() as $categoryName) {
                if (! $categories->has($categoryName)) {
                    $category = ProductCategory::create([
                        'name' => $categoryName,
                        'code' => $this->uniqueCategoryCode($categoryName),
                        'is_active' => true,
                    ]);
                    $categories->put($categoryName, $category);
                }
            }

            $products = Product::whereIn('sku', $rows->pluck('sku'))->get()->keyBy('sku');
            foreach ($rows as $row) {
                $product = $products->get($row['sku']);
                $attributes = [
                    'name' => $row['name'], 'category' => $row['category'], 'type' => $row['type'],
                    'barcode' => $row['barcode'] ?: null,
                    'unit_of_measure_id' => $row['unit_id'], 'price' => $row['price'], 'cost' => $row['cost'],
                    'sales_tax_rate' => $row['tax'], 'alert_qty' => $row['type'] === 'product' ? $row['alert'] : 0,
                    'is_active' => $row['active'], 'internal_notes' => $row['notes'] ?: null,
                ];

                if ($product && ($prepared['update_existing'] ?? false)) {
                    $product->comboItems()->delete();
                    $product->update($attributes);
                    $updated++;

                    continue;
                }

                if ($product) {
                    continue;
                }

                $product = Product::create([
                    ...$attributes,
                    'sku' => $row['sku'],
                    'tax_included' => $this->defaultTaxIncluded(),
                    'stock_qty' => $row['type'] === 'product' ? $row['stock'] : 0,
                ]);
                if ($product->type === 'product' && (float) $product->stock_qty > 0) {
                    StockMovement::create([
                        'product_id' => $product->id, 'type' => 'in', 'reason' => 'initial_stock',
                        'reference' => $batchReference, 'quantity' => $product->stock_qty,
                        'unit_cost' => $product->cost, 'stock_before' => 0, 'stock_after' => $product->stock_qty,
                        'description' => 'Stock initial importé depuis Excel', 'user_id' => auth()->id(),
                    ]);
                }
                $created++;
            }
        });

        ActivityLog::log('Import Produits', "Import Excel {$batchReference} : {$created} création(s), {$updated} mise(s) à jour.");
        session()->forget($this->importSessionKey());
        $this->importResult = ['created' => $created, 'updated' => $updated, 'reference' => $batchReference];
        $this->importStep = 'complete';
        $this->importFile = null;
        $this->resetPage();
        $this->dispatch('notify', "Import terminé : {$created} création(s), {$updated} mise(s) à jour.");
    }

    public function downloadProductTemplate(ProductSpreadsheet $spreadsheets)
    {
        abort_unless(auth()->user()?->can('export inventory'), 403, 'Vous ne pouvez pas exporter l’inventaire.');

        return $this->spreadsheetDownload($spreadsheets, $spreadsheets->template(), 'Modele-import-produits-Yeboox.xlsx');
    }

    public function exportProducts(ProductSpreadsheet $spreadsheets)
    {
        abort_unless(auth()->user()?->can('export inventory'), 403, 'Vous ne pouvez pas exporter l’inventaire.');
        $products = Product::with('unitOfMeasure')->orderBy('name')->get();

        return $this->spreadsheetDownload($spreadsheets, $spreadsheets->export($products), 'Catalogue-produits-'.now()->format('Y-m-d-His').'.xlsx');
    }

    private function spreadsheetDownload(ProductSpreadsheet $spreadsheets, Spreadsheet $spreadsheet, string $filename)
    {
        Storage::disk('local')->makeDirectory('exports');
        $path = Storage::disk('local')->path('exports/'.Str::uuid().'.xlsx');
        $spreadsheets->save($spreadsheet, $path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function resetProductImport(bool $resetFile = true): void
    {
        session()->forget($this->importSessionKey());
        $this->reset(['importToken', 'importPreview', 'importErrors', 'importSummary', 'importResult']);
        $this->importStep = 'upload';
        if ($resetFile) {
            $this->importFile = null;
        }
    }

    private function importSessionKey(): string
    {
        return 'inventory.product-import.'.auth()->id();
    }

    private function uniqueCategoryCode(string $name): string
    {
        $normalized = strtoupper(Str::ascii($name));
        $base = Str::substr(preg_replace('/[^A-Z0-9]/', '', $normalized) ?: 'CAT', 0, 12);
        $candidate = $base;
        $suffix = 2;
        while (ProductCategory::where('code', $candidate)->exists()) {
            $candidate = Str::substr($base, 0, 9).'-'.$suffix++;
        }

        return $candidate;
    }

    /** Génère un SKU déterministe et libre pour chaque ligne sans référence. */
    private function nextImportSku(string $type, string $category, $categoryCodes, array $usedSkus): string
    {
        $typeCode = $type === 'service' ? 'SRV' : 'PRD';
        $knownCode = (string) $categoryCodes->get($category, '');
        $normalized = strtoupper(Str::ascii($knownCode !== '' ? $knownCode : $category));
        $categoryCode = Str::substr(preg_replace('/[^A-Z0-9]/', '', $normalized) ?: 'GEN', 0, 12);
        $prefix = "{$typeCode}-{$categoryCode}";
        $sequence = 1;

        do {
            $candidate = sprintf('%s-%03d', $prefix, $sequence++);
        } while (isset($usedSkus[$candidate]));

        return $candidate;
    }

    public function editProduct(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->priceEntryCurrency = $this->defaultPriceEntryCurrency();
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode ?? '';
        $this->categoryName = $product->category;
        $this->unitOfMeasureId = $product->unit_of_measure_id ?: '';
        $this->type = $product->type;
        $this->price = (float) $product->price;
        $this->salesTaxRate = $product->sales_tax_rate;
        $this->taxIncluded = (bool) $product->tax_included;
        $this->cost = (float) $product->cost;
        $this->stockQty = $product->stock_qty;
        $this->alertQty = $product->alert_qty;
        $this->internalNotes = $product->internal_notes ?? '';
        $this->isActive = (bool) $product->is_active;
        $this->currentImagePath = $product->image_path;
        $this->image = null;
        $this->comboItems = $product->type === 'combo'
            ? $product->comboItems()->orderBy('id')->get()->map(fn ($item) => [
                'product_id' => (string) $item->component_product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->all()
            : [];
        $this->showProductModal = true;
    }

    public function saveProduct(): void
    {
        // Accepte la saisie comptable locale : 12,50, 12.50 ou 1 250,50.
        $this->price = $this->normalizeDecimalInput($this->price);
        $this->cost = $this->normalizeDecimalInput($this->cost);
        $this->barcode = mb_strtoupper(trim((string) $this->barcode));

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->productId)],
            'barcode' => ['nullable', 'string', 'min:4', 'max:80', 'regex:/^[0-9A-Z. $\/+%\-]+$/', Rule::unique('products', 'barcode')->ignore($this->productId)],
            'categoryName' => ['required', 'string', 'max:255', Rule::exists('product_categories', 'name')->where('is_active', true)],
            'unitOfMeasureId' => [Rule::requiredIf($this->type === 'product'), 'nullable', 'exists:unit_of_measures,id'],
            'type' => ['required', 'in:product,service,combo'],
            'priceEntryCurrency' => ['required', Rule::in(DisplayCurrency::SUPPORTED)],
            'price' => [Rule::requiredIf($this->type !== 'combo'), 'nullable', 'numeric', 'min:0'],
            'salesTaxRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'taxIncluded' => ['required', 'boolean'],
            'cost' => ['required', 'numeric', 'min:0'],
            'stockQty' => ['nullable', 'numeric', 'min:0'],
            'alertQty' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'internalNotes' => ['nullable', 'string', 'max:5000'],
            'comboItems' => [Rule::requiredIf($this->type === 'combo'), 'array', Rule::when($this->type === 'combo', ['min:1'])],
            'comboItems.*.product_id' => [Rule::requiredIf($this->type === 'combo'), 'nullable', 'distinct', Rule::exists('products', 'id')->where(fn ($query) => $query->whereIn('type', ['product', 'service'])->where('is_active', true))],
            'comboItems.*.quantity' => [Rule::requiredIf($this->type === 'combo'), 'nullable', 'numeric', 'min:0.001'],
        ], [
            'price.numeric' => 'Saisissez un prix valide, par exemple 12,50.',
            'cost.numeric' => 'Saisissez un coût valide, par exemple 5,25.',
        ]);

        $comboComponents = collect();
        if ($this->type === 'combo') {
            $comboComponents = collect($this->comboItems)->map(function (array $item) {
                $component = Product::whereIn('type', ['product', 'service'])->where('is_active', true)->findOrFail($item['product_id']);

                return [
                    'component_product_id' => $component->id,
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => $component->priceForTaxMode($this->taxIncluded),
                    'unit_cost' => (float) $component->cost,
                ];
            });
        }

        $derivedComboPrice = (float) $comboComponents->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);
        $derivedComboCost = (float) $comboComponents->sum(fn (array $item) => $item['quantity'] * $item['unit_cost']);

        $data = [
            'name' => trim($this->name),
            'sku' => trim($this->sku),
            'barcode' => filled($this->barcode) ? $this->barcode : null,
            'category' => $this->categoryName,
            'unit_of_measure_id' => $this->type === 'product' ? $this->unitOfMeasureId : null,
            'type' => $this->type,
            'price' => $this->type === 'combo' ? $derivedComboPrice : (float) $this->price,
            'sales_tax_rate' => $this->salesTaxRate ?: 0,
            'tax_included' => $this->taxIncluded,
            'cost' => $this->type === 'combo' ? $derivedComboCost : (float) $this->cost,
            'alert_qty' => $this->type === 'product' ? ($this->alertQty ?: 0) : 0,
            'is_active' => $this->isActive,
            'internal_notes' => filled($this->internalNotes) ? trim($this->internalNotes) : null,
        ];
        if ($this->image) {
            $data['image_path'] = $this->image->store('images/products', 'public');
        }

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            if ($this->image && $product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->update($data);
            ActivityLog::log('Mise à jour Produit', "Produit #{$product->id} mis à jour.");
            $message = 'Article mis à jour.';
        } else {
            $data['stock_qty'] = $this->type === 'product' ? ($this->stockQty ?: 0) : 0;
            $product = Product::create($data);
            if ($product->type === 'product' && (float) $product->stock_qty > 0) {
                StockMovement::create([
                    'product_id' => $product->id, 'type' => 'in', 'reason' => 'initial_stock',
                    'reference' => 'INIT-'.$product->id, 'quantity' => $product->stock_qty,
                    'unit_cost' => $product->cost, 'stock_before' => 0, 'stock_after' => $product->stock_qty,
                    'description' => 'Stock initial', 'user_id' => auth()->id(),
                ]);
            }
            ActivityLog::log('Création Produit', "Article #{$product->id} ({$product->name}) créé.");
            $message = 'Article créé.';
        }

        $product->comboItems()->delete();
        if ($product->type === 'combo') {
            $product->comboItems()->createMany($comboComponents->map(fn (array $item) => [
                'component_product_id' => $item['component_product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ])->all());
        }

        $this->showProductModal = false;
        $this->resetProductForm();
        $this->dispatch('notify', $message);
    }

    public function updatedType(): void
    {
        if ($this->type !== 'product') {
            $this->unitOfMeasureId = '';
            $this->stockQty = 0;
            $this->alertQty = 0;
        }
        if ($this->type === 'combo' && $this->comboItems === []) {
            $this->addComboItem();
        }
        $this->generateSku();
    }

    public function addComboItem(): void
    {
        $this->comboItems[] = ['product_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeComboItem(int $index): void
    {
        unset($this->comboItems[$index]);
        $this->comboItems = array_values($this->comboItems);
    }

    public function updatedComboItems($value, string $key): void
    {
        if (! str_ends_with($key, '.product_id')) {
            return;
        }

        $index = (int) str($key)->before('.')->toString();
        $component = Product::whereIn('type', ['product', 'service'])->where('is_active', true)->find($value);
        $this->comboItems[$index]['unit_price'] = $component ? $component->priceForTaxMode($this->taxIncluded) : 0;
    }

    public function updatedTaxIncluded(): void
    {
        if ($this->type !== 'combo') {
            return;
        }

        foreach ($this->comboItems as $index => $item) {
            $component = Product::query()->find($item['product_id'] ?? null);
            $this->comboItems[$index]['unit_price'] = $component
                ? $component->priceForTaxMode($this->taxIncluded)
                : 0;
        }
    }

    public function updatedCategoryName(): void
    {
        $this->generateSku();
    }

    public function generateSku(bool $force = false): void
    {
        if ($this->productId && ! $force) {
            return;
        }
        $typePrefix = match ($this->type) {
            'service' => 'SRV',
            'combo' => 'CMB',
            default => 'PRD',
        };
        $categoryPrefix = ProductCategory::where('name', $this->categoryName)->value('code') ?: 'GEN';
        $prefix = strtoupper($typePrefix.'-'.$categoryPrefix);
        $numbers = Product::where('sku', 'like', $prefix.'-%')->pluck('sku')->map(function ($sku) use ($prefix) {
            preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $sku, $matches);

            return (int) ($matches[1] ?? 0);
        });
        $this->sku = sprintf('%s-%03d', $prefix, ($numbers->max() ?? 0) + 1);
    }

    /** Génère un EAN-13 local et unique, avec son chiffre de contrôle. */
    public function generateBarcode(): void
    {
        do {
            $body = now()->format('ymd').str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sum = 0;
            foreach (str_split($body) as $index => $digit) {
                $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
            }
            $candidate = $body.((10 - ($sum % 10)) % 10);
        } while (Product::where('barcode', $candidate)->exists());

        $this->barcode = $candidate;
        $this->resetValidation('barcode');
    }

    private function resetProductForm(): void
    {
        $this->reset(['productId', 'name', 'sku', 'barcode', 'price', 'image', 'currentImagePath', 'internalNotes', 'comboItems']);
        $this->type = 'product';
        $this->salesTaxRate = 0;
        $this->taxIncluded = $this->defaultTaxIncluded();
        $this->cost = 0;
        $this->stockQty = 0;
        $this->alertQty = 5;
        $this->isActive = true;
        $this->priceEntryCurrency = $this->defaultPriceEntryCurrency();
        $this->setFormDefaults();
    }

    private function setFormDefaults(): void
    {
        $this->categoryName = ProductCategory::where('is_active', true)->orderBy('name')->value('name') ?: '';
        $this->unitOfMeasureId = UnitOfMeasure::where('is_active', true)->where('symbol', 'u')->value('id')
            ?: UnitOfMeasure::where('is_active', true)->value('id') ?: '';
    }

    /** Normalise un montant saisi comme texte sans convertir une valeur invalide en zéro. */
    private function normalizeDecimalInput(mixed $value): mixed
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 8);
        }

        $original = trim((string) $value);
        $normalized = str_replace(["\u{00A0}", "\u{202F}", ' '], '', $original);
        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $normalized = $lastComma > $lastDot
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? round((float) $normalized, 8) : $original;
    }

    /**
     * Conservé pour la compatibilité des anciennes actions Livewire.
     * La saisie reste désormais verrouillée sur la devise de base.
     */
    public function setPriceEntryCurrency(string $currency): void
    {
        $this->priceEntryCurrency = $this->defaultPriceEntryCurrency();
    }

    public function priceEntryEquivalent(mixed $value): string
    {
        $normalized = $this->normalizeDecimalInput($value);
        if (! is_numeric($normalized)) {
            return '—';
        }

        return DisplayCurrency::format((float) $normalized, DisplayCurrency::secondaryCode());
    }

    public function defaultTaxModeLabel(): string
    {
        return $this->defaultTaxIncluded() ? 'Prix TTC' : 'Prix HT';
    }

    private function defaultPriceEntryCurrency(): string
    {
        return DisplayCurrency::baseCode();
    }

    private function defaultTaxIncluded(): bool
    {
        return filter_var(
            Setting::get('default_tax_included', '1'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? true;
    }

    private function priceExchangeRate(): float
    {
        return DisplayCurrency::rate();
    }

    public function openCategoryModal(?int $id = null): void
    {
        $this->reset(['categoryId', 'categoryFormName', 'categoryCode']);
        $this->categoryIsActive = true;
        if ($id) {
            $category = ProductCategory::findOrFail($id);
            $this->categoryId = $category->id;
            $this->categoryFormName = $category->name;
            $this->categoryCode = $category->code;
            $this->categoryIsActive = $category->is_active;
        }
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->categoryCode = strtoupper(trim($this->categoryCode));
        $this->validate([
            'categoryFormName' => ['required', 'string', 'max:120', Rule::unique('product_categories', 'name')->ignore($this->categoryId)],
            'categoryCode' => ['required', 'alpha_dash', 'max:20', Rule::unique('product_categories', 'code')->ignore($this->categoryId)],
        ]);
        DB::transaction(function () {
            $category = $this->categoryId ? ProductCategory::findOrFail($this->categoryId) : new ProductCategory;
            $oldName = $category->name;
            $category->fill(['name' => trim($this->categoryFormName), 'code' => $this->categoryCode, 'is_active' => $this->categoryIsActive])->save();
            if ($oldName && $oldName !== $category->name) {
                Product::where('category', $oldName)->update(['category' => $category->name]);
            }
        });
        $this->showCategoryModal = false;
        $this->setFormDefaults();
        $this->dispatch('notify', 'Catégorie enregistrée.');
    }

    public function deleteCategory(int $id): void
    {
        $category = ProductCategory::findOrFail($id);
        if (Product::where('category', $category->name)->exists()) {
            $this->dispatch('notify-error', 'Cette catégorie contient des articles.');

            return;
        }
        $category->delete();
        $this->dispatch('notify', 'Catégorie supprimée.');
    }

    public function openUnitModal(?int $id = null): void
    {
        $this->reset(['unitId', 'unitName', 'unitSymbol']);
        $this->unitType = 'unit';
        $this->unitIsActive = true;
        if ($id) {
            $unit = UnitOfMeasure::findOrFail($id);
            $this->unitId = $unit->id;
            $this->unitName = $unit->name;
            $this->unitSymbol = $unit->symbol;
            $this->unitType = $unit->type;
            $this->unitIsActive = $unit->is_active;
        }
        $this->showUnitModal = true;
    }

    public function saveUnit(): void
    {
        $this->validate([
            'unitName' => ['required', 'string', 'max:80', Rule::unique('unit_of_measures', 'name')->ignore($this->unitId)],
            'unitSymbol' => ['required', 'string', 'max:16', Rule::unique('unit_of_measures', 'symbol')->ignore($this->unitId)],
            'unitType' => ['required', 'in:unit,weight,volume,length,packaging'],
        ]);
        UnitOfMeasure::updateOrCreate(['id' => $this->unitId], [
            'name' => trim($this->unitName), 'symbol' => trim($this->unitSymbol),
            'type' => $this->unitType, 'is_active' => $this->unitIsActive,
        ]);
        $this->showUnitModal = false;
        $this->setFormDefaults();
        $this->dispatch('notify', 'Unité de mesure enregistrée.');
    }

    public function deleteUnit(int $id): void
    {
        $unit = UnitOfMeasure::findOrFail($id);
        if ($unit->products()->exists()) {
            $this->dispatch('notify-error', 'Cette unité est utilisée par des articles.');

            return;
        }
        $unit->delete();
        $this->dispatch('notify', 'Unité supprimée.');
    }

    public function openOperation(string $mode = 'adjustment', ?int $productId = null): void
    {
        if (! in_array($mode, ['adjustment', 'supply'], true)) {
            return;
        }
        $this->operationMode = $mode;
        $this->selectedProductId = $productId ?: '';
        $this->adjustType = 'in';
        $this->adjustQty = 1;
        $this->adjustDesc = $mode === 'supply' ? 'Approvisionnement fournisseur' : 'Correction de stock';
        $this->operationReference = ($mode === 'supply' ? 'APP-' : 'AJU-').now()->format('Ymd-His');
        $this->operationUnitCost = $productId ? Product::find($productId)?->cost : '';
        $this->showOperationModal = true;
    }

    public function updatedSelectedProductId($value): void
    {
        if ($value && $this->operationMode === 'supply') {
            $this->operationUnitCost = Product::find($value)?->cost ?: '';
        }
    }

    public function saveOperation(): void
    {
        if ($this->operationMode === 'supply') {
            $this->operationUnitCost = $this->normalizeDecimalInput($this->operationUnitCost);
        }

        $this->validate([
            'selectedProductId' => ['required', Rule::exists('products', 'id')->where('type', 'product')],
            'operationMode' => ['required', 'in:adjustment,supply'],
            'adjustType' => ['required', 'in:in,out'],
            'adjustQty' => ['required', 'numeric', 'min:0.001'],
            'adjustDesc' => ['required', 'string', 'max:255'],
            'operationReference' => ['nullable', 'string', 'max:100'],
            'operationUnitCost' => [Rule::requiredIf($this->operationMode === 'supply'), 'nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () {
            $product = Product::lockForUpdate()->findOrFail($this->selectedProductId);
            $quantity = (float) $this->adjustQty;
            $before = (float) $product->stock_qty;
            if ($this->adjustType === 'out' && $quantity > $before) {
                throw ValidationException::withMessages(['adjustQty' => 'La sortie dépasse le stock disponible.']);
            }
            $after = $this->adjustType === 'in' ? $before + $quantity : $before - $quantity;
            $unitCost = $this->operationMode === 'supply' ? (float) $this->operationUnitCost : null;
            StockMovement::create([
                'product_id' => $product->id, 'type' => $this->adjustType, 'reason' => $this->operationMode,
                'reference' => $this->operationReference ?: null, 'quantity' => $quantity, 'unit_cost' => $unitCost,
                'stock_before' => $before, 'stock_after' => $after, 'description' => trim($this->adjustDesc), 'user_id' => auth()->id(),
            ]);
            if ($this->operationMode === 'supply') {
                $newCost = $after > 0 ? (($before * (float) $product->cost) + ($quantity * $unitCost)) / $after : $unitCost;
                $product->cost = round($newCost, 2);
                $this->writeSupplyAccounting($product, $quantity, $unitCost);
            }
            $product->stock_qty = $after;
            $product->save();
        });

        ActivityLog::log('Opération Stock', strtoupper($this->operationMode)." produit #{$this->selectedProductId}, quantité {$this->adjustQty}.");
        $this->showOperationModal = false;
        $this->dispatch('notify', $this->operationMode === 'supply' ? 'Approvisionnement enregistré.' : 'Ajustement enregistré.');
    }

    private function writeSupplyAccounting(Product $product, float $quantity, float $unitCost): void
    {
        $reference = $this->operationReference ?: 'APP-'.now()->format('YmdHis');
        $amount = round($quantity * $unitCost, 2);
        AccountingRecord::create(['reference' => $reference, 'record_date' => today()->toDateString(), 'syscohada_code' => '601', 'debit' => $amount, 'credit' => 0, 'description' => "Approvisionnement {$product->name}", 'user_id' => auth()->id()]);
        AccountingRecord::create(['reference' => $reference, 'record_date' => today()->toDateString(), 'syscohada_code' => '5711', 'debit' => 0, 'credit' => $amount, 'description' => "Paiement approvisionnement {$product->name}", 'user_id' => auth()->id()]);
    }

    public function openPhysicalInventory(): void
    {
        $this->inventoryCounts = Product::where('type', 'product')->where('is_active', true)->orderBy('name')
            ->pluck('stock_qty', 'id')->map(fn ($quantity) => (string) $quantity)->all();
        $this->showPhysicalInventoryModal = true;
    }

    public function applyPhysicalInventory(): void
    {
        $this->validate(['inventoryCounts' => ['required', 'array'], 'inventoryCounts.*' => ['nullable', 'numeric', 'min:0']]);
        $reference = 'INV-'.now()->format('Ymd-His');
        $changes = 0;
        DB::transaction(function () use ($reference, &$changes) {
            foreach ($this->inventoryCounts as $productId => $countedValue) {
                if ($countedValue === '' || $countedValue === null) {
                    continue;
                }
                $product = Product::where('type', 'product')->lockForUpdate()->find($productId);
                if (! $product) {
                    continue;
                }
                $before = (float) $product->stock_qty;
                $counted = (float) $countedValue;
                $difference = round($counted - $before, 3);
                if (abs($difference) < 0.001) {
                    continue;
                }
                StockMovement::create([
                    'product_id' => $product->id, 'type' => $difference > 0 ? 'in' : 'out', 'reason' => 'physical_inventory',
                    'reference' => $reference, 'quantity' => abs($difference), 'unit_cost' => $product->cost,
                    'stock_before' => $before, 'stock_after' => $counted,
                    'description' => 'Écart constaté lors de l’inventaire physique', 'user_id' => auth()->id(),
                ]);
                $product->update(['stock_qty' => $counted]);
                $changes++;
            }
        });
        ActivityLog::log('Inventaire physique', "Inventaire {$reference} validé avec {$changes} écart(s).");
        $this->showPhysicalInventoryModal = false;
        $this->dispatch('notify', "Inventaire validé : {$changes} écart(s) corrigé(s).");
    }

    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        $this->dispatch('notify', $product->is_active ? 'Article activé.' : 'Article désactivé.');
    }

    public function confirmDelete(int $id): void
    {
        $this->productToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteProduct(): void
    {
        $product = Product::findOrFail($this->productToDelete);
        if ($product->saleItems()->exists() || $product->componentOfCombos()->exists() || DB::table('appointments')->where('service_id', $product->id)->exists()) {
            $product->update(['is_active' => false]);
            $this->showDeleteModal = false;
            $this->dispatch('notify-error', 'Article déjà utilisé : il a été désactivé pour préserver l’historique.');

            return;
        }
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        $this->showDeleteModal = false;
        $this->productToDelete = null;
        $this->dispatch('notify', 'Article supprimé.');
    }

    public function render()
    {
        $movementPeriodStart = $this->movementStartDate ? Carbon::parse($this->movementStartDate)->startOfDay() : null;
        $movementPeriodEnd = $this->movementEndDate ? Carbon::parse($this->movementEndDate)->endOfDay() : null;
        // Les données des sections invisibles ne sont plus chargées à chaque interaction.
        $products = collect();
        $categories = collect();
        $categoryCounts = collect();
        $units = collect();
        $stockProducts = collect();
        $comboProducts = collect();
        $recentMovements = collect();
        $movements = collect();
        $operationMovements = collect();
        $physicalMovements = collect();
        $topMovements = collect();
        $reasonAnalysis = collect();
        $movementTimeline = collect();
        $priceGridGroups = collect();
        $priceGridTemplates = PriceGridCatalog::templates();
        $priceGridCompany = [];
        $priceGridLogoUrl = null;
        $analysisIn = 0.0;
        $analysisOut = 0.0;

        if ($this->activeSection === 'products') {
            $products = Product::with('unitOfMeasure')
                ->when($this->search, fn ($query) => $query->where(fn ($subQuery) => $subQuery->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%')->orWhere('barcode', 'like', '%'.$this->search.'%')))
                ->when($this->filterCategory, fn ($query) => $query->where('category', $this->filterCategory))
                ->when($this->filterType, fn ($query) => $query->where('type', $this->filterType))
                ->when($this->filterStockStatus === 'alert', fn ($query) => $query->where('type', 'product')->whereColumn('stock_qty', '<=', 'alert_qty')->where('stock_qty', '>', 0))
                ->when($this->filterStockStatus === 'out', fn ($query) => $query->where('type', 'product')->where('stock_qty', '<=', 0))
                ->when($this->filterStockStatus === 'in_stock', fn ($query) => $query->where('type', 'product')->whereColumn('stock_qty', '>', 'alert_qty'))
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage);
        }

        if (in_array($this->activeSection, ['products', 'price_grid', 'categories'], true) || $this->showProductModal || $this->showCategoryModal) {
            $categories = ProductCategory::orderBy('name')->get();
        }
        if ($this->activeSection === 'price_grid') {
            $priceGridGroups = PriceGridCatalog::grouped($this->priceGridCategory ?: null);
            $configuredLogo = ltrim(str_replace('public/', '', (string) Setting::get('company_logo', '')), '/');
            $priceGridLogoUrl = $configuredLogo !== '' && Storage::disk('public')->exists($configuredLogo)
                ? Storage::disk('public')->url($configuredLogo)
                : asset('images/yeboox-logo.png');
            $priceGridCompany = [
                'name' => Setting::get('company_name', 'Yeboox'),
                'address' => Setting::get('company_address', 'Kinshasa, RDC'),
                'phone' => Setting::get('company_phone', ''),
                'email' => Setting::get('company_email', ''),
            ];
        }
        if ($this->activeSection === 'categories') {
            $categoryCounts = Product::selectRaw('category, COUNT(*) as total')->groupBy('category')->pluck('total', 'category');
        }
        if ($this->activeSection === 'categories' || $this->showProductModal || $this->showUnitModal) {
            $units = UnitOfMeasure::withCount('products')->orderBy('type')->orderBy('name')->get();
        }
        if (in_array($this->activeSection, ['overview', 'movements'], true) || $this->showOperationModal || $this->showPhysicalInventoryModal) {
            $stockProducts = Product::with('unitOfMeasure')->where('type', 'product')->where('is_active', true)->orderBy('name')->get();
        }
        if ($this->showProductModal && $this->type === 'combo') {
            $comboProducts = Product::whereIn('type', ['product', 'service'])->where('is_active', true)
                ->when($this->productId, fn ($query) => $query->whereKeyNot($this->productId))
                ->orderBy('name')->get();
        }

        $movementQuery = StockMovement::with(['product.unitOfMeasure', 'user'])
            ->when($movementPeriodStart, fn ($query) => $query->where('created_at', '>=', $movementPeriodStart))
            ->when($movementPeriodEnd, fn ($query) => $query->where('created_at', '<=', $movementPeriodEnd))
            ->when($this->movementType, fn ($query) => $query->where('type', $this->movementType))
            ->when($this->movementReason, fn ($query) => $query->where('reason', $this->movementReason))
            ->when($this->movementProductId, fn ($query) => $query->where('product_id', $this->movementProductId));
        if ($this->activeSection === 'overview') {
            $recentMovements = (clone $movementQuery)->latest()->limit(15)->get();
        } elseif ($this->activeSection === 'movements') {
            $movements = (clone $movementQuery)->latest()->paginate(12, ['*'], 'movementPage');
        } elseif ($this->activeSection === 'operations') {
            $operationMovements = (clone $movementQuery)->whereIn('reason', ['supply', 'adjustment', 'initial_stock'])->latest()->paginate(10, ['*'], 'operationPage');
        } elseif ($this->activeSection === 'physical') {
            $physicalMovements = (clone $movementQuery)->where('reason', 'physical_inventory')->latest()->paginate(10, ['*'], 'physicalPage');
        }

        // Regrouper les indicateurs évite six lectures complètes de la table produits.
        $stats = ['products' => 0, 'services' => 0, 'units' => 0.0, 'value' => 0.0, 'alerts' => 0, 'out' => 0];
        if ($this->activeSection === 'overview') {
            $stockSummary = Product::query()
                ->selectRaw("SUM(CASE WHEN type = 'product' THEN 1 ELSE 0 END) as products")
                ->selectRaw("SUM(CASE WHEN type = 'service' THEN 1 ELSE 0 END) as services")
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'product' THEN stock_qty ELSE 0 END), 0) as units")
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'product' THEN stock_qty * cost ELSE 0 END), 0) as value")
                ->selectRaw("SUM(CASE WHEN type = 'product' AND stock_qty <= alert_qty THEN 1 ELSE 0 END) as alerts")
                ->selectRaw("SUM(CASE WHEN type = 'product' AND stock_qty <= 0 THEN 1 ELSE 0 END) as out")
                ->first();
            $stats = [
                'products' => (int) $stockSummary->products, 'services' => (int) $stockSummary->services,
                'units' => (float) $stockSummary->units, 'value' => (float) $stockSummary->value,
                'alerts' => (int) $stockSummary->alerts, 'out' => (int) $stockSummary->out,
            ];
        }

        if ($this->activeSection === 'analysis') {
            $analysisIn = (float) (clone $movementQuery)->where('type', 'in')->sum('quantity');
            $analysisOut = (float) (clone $movementQuery)->where('type', 'out')->sum('quantity');
            $topMovements = StockMovement::query()->selectRaw('product_id, SUM(quantity) as movement_total')
                ->when($movementPeriodStart, fn ($query) => $query->where('created_at', '>=', $movementPeriodStart))
                ->when($movementPeriodEnd, fn ($query) => $query->where('created_at', '<=', $movementPeriodEnd))
                ->groupBy('product_id')->orderByDesc('movement_total')->with('product.unitOfMeasure')->limit(8)->get();
            $reasonAnalysis = (clone $movementQuery)->selectRaw('reason, COUNT(*) as operations, SUM(quantity) as quantity_total')
                ->groupBy('reason')->orderByDesc('quantity_total')->get();
            $movementTimeline = (clone $movementQuery)
                ->without(['product.unitOfMeasure', 'user'])
                ->selectRaw('DATE(created_at) as movement_date')
                ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as incoming")
                ->selectRaw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as outgoing")
                ->groupBy('movement_date')
                ->orderBy('movement_date')
                ->get()
                ->map(fn (StockMovement $movement) => [
                    'date' => $movement->movement_date,
                    'label' => Carbon::parse($movement->movement_date)->format('d/m'),
                    'in' => (float) $movement->incoming,
                    'out' => (float) $movement->outgoing,
                ]);
        }

        return view('livewire.inventory', compact('products', 'categories', 'categoryCounts', 'units', 'stockProducts', 'comboProducts', 'recentMovements', 'movements', 'operationMovements', 'physicalMovements', 'stats', 'analysisIn', 'analysisOut', 'topMovements', 'reasonAnalysis', 'movementTimeline', 'priceGridGroups', 'priceGridTemplates', 'priceGridCompany', 'priceGridLogoUrl'))->layout('layouts.app');
    }
}
