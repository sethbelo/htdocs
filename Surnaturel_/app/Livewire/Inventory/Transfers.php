<?php

namespace App\Livewire\Inventory;

use App\Enums\StockTransferStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\WithListFilters;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\Inventory\StockTransferService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Transferts de stock')]
class Transfers extends Component
{
    use InteractsWithToasts, WithListFilters, WithPagination;

    public ?int $warehouseFilter = null;

    #[Url(as: 'statut')]
    public string $status = '';

    /** @var array<string, string> */
    public array $form = ['from_warehouse_id' => '', 'to_warehouse_id' => '', 'notes' => ''];

    /** @var list<array{product_id: string, quantity: string}> */
    public array $lines = [];

    public ?int $transferId = null;

    /** @var array<int|string, string> */
    public array $quantities = [];

    public string $carrierName = '';

    public string $receptionNotes = '';

    public string $cancelReason = '';

    /**
     * @return Collection<int, Warehouse>
     */
    #[Computed]
    public function myWarehouses(): Collection
    {
        return Warehouse::query()->visibleTo(auth()->user())->where('is_active', true)->orderBy('name')->get();
    }

    public function create(?int $toWarehouseId = null): void
    {
        $this->authorize('inventory.create');
        $this->form = ['from_warehouse_id' => '', 'to_warehouse_id' => (string) ($toWarehouseId ?? ''), 'notes' => ''];
        $this->lines = [['product_id' => '', 'quantity' => '']];
        $this->modal('transfer')->show();
    }

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => '', 'quantity' => ''];
    }

    public function save(StockTransferService $transfers): void
    {
        $this->authorize('inventory.create');
        $this->validate([
            'form.from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'form.to_warehouse_id' => ['required', 'different:form.from_warehouse_id', 'exists:warehouses,id'],
            'form.notes' => ['nullable', 'string', 'max:1000'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], [], ['form.from_warehouse_id' => 'entrepôt source', 'form.to_warehouse_id' => 'entrepôt destination', 'lines.*.product_id' => 'article', 'lines.*.quantity' => 'quantité']);

        $transfer = $this->attempt(fn () => $transfers->request(
            Warehouse::query()->findOrFail($this->form['from_warehouse_id']),
            Warehouse::query()->findOrFail($this->form['to_warehouse_id']),
            $this->lines, auth()->user(), $this->form['notes'] ?: null,
        ));

        if ($transfer) {
            $this->modal('transfer')->close();
            $this->toast("Transfert {$transfer->number} demandé.");
        }
    }

    public function open(int $id): void
    {
        $transfer = $this->find($id);
        $this->transferId = $id;
        $this->quantities = $transfer->items->mapWithKeys(fn ($item): array => [$item->id => (string) (float) ($transfer->status === StockTransferStatus::Requested ? $item->quantity_requested : $item->quantity_shipped)])->all();
        $this->reset('carrierName', 'receptionNotes', 'cancelReason');
        $this->modal('process')->show();
    }

    public function ship(StockTransferService $transfers): void
    {
        $this->authorize('inventory.update');
        $this->validate(['quantities.*' => ['required', 'numeric', 'min:0'], 'carrierName' => ['required', 'string', 'max:255']], [], ['carrierName' => 'convoyeur']);

        if ($this->attempt(fn () => $transfers->ship($this->find($this->transferId), auth()->user(), $this->quantities, $this->carrierName), 'Marchandise expédiée : sortie de stock enregistrée.')) {
            $this->modal('process')->close();
        }
    }

    public function receive(StockTransferService $transfers): void
    {
        $this->authorize('inventory.update');
        $this->validate(['quantities.*' => ['required', 'numeric', 'min:0'], 'receptionNotes' => ['nullable', 'string', 'max:1000']]);

        if ($this->attempt(fn () => $transfers->receive($this->find($this->transferId), auth()->user(), $this->quantities, $this->receptionNotes ?: null), 'Réception enregistrée : stock destination mis à jour.')) {
            $this->modal('process')->close();
        }
    }

    public function cancel(StockTransferService $transfers): void
    {
        $this->authorize('inventory.update');
        $this->validate(['cancelReason' => ['required', 'string', 'max:255']], [], ['cancelReason' => 'motif']);

        if ($this->attempt(fn () => $transfers->cancel($this->find($this->transferId), auth()->user(), $this->cancelReason), 'Transfert annulé.')) {
            $this->modal('process')->close();
        }
    }

    private function find(int $id): StockTransfer
    {
        $ids = $this->myWarehouses->pluck('id');

        return StockTransfer::query()->with('items.product', 'from', 'to')
            ->where(fn (Builder $q) => $q->whereIn('from_warehouse_id', $ids)->orWhereIn('to_warehouse_id', $ids))
            ->findOrFail($id);
    }

    public function render()
    {
        $ids = $this->myWarehouses->pluck('id');
        $base = StockTransfer::query()->where(fn (Builder $q) => $q->whereIn('from_warehouse_id', $ids)->orWhereIn('to_warehouse_id', $ids));

        return view('livewire.inventory.transfers', [
            'transfers' => (clone $base)->with('from', 'to', 'requester', 'shipper', 'receiver', 'items')
                ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
                ->when($this->warehouseFilter, fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('from_warehouse_id', $this->warehouseFilter)->orWhere('to_warehouse_id', $this->warehouseFilter)))
                ->tap(fn ($q) => $this->applySearch($q, ['number', 'notes', 'carrier_name', 'from.name', 'to.name']))
                ->tap(fn ($q) => $this->applyPeriod($q, 'requested_at'))
                ->latest('requested_at')->paginate(20),
            'counts' => (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'allWarehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_stockable', true)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
            'current' => $this->transferId ? StockTransfer::query()->with('items.product', 'from', 'to', 'requester', 'shipper', 'receiver')->find($this->transferId) : null,
            'statuses' => StockTransferStatus::options(),
        ]);
    }

    protected function filterProperties(): array
    {
        return ['status', 'warehouseFilter'];
    }
}
