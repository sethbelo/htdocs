<?php

namespace App\Livewire\Inventory;

use App\Enums\StockMovementType;
use App\Exports\TableExport;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use App\Services\Inventory\StockTransferService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Stocks')]
class Stock extends Component
{
    use InteractsWithToasts, WithPagination;

    #[Url(as: 'vue')]
    public string $tab = 'levels';

    #[Url(as: 'entrepot')]
    public ?int $warehouseId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'alerte')]
    public bool $lowOnly = false;

    #[Url(as: 'mouvement')]
    public string $movementType = '';

    /** @var array<string, string> */
    public array $move = ['operation' => 'in', 'product_id' => '', 'warehouse_id' => '', 'to_warehouse_id' => '', 'quantity' => '', 'unit_cost' => '', 'reason' => ''];

    public function updating(string $property): void
    {
        if (in_array($property, ['warehouseId', 'search', 'lowOnly', 'movementType', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function openMove(string $operation = 'in', ?int $productId = null, ?int $warehouseId = null): void
    {
        $this->authorize('inventory.create');
        $this->move = ['operation' => $operation, 'product_id' => (string) $productId, 'warehouse_id' => (string) ($warehouseId ?? $this->warehouseId), 'to_warehouse_id' => '', 'quantity' => '', 'unit_cost' => '', 'reason' => ''];
        $this->modal('move')->show();
    }

    public function saveMove(StockService $stock): void
    {
        $this->authorize('inventory.create');
        $accessible = Warehouse::query()->visibleTo(auth()->user())->pluck('id')->all();

        $data = $this->validate([
            'move.operation' => ['required', 'in:in,out,transfer,loss,breakage,return,count'],
            'move.product_id' => ['required', 'exists:products,id'],
            'move.warehouse_id' => ['required', Rule::in($accessible)],
            'move.to_warehouse_id' => ['required_if:move.operation,transfer', 'nullable', 'different:move.warehouse_id', 'exists:warehouses,id'],
            'move.quantity' => ['required', 'numeric', $this->move['operation'] === 'count' ? 'min:0' : 'gt:0'],
            'move.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'move.reason' => [in_array($this->move['operation'], ['loss', 'breakage', 'out', 'count'], true) ? 'required' : 'nullable', 'string', 'max:255'],
        ], [], ['move.product_id' => 'article', 'move.warehouse_id' => 'entrepôt', 'move.quantity' => 'quantité', 'move.reason' => 'motif'])['move'];

        $product = Product::query()->findOrFail($data['product_id']);
        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
        $quantity = (float) $data['quantity'];
        $user = auth()->user();

        $done = $this->attempt(fn () => match ($data['operation']) {
            'transfer' => app(StockTransferService::class)->transferNow($warehouse, Warehouse::query()->findOrFail($data['to_warehouse_id']), [['product_id' => $product->id, 'quantity' => $quantity]], $user, $user->name, $data['reason'] ?: null),
            'count' => $stock->adjustTo($product, $warehouse, $quantity, $user, $data['reason']) ?? true,
            default => $stock->move($product, $warehouse, match ($data['operation']) {
                'in' => StockMovementType::In,
                'out' => StockMovementType::Out,
                'loss' => StockMovementType::Loss,
                'breakage' => StockMovementType::Breakage,
                'return' => StockMovementType::Return,
            }, $quantity, null, $data['reason'] ?: null, $user, $data['unit_cost'] !== '' ? (float) $data['unit_cost'] : null),
        }, 'Mouvement enregistré.');

        if ($done) {
            $this->modal('move')->close();
        }
    }

    private function levelsQuery(): Builder
    {
        return StockLevel::query()->with('product.unit', 'warehouse')
            ->whereIn('warehouse_id', $this->warehouseId ? [$this->warehouseId] : Warehouse::query()->visibleTo(auth()->user())->pluck('id'))
            ->whereHas('product', fn (Builder $q) => $q->search($this->search ?: null))
            ->when($this->lowOnly, fn (Builder $q) => $q->join('products', 'products.id', '=', 'stock_levels.product_id')->whereColumn('stock_levels.quantity', '<=', 'products.min_stock')->select('stock_levels.*'));
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('inventory.export');

        $rows = $this->levelsQuery()->get()->map(fn (StockLevel $level): array => [
            $level->product->sku, $level->product->name, $level->warehouse->name, (float) $level->quantity,
            (float) $level->product->min_stock, (float) $level->product->cost_price, round((float) $level->quantity * (float) $level->product->cost_price, 2),
        ]);

        return Excel::download(new TableExport(['SKU', 'Article', 'Entrepôt', 'Quantité', 'Stock min.', 'Coût unitaire', 'Valeur'], $rows, 'Stocks'), 'stocks-'.now()->format('Ymd').'.xlsx');
    }

    public function render()
    {
        $warehouses = Warehouse::query()->visibleTo(auth()->user())->orderBy('name')->get();

        return view('livewire.inventory.stock', [
            'warehouses' => $warehouses,
            'levels' => $this->tab === 'levels' ? $this->levelsQuery()->orderBy('quantity')->paginate(25) : null,
            'movements' => $this->tab === 'movements' ? StockMovement::query()->with('product', 'warehouse', 'relatedWarehouse', 'user', 'source')
                ->whereIn('warehouse_id', $this->warehouseId ? [$this->warehouseId] : $warehouses->pluck('id'))
                ->when($this->search, fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->search($this->search)))
                ->when($this->movementType, fn (Builder $q) => $q->where('type', $this->movementType))
                ->latest('moved_at')->latest('id')->paginate(30) : null,
            'stockValue' => (float) StockLevel::query()->whereIn('warehouse_id', $this->warehouseId ? [$this->warehouseId] : $warehouses->pluck('id'))
                ->join('products', 'products.id', '=', 'stock_levels.product_id')->selectRaw('COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as value')->value('value'),
            'products' => Product::query()->where('is_stockable', true)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
            'allWarehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'movementTypes' => StockMovementType::options(),
        ]);
    }
}
