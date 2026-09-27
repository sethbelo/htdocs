<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Employee;
use App\Models\ManufacturingOperation;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\ManufacturingService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fiche d'un ordre de fabrication / dossier de construction :
 * spécifications, liste de matériel, gamme opératoire, bons de sortie.
 */
class ManufacturingShow extends Component
{
    use InteractsWithUi;

    public ManufacturingOrder $mo;

    #[Url(as: 'onglet')]
    public string $tab = 'materiel';

    public array $specs = [];

    public ?int $responsibleId = null;

    public ?int $warehouseId = null;

    public string $plannedStart = '';

    public string $plannedEnd = '';

    public int $progress = 0;

    public string $description = '';

    // Ajout de matière / opération
    public ?int $newProductId = null;

    public string $newQuantity = '1';

    public string $newOperation = '';

    public string $newOperationHours = '2';

    public function mount(ManufacturingOrder $mo): void
    {
        abort_unless($mo->company_id === company_id(), 404);
        $this->mo = $mo;
        $this->specs = $mo->specifications ?: [['label' => '', 'value' => '']];
        $this->responsibleId = $mo->responsible_id;
        $this->warehouseId = $mo->warehouse_id;
        $this->plannedStart = $mo->planned_start?->toDateString() ?? '';
        $this->plannedEnd = $mo->planned_end?->toDateString() ?? '';
        $this->progress = (int) $mo->progress;
        $this->description = $mo->description ?? '';
    }

    private function service(): ManufacturingService
    {
        return app(ManufacturingService::class);
    }

    private function locked(): bool
    {
        return in_array($this->mo->status, ['done', 'cancelled'], true);
    }

    // ── Cycle de vie ────────────────────────────────────────────────────────
    public function start(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->attempt(fn () => $this->service()->start($this->mo), 'Production démarrée.');
        $this->mo->refresh();
        $this->progress = (int) $this->mo->progress;
    }

    public function toQuality(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->attempt(fn () => $this->service()->toQuality($this->mo), 'Ordre transmis au contrôle qualité.');
        $this->mo->refresh();
    }

    public function finish(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->attempt(fn () => $this->service()->finish($this->mo), 'Fabrication terminée'.($this->mo->product?->track_stock ? ' : produit fini entré en stock.' : '.'));
        $this->mo->refresh();
        $this->progress = 100;
    }

    public function cancel(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        if ($this->mo->status === 'done') {
            $this->toast('Un ordre terminé ne peut pas être annulé.', 'error');

            return;
        }
        $this->mo->update(['status' => 'cancelled']);
        $this->toast('Ordre annulé.', 'warning');
    }

    public function requestMaterials()
    {
        $this->authorizeModule('manufacturing', 'manage');
        $voucher = $this->attempt(fn () => $this->service()->requestMaterials($this->mo->load('materials', 'salesOrder')));
        if ($voucher) {
            $this->toast("Bon de sortie {$voucher->number} transmis pour approbation.");
            $this->tab = 'bons';
        }
    }

    // ── Détails ─────────────────────────────────────────────────────────────
    public function saveDetails(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->validate(['plannedStart' => ['nullable', 'date'], 'plannedEnd' => ['nullable', 'date', 'after_or_equal:plannedStart'], 'progress' => ['integer', 'between:0,100'], 'specs.*.label' => ['nullable', 'string', 'max:120'], 'specs.*.value' => ['nullable', 'string', 'max:255']]);
        $this->mo->update([
            'responsible_id' => $this->responsibleId ?: null,
            'warehouse_id' => $this->warehouseId ?: null,
            'planned_start' => $this->plannedStart ?: null,
            'planned_end' => $this->plannedEnd ?: null,
            'progress' => $this->progress,
            'description' => $this->description ?: null,
            'specifications' => array_values(array_filter($this->specs, fn ($s) => trim((string) ($s['label'] ?? '')) !== '')),
        ]);
        $this->toast('Ordre mis à jour.');
    }

    public function addSpec(): void
    {
        $this->specs[] = ['label' => '', 'value' => ''];
    }

