<?php

namespace App\Livewire\Admin\Stocks;

use App\Models\Stock;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $warehouseFilter = '';

    public string $stockFilter = ''; // low, out, all

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $establishmentId = auth()->user()->establishment_id;

        $stocks = Stock::query()
            ->whereHas('warehouse', fn ($q) => $q->where('establishment_id', $establishmentId))
            ->with(['product.category', 'product.unit', 'warehouse'])
            ->when($this->search, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->lowStock())
            ->when($this->stockFilter === 'out', fn ($q) => $q->outOfStock())
            ->orderBy('quantity', 'asc')
            ->paginate(20);

        $warehouses = Warehouse::where('establishment_id', $establishmentId)->active()->get();

        $totalLow = Stock::whereHas('warehouse', fn ($q) => $q->where('establishment_id', $establishmentId))->lowStock()->count();
        $totalOut = Stock::whereHas('warehouse', fn ($q) => $q->where('establishment_id', $establishmentId))->outOfStock()->count();

        return view('livewire.admin.stocks.index', compact('stocks', 'warehouses', 'totalLow', 'totalOut'))
            ->layout('layouts.admin', ['title' => 'Stocks', 'breadcrumb' => 'Stocks']);
    }
}
