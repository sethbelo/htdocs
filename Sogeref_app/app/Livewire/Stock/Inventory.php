<?php

namespace App\Livewire\Stock;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Equipment;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockLevel;
use App\Models\StockMove;
use App\Models\Warehouse;
use App\Services\SequenceService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Stock & entrepôts : articles, niveaux, mouvements, entrepôts,
 * parc matériel et inventaires physiques.
 */
#[Title('Stock & entrepôts')]
class Inventory extends Component
{
    use InteractsWithUi, WithFileUploads, WithPagination;

    #[Url(as: 'onglet')]
    public string $tab = 'articles';

    #[Url(as: 'q')]
    public string $search = '';

    public string $productType = '';

    public bool $lowOnly = false;

    // Article
    public bool $showProduct = false;

    public ?int $productId = null;

    public array $product = [];

    public $image = null;

    // Mouvement manuel
    public bool $showMove = false;

    public string $moveType = 'in';

    public ?int $moveProductId = null;

    public ?int $fromWarehouseId = null;

    public ?int $toWarehouseId = null;

    public string $moveQuantity = '1';

    public string $moveCost = '';

    public string $moveNotes = '';

    // Entrepôt
    public bool $showWarehouse = false;

    public ?int $warehouseId = null;

    public array $warehouse = [];

    // Matériel
    public bool $showEquipment = false;

    public ?int $equipmentId = null;

    public array $equipment = [];

    // Inventaire physique
    public ?int $countId = null;

    public array $counted = [];

    public ?int $countWarehouseId = null;

    public function updating($name): void
    {
        if (in_array($name, ['search', 'tab', 'productType', 'lowOnly'], true)) {
            $this->resetPage();
        }
    }

    // ── Articles ────────────────────────────────────────────────────────────
    public function editProduct(?int $id = null): void
    {
        $this->authorizeModule('inventory', 'manage');
        $p = $id ? Product::where('company_id', company_id())->findOrFail($id) : new Product(['type' => 'material', 'unit' => 'unité', 'tax_rate' => 0, 'track_stock' => true, 'is_sellable' => true, 'is_purchasable' => true, 'lead_time_days' => 7]);
        $this->productId = $p->id;
        $this->image = null;
        $this->product = [
            'sku' => $p->sku, 'name' => $p->name, 'type' => $p->type, 'category_id' => $p->category_id, 'unit' => $p->unit, 'barcode' => $p->barcode,
            'description' => $p->description, 'sale_price' => (float) $p->sale_price, 'purchase_price' => (float) $p->purchase_price, 'tax_rate' => (float) $p->tax_rate,
            'track_stock' => (bool) $p->track_stock, 'reorder_level' => (float) $p->reorder_level, 'reorder_quantity' => (float) $p->reorder_quantity,
            'lead_time_days' => (int) $p->lead_time_days, 'default_supplier_id' => $p->default_supplier_id, 'is_sellable' => (bool) $p->is_sellable,
            'is_purchasable' => (bool) $p->is_purchasable, 'pos_visible' => (bool) $p->pos_visible, 'is_active' => $p->exists ? (bool) $p->is_active : true,
        ];
        $this->showProduct = true;
    }

