<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDeleteModal = false;

    public ?string $deleteId = null;

    public string $deleteName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(string $id): void
    {
        abort_unless(auth()->user()->can('categories.edit'), 403);
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('categories.delete'), 403);
        $category = Category::withCount('products')->findOrFail($id);
        $this->deleteId = $category->id;
        $this->deleteName = $category->name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('categories.delete'), 403);
        $category = Category::withCount('products')->findOrFail($this->deleteId);
        if ($category->products_count > 0) {
            $this->addError('delete', 'Cette catégorie contient encore des produits. Déplacez-les avant de la supprimer.');

            return;
        }
        $category->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Catégorie supprimée.');
    }

    public function render()
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->ordered()
            ->paginate(20);

        return view('livewire.admin.categories.index', compact('categories'))
            ->layout('layouts.admin', ['title' => 'Catégories', 'breadcrumb' => 'Catégories']);
    }
}