    public function removeSpec(int $i): void
    {
        unset($this->specs[$i]);
        $this->specs = array_values($this->specs);
    }

    // ── Matériel ────────────────────────────────────────────────────────────
    public function addMaterial(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->validate(['newProductId' => ['required', 'integer'], 'newQuantity' => ['required', 'numeric', 'gt:0']], [], ['newProductId' => 'matière', 'newQuantity' => 'quantité']);
        $product = Product::where('company_id', company_id())->findOrFail($this->newProductId);
        $existing = $this->mo->materials()->where('product_id', $product->id)->first();
        $existing
            ? $existing->update(['quantity_planned' => (float) $existing->quantity_planned + (float) $this->newQuantity])
            : $this->mo->materials()->create(['product_id' => $product->id, 'description' => $product->name, 'unit' => $product->unit, 'quantity_planned' => $this->newQuantity, 'unit_cost' => $product->purchase_price]);
        $this->reset(['newProductId']);
        $this->newQuantity = '1';
        $this->toast('Matière ajoutée à la liste.');
    }

    public function updateMaterial(int $id, $quantity): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $material = $this->mo->materials()->findOrFail($id);
        $material->update(['quantity_planned' => max((float) $material->quantity_consumed, (float) $quantity)]);
    }

    public function removeMaterial(int $id): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $material = $this->mo->materials()->findOrFail($id);
        if ((float) $material->quantity_consumed > 0) {
            $this->toast('Cette matière a déjà été sortie du magasin.', 'error');

            return;
        }
        $material->delete();
    }

    // ── Opérations ──────────────────────────────────────────────────────────
    public function addOperation(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->validate(['newOperation' => ['required', 'string', 'max:160'], 'newOperationHours' => ['required', 'numeric', 'min:0']]);
        $this->mo->operations()->create(['name' => $this->newOperation, 'planned_hours' => $this->newOperationHours, 'position' => $this->mo->operations()->max('position') + 1]);
        $this->newOperation = '';
    }

    public function cycleOperation(int $id): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $op = ManufacturingOperation::where('manufacturing_order_id', $this->mo->id)->findOrFail($id);
        $next = ['todo' => 'in_progress', 'in_progress' => 'done', 'done' => 'todo'][$op->status];
        $op->update(['status' => $next, 'actual_hours' => $next === 'done' && ! (float) $op->actual_hours ? $op->planned_hours : $op->actual_hours]);

        // Avancement automatique = part des heures des opérations terminées.
        $ops = $this->mo->operations()->get();
        $total = max(1, (float) $ops->sum('planned_hours'));
        $done = (float) $ops->where('status', 'done')->sum('planned_hours') + (float) $ops->where('status', 'in_progress')->sum('planned_hours') / 2;
        $this->progress = min(99, (int) round($done / $total * 100));
        $this->mo->update(['progress' => $this->progress]);
        if ($this->mo->status === 'confirmed' && $next !== 'todo') {
            $this->service()->start($this->mo);
        }
    }

    public function assignOperation(int $id, $employeeId): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        ManufacturingOperation::where('manufacturing_order_id', $this->mo->id)->findOrFail($id)->update(['employee_id' => $employeeId ?: null]);
    }

    public function render()
    {
        $this->mo->load('materials.product', 'operations.employee', 'salesOrder.contact', 'product', 'responsible', 'warehouse', 'vouchers');
        $productIds = $this->mo->materials->pluck('product_id')->filter();
        $cid = company_id();

        return view('livewire.operations.manufacturing-show', [
            'locked' => $this->locked(),
            'stock' => StockLevel::whereIn('product_id', $productIds)->get()->groupBy('product_id')->map->sum('quantity'),
            'employees' => Employee::where('company_id', $cid)->where('status', 'active')->orderBy('last_name')->get(),
            'warehouses' => Warehouse::where('company_id', $cid)->where('is_active', true)->get(),
            'materialsCatalog' => Product::where('company_id', $cid)->whereIn('type', ['material', 'consumable'])->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'unit']),
            'labourHours' => $this->mo->operations->sum('planned_hours'),
        ])->title($this->mo->number.' · Atelier');
    }
}