    public function saveProduct(): void
    {
        $this->authorizeModule('inventory', 'manage');
        $data = $this->validate([
            'product.sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->where('company_id', company_id())->ignore($this->productId)],
            'product.name' => ['required', 'string', 'max:200'],
            'product.type' => ['required', Rule::in(array_keys(config('erp.product_types')))],
            'product.category_id' => ['nullable', 'integer'],
            'product.unit' => ['required', 'string', 'max:20'],
            'product.barcode' => ['nullable', 'string', 'max:60'],
            'product.description' => ['nullable', 'string', 'max:2000'],
            'product.sale_price' => ['numeric', 'min:0'], 'product.purchase_price' => ['numeric', 'min:0'], 'product.tax_rate' => ['numeric', 'between:0,100'],
            'product.track_stock' => ['boolean'], 'product.reorder_level' => ['numeric', 'min:0'], 'product.reorder_quantity' => ['numeric', 'min:0'],
            'product.lead_time_days' => ['integer', 'min:0'], 'product.default_supplier_id' => ['nullable', 'integer'],
            'product.is_sellable' => ['boolean'], 'product.is_purchasable' => ['boolean'], 'product.pos_visible' => ['boolean'], 'product.is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:3072'],
        ], [], ['product.sku' => 'code', 'product.name' => 'désignation'])['product'];
        $data['is_manufactured'] = $data['type'] === 'finished';
        if ($this->image) {
            $data['image_path'] = $this->image->store('products', 'public');
        }
        Product::updateOrCreate(['id' => $this->productId], $data + ['company_id' => company_id()]);
        $this->showProduct = false;
        $this->toast('Article enregistré.');
    }

    // ── Mouvements ──────────────────────────────────────────────────────────
    public function newMove(string $type = 'in', ?int $productId = null): void
    {
        $this->authorizeModule('inventory', 'manage');
        $this->moveType = $type;
        $this->moveProductId = $productId;
        $central = Warehouse::where('company_id', company_id())->where('type', 'central')->value('id');
        $this->fromWarehouseId = in_array($type, ['out', 'transfer'], true) ? $central : null;
        $this->toWarehouseId = $type === 'in' ? $central : null;
        $this->moveQuantity = '1';
        $this->moveCost = $productId ? (string) (float) Product::find($productId)?->purchase_price : '';
        $this->moveNotes = '';
        $this->showMove = true;
    }

    public function saveMove(StockService $stock): void
    {
        $this->authorizeModule('inventory', 'manage');
        $this->validate([
            'moveType' => ['required', 'in:in,out,transfer,adjust'], 'moveProductId' => ['required', 'integer'], 'moveQuantity' => ['required', 'numeric', 'gt:0'],
            'fromWarehouseId' => [Rule::requiredIf(in_array($this->moveType, ['out', 'transfer'], true)), 'nullable', 'integer'],
            'toWarehouseId' => [Rule::requiredIf(in_array($this->moveType, ['in', 'transfer', 'adjust'], true)), 'nullable', 'integer'],
            'moveNotes' => ['nullable', 'string', 'max:255'],
        ], [], ['moveProductId' => 'article', 'moveQuantity' => 'quantité', 'fromWarehouseId' => 'entrepôt source', 'toWarehouseId' => 'entrepôt']);
        $product = Product::where('company_id', company_id())->findOrFail($this->moveProductId);
        $from = $this->fromWarehouseId ? Warehouse::where('company_id', company_id())->find($this->fromWarehouseId) : null;
        $to = $this->toWarehouseId ? Warehouse::where('company_id', company_id())->find($this->toWarehouseId) : null;
        $qty = (float) $this->moveQuantity;
        $ref = 'MVT-'.now()->format('ymdHi');

        $done = $this->attempt(fn () => match ($this->moveType) {
            'in' => $stock->receive($product, $to, $qty, 'in', null, $ref, $this->moveCost !== '' ? (float) $this->moveCost : null, $this->moveNotes ?: 'Entrée manuelle'),
            'out' => $stock->issue($product, $from, $qty, 'out', null, $ref, $this->moveNotes ?: 'Sortie manuelle'),
            'transfer' => $stock->transfer($product, $from, $to, $qty, null, $ref, $this->moveNotes ?: 'Transfert'),
            'adjust' => $stock->adjustTo($product, $to, $qty, null, $ref),
        }, 'Mouvement enregistré.');
        if ($done) {
            $this->showMove = false;
        }
    }

    // ── Entrepôts ───────────────────────────────────────────────────────────
    public function editWarehouse(?int $id = null): void
    {
        $this->authorizeModule('inventory', 'manage');
        $w = $id ? Warehouse::where('company_id', company_id())->findOrFail($id) : new Warehouse(['type' => 'central', 'is_active' => true]);
        $this->warehouseId = $w->id;
        $this->warehouse = ['code' => $w->code, 'name' => $w->name, 'type' => $w->type, 'site_id' => $w->site_id, 'address' => $w->address, 'is_active' => (bool) ($w->is_active ?? true)];
        $this->showWarehouse = true;
    }

    public function saveWarehouse(): void
    {
        $this->authorizeModule('inventory', 'manage');
        $data = $this->validate([
            'warehouse.code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->where('company_id', company_id())->ignore($this->warehouseId)],
            'warehouse.name' => ['required', 'string', 'max:120'], 'warehouse.type' => ['required', Rule::in(array_keys(Warehouse::TYPES))],
            'warehouse.site_id' => ['nullable', 'integer'], 'warehouse.address' => ['nullable', 'string', 'max:255'], 'warehouse.is_active' => ['boolean'],
        ], [], ['warehouse.code' => 'code', 'warehouse.name' => 'nom'])['warehouse'];
        Warehouse::updateOrCreate(['id' => $this->warehouseId], $data + ['company_id' => company_id()]);
        $this->showWarehouse = false;
        $this->toast('Entrepôt enregistré.');
    }

    // ── Matériel ────────────────────────────────────────────────────────────
    public function editEquipment(?int $id = null): void
    {
        $this->authorizeModule('inventory', 'manage');
        $e = $id ? Equipment::where('company_id', company_id())->findOrFail($id) : new Equipment(['status' => 'available']);
        $this->equipmentId = $e->id;
        $this->equipment = ['code' => $e->code, 'name' => $e->name, 'category' => $e->category, 'serial_number' => $e->serial_number, 'status' => $e->status, 'purchase_value' => (float) $e->purchase_value, 'is_rentable' => (bool) $e->is_rentable, 'daily_rent_price' => (float) $e->daily_rent_price, 'warehouse_id' => $e->warehouse_id, 'notes' => $e->notes];
        $this->showEquipment = true;
    }

    public function saveEquipment(): void
    {
        $this->authorizeModule('inventory', 'manage');
        $data = $this->validate([
            'equipment.code' => ['required', 'string', 'max:30', Rule::unique('equipment', 'code')->where('company_id', company_id())->ignore($this->equipmentId)],
            'equipment.name' => ['required', 'string', 'max:160'], 'equipment.category' => ['nullable', 'string', 'max:80'], 'equipment.serial_number' => ['nullable', 'string', 'max:80'],
            'equipment.status' => ['required', Rule::in(array_keys(config('erp.statuses.equipment')))], 'equipment.purchase_value' => ['numeric', 'min:0'],
            'equipment.is_rentable' => ['boolean'], 'equipment.daily_rent_price' => ['numeric', 'min:0'], 'equipment.warehouse_id' => ['nullable', 'integer'], 'equipment.notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['equipment.code' => 'code', 'equipment.name' => 'désignation'])['equipment'];
        Equipment::updateOrCreate(['id' => $this->equipmentId], $data + ['company_id' => company_id()]);
        $this->showEquipment = false;
        $this->toast('Équipement enregistré.');
    }

    // ── Inventaires physiques ───────────────────────────────────────────────
    public function startCount(SequenceService $sequences): void
    {
        $this->authorizeModule('inventory', 'manage');
        $this->validate(['countWarehouseId' => ['required', 'integer']], [], ['countWarehouseId' => 'entrepôt']);
        $warehouse = Warehouse::where('company_id', company_id())->findOrFail($this->countWarehouseId);
        $count = DB::transaction(function () use ($warehouse, $sequences) {
            $count = InventoryCount::create(['company_id' => company_id(), 'warehouse_id' => $warehouse->id, 'reference' => $sequences->next('inventory'), 'count_date' => now()->toDateString(), 'user_id' => auth()->id()]);
            $levels = StockLevel::where('warehouse_id', $warehouse->id)->pluck('quantity', 'product_id');
            $products = Product::where('company_id', company_id())->where('track_stock', true)->where(fn ($q) => $q->whereIn('id', $levels->keys())->orWhere('is_active', true))->orderBy('name')->get();
            foreach ($products as $product) {
                if (isset($levels[$product->id]) || $warehouse->type !== 'showroom' || $product->type === 'finished') {
                    $count->lines()->create(['product_id' => $product->id, 'expected' => (float) ($levels[$product->id] ?? 0)]);
                }
            }

            return $count;
        });
        $this->openCount($count->id);
        $this->toast("Inventaire {$count->reference} ouvert : saisissez les quantités comptées.");
    }

    public function openCount(int $id): void
    {
        $count = InventoryCount::with('lines')->where('company_id', company_id())->findOrFail($id);
        $this->countId = $count->id;
        $this->counted = $count->lines->mapWithKeys(fn ($l) => [$l->id => $l->counted === null ? '' : (string) (float) $l->counted])->all();
    }

    public function saveCount(bool $validate = false, ?StockService $stock = null): void
    {
        $this->authorizeModule('inventory', 'manage');
        $count = InventoryCount::with('lines.product', 'warehouse')->where('company_id', company_id())->findOrFail($this->countId);
        abort_if($count->status === 'validated', 403);
        DB::transaction(function () use ($count, $validate, $stock) {
            foreach ($count->lines as $line) {
                $value = $this->counted[$line->id] ?? '';
                $line->update(['counted' => $value === '' ? null : (float) $value]);
                if ($validate && $value !== '') {
                    $stock->adjustTo($line->product, $count->warehouse, (float) $value, $count, $count->reference);
                }
            }
            if ($validate) {
                $count->update(['status' => 'validated']);
            }
        });
        $this->toast($validate ? 'Inventaire validé : stocks ajustés.' : 'Comptage enregistré.');
        if ($validate) {
            $this->countId = null;
        }
    }

    public function validateCount(StockService $stock): void
    {
        $this->saveCount(true, $stock);
    }

    public function render()
    {
        $cid = company_id();
        $warehouses = Warehouse::with('site')->where('company_id', $cid)->orderBy('type')->orderBy('name')->get();
        $data = ['warehouses' => $warehouses];

        match ($this->tab) {
            'articles' => $data['products'] = Product::with('category', 'supplier')->where('company_id', $cid)
                ->when($this->productType, fn ($q) => $q->where('type', $this->productType))
                ->when($this->lowOnly, fn ($q) => $q->where('track_stock', true)->where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level'))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
                ->orderBy('name')->paginate(20),
            'niveaux' => $data['matrix'] = Product::where('company_id', $cid)->where('track_stock', true)
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
                ->with('levels')->orderBy('name')->paginate(25),
            'mouvements' => $data['moves'] = StockMove::with('product', 'from', 'to', 'user')->where('company_id', $cid)
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))))
                ->latest('moved_at')->latest('id')->paginate(25),
            'materiel' => $data['equipmentList'] = Equipment::with('site', 'warehouse')->where('company_id', $cid)
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")))
                ->orderBy('category')->orderBy('name')->paginate(25),
            'inventaires' => $data['counts'] = InventoryCount::with('warehouse', 'user')->withCount('lines')->where('company_id', $cid)->latest('id')->paginate(15),
            default => null,
        };

        $totalValue = (float) Product::where('company_id', $cid)->where('track_stock', true)->sum(DB::raw('stock_quantity * purchase_price'));

        return view('livewire.stock.inventory', $data + [
            'stats' => [
                'value' => $totalValue,
                'items' => Product::where('company_id', $cid)->where('track_stock', true)->count(),
                'low' => Product::where('company_id', $cid)->where('track_stock', true)->where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
                'equipment' => Equipment::where('company_id', $cid)->count(),
            ],
            'categories' => Category::where('company_id', $cid)->orderBy('name')->get(),
            'suppliers' => Contact::where('company_id', $cid)->where('type', 'supplier')->orderBy('company_name')->get(),
            'sites' => Site::where('company_id', $cid)->orderBy('name')->get(),
            'catalog' => $this->showMove ? Product::where('company_id', $cid)->where('track_stock', true)->orderBy('name')->get(['id', 'sku', 'name', 'unit']) : collect(),
            'currentCount' => $this->countId ? InventoryCount::with('lines.product', 'warehouse')->find($this->countId) : null,
        ]);
    }
}
