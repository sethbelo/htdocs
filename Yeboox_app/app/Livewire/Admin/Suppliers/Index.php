<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
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

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('suppliers.delete'), 403);
        $supplier = Supplier::findOrFail($id);
        $this->deleteId = $supplier->id;
        $this->deleteName = $supplier->name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('suppliers.delete'), 403);
        Supplier::findOrFail($this->deleteId)->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Fournisseur supprimé.');
    }

    public function toggleStatus(string $id): void
    {
        abort_unless(auth()->user()->can('suppliers.edit'), 403);
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => ! $supplier->is_active]);
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->forEstablishment()
            ->search($this->search)
            ->withCount('purchaseOrders')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.suppliers.index', compact('suppliers'))
            ->layout('layouts.admin', ['title' => 'Fournisseurs', 'breadcrumb' => 'Fournisseurs']);
    }
}
