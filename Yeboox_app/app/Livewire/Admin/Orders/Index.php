<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $dateFilter = '';

    public string $typeFilter = '';

    public bool $showDetailsModal = false;

    public bool $showCancelModal = false;

    public ?string $selectedId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function viewOrder(string $id): void
    {
        abort_unless(auth()->user()->can('orders.view'), 403);
        Order::findOrFail($id);
        $this->selectedId = $id;
        $this->showDetailsModal = true;
    }

    public function requestCancel(string $id): void
    {
        abort_unless(auth()->user()->can('orders.cancel'), 403);
        $order = Order::findOrFail($id);
        abort_if(in_array($order->status->value, ['completed', 'cancelled'], true), 422);
        $this->selectedId = $id;
        $this->showCancelModal = true;
    }

    public function confirmCancel(): void
    {
        abort_unless(auth()->user()->can('orders.cancel'), 403);
        $order = Order::findOrFail($this->selectedId);
        abort_if($order->status->value === 'completed', 422);
        $order->update(['status' => 'cancelled']);
        $order->table?->update(['status' => 'free']);
        $this->reset(['showCancelModal', 'selectedId']);
        session()->flash('success', 'Commande annulée.');
    }

    public function render()
    {
        $establishmentId = auth()->user()->establishment_id;

        $orders = Order::where('establishment_id', $establishmentId)
            ->with(['user', 'table', 'customer', 'items'])
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter, fn ($q) => $q->whereDate('created_at', $this->dateFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'today_count' => Order::where('establishment_id', $establishmentId)->today()->count(),
            'today_revenue' => Order::where('establishment_id', $establishmentId)->today()->completed()->sum('total'),
            'active' => Order::where('establishment_id', $establishmentId)->active()->count(),
            'avg_ticket' => Order::where('establishment_id', $establishmentId)->today()->completed()->avg('total') ?? 0,
        ];

        $selectedOrder = $this->selectedId
            ? Order::with(['items.product', 'payments.paymentMethod', 'user', 'table', 'customer'])->find($this->selectedId)
            : null;

        return view('livewire.admin.orders.index', compact('orders', 'stats', 'selectedOrder'))
            ->layout('layouts.admin', ['title' => 'Commandes', 'breadcrumb' => 'Commandes']);
    }
}
