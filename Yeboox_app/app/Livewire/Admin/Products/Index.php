<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public string $sortBy = 'name';

    public string $sortDir = 'asc';

    public bool $showDeleteModal = false;

    public ?string $deleteId = null;

    public string $deleteName = '';

    protected $queryString = ['search', 'categoryFilter', 'statusFilter'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function toggleStatus(string $id): void
    {
        abort_unless(auth()->user()->can('products.edit'), 403);
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('products.delete'), 403);
        $product = Product::findOrFail($id);
        $this->deleteId = $product->id;
        $this->deleteName = $product->name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('products.delete'), 403);
        Product::findOrFail($this->deleteId)->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Produit supprimé.');
    }

    public function render()
    {
        $products = Product::query()
            ->with(['category', 'tax', 'unit'])
            ->search($this->search)
            ->byCategory($this->categoryFilter ?: null)
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);

        $categories = Category::active()->ordered()->get();

        return view('livewire.admin.products.index', compact('products', 'categories'))
            ->layout('layouts.admin', [
                'title' => 'Produits',
                'breadcrumb' => 'Produits',
            ]);
    }
}
