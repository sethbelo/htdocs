<?php

namespace App\Livewire\Kitchen;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Component;

class Display extends Component
{
    // ── Filters ────────────────────────────────
    public string $viewMode = 'all'; // all, pending, preparing, ready

    public bool $showCompleted = false;

    public string $station = 'all'; // all, kitchen, bar, desserts

    // ── Sound ────────────────────────────────
    public bool $soundEnabled = true;

    // ── Stats ────────────────────────────────
    public int $newOrderCount = 0;

    protected $listeners = ['refreshKds' => '$refresh'];

    public function boot(): void
    {
        abort_unless(
            auth()->user()?->establishment?->kitchenModuleEnabled(),
            403,
            'Le module Cuisine est désactivé pour cet établissement.'
        );
    }

    // ── Item Actions ────────────────────────────────
    public function startPreparing(string $itemId): void
    {
        $item = OrderItem::find($itemId);
        if (! $item || $item->kitchen_status !== 'pending') {
            return;
        }

        $item->update([
            'kitchen_status' => 'preparing',
        ]);

        // Update order status if not already preparing
        $order = $item->order;
        if ($order && $order->status === OrderStatus::Confirmed) {
            $order->update(['status' => OrderStatus::Preparing]);
        }
    }

    public function markReady(string $itemId): void
    {
        $item = OrderItem::find($itemId);
        if (! $item || $item->kitchen_status !== 'preparing') {
            return;
        }

        $item->update([
            'kitchen_status' => 'ready',
            'prepared_at' => now(),
        ]);

        // Check if all items of this order are ready
        $order = $item->order;
        if ($order) {
            $allReady = $order->items()->where('kitchen_status', '!=', 'ready')->doesntExist();
            if ($allReady) {
                $order->update(['status' => OrderStatus::Ready]);
            }
        }
    }

    public function markServed(string $itemId): void
    {
        $item = OrderItem::find($itemId);
        if (! $item || $item->kitchen_status !== 'ready') {
            return;
        }

        $item->update([
            'kitchen_status' => 'served',
            'served_at' => now(),
        ]);

        // Check if all items of this order are served
        $order = $item->order;
        if ($order) {
            $allServed = $order->items()->where('kitchen_status', '!=', 'served')->doesntExist();
            if ($allServed) {
                $order->update(['status' => OrderStatus::Served]);
            }
        }
    }

    // ── Bulk Order Actions ────────────────────────────────
    public function startOrderPreparing(string $orderId): void
    {
        $order = Order::find($orderId);
        if (! $order) {
            return;
        }

        $order->items()->where('kitchen_status', 'pending')
            ->update(['kitchen_status' => 'preparing']);

        $order->update(['status' => OrderStatus::Preparing]);
    }

    public function markOrderReady(string $orderId): void
    {
        $order = Order::find($orderId);
        if (! $order) {
            return;
        }

        $order->items()->where('kitchen_status', 'preparing')
            ->update(['kitchen_status' => 'ready', 'prepared_at' => now()]);

        // Check remaining non-ready items
        $allReady = $order->items()->where('kitchen_status', '!=', 'ready')
            ->where('kitchen_status', '!=', 'served')->doesntExist();
        if ($allReady) {
            $order->update(['status' => OrderStatus::Ready]);
        }
    }

    public function markOrderServed(string $orderId): void
    {
        $order = Order::find($orderId);
        if (! $order) {
            return;
        }

        $order->items()->whereIn('kitchen_status', ['ready', 'preparing'])
            ->update(['kitchen_status' => 'served', 'served_at' => now()]);

        $order->update(['status' => OrderStatus::Served]);
    }

    public function recallOrder(string $orderId): void
    {
        $order = Order::find($orderId);
        if (! $order) {
            return;
        }

        $order->items()->update([
            'kitchen_status' => 'pending',
            'prepared_at' => null,
            'served_at' => null,
        ]);

        $order->update(['status' => OrderStatus::Confirmed]);
    }

    // ── Render ────────────────────────────────
    public function render()
    {
        $establishmentId = auth()->user()->establishment_id;

        // Active kitchen orders (not completed/cancelled/served)
        $query = Order::where('establishment_id', $establishmentId)
            ->whereIn('status', [
                OrderStatus::Confirmed,
                OrderStatus::Preparing,
                OrderStatus::Ready,
                ...(($this->showCompleted) ? [OrderStatus::Served] : []),
            ])
            ->with(['items.product', 'table', 'user']);

        // Filter by kitchen status
        if ($this->viewMode !== 'all') {
            $query->whereHas('items', fn ($q) => $q->where('kitchen_status', $this->viewMode));
        }

        $orders = $query->orderBy('created_at', 'asc')->get();

        // Group by status for column display
        $pendingOrders = $orders->filter(fn ($o) => $o->status === OrderStatus::Confirmed);
        $preparingOrders = $orders->filter(fn ($o) => $o->status === OrderStatus::Preparing);
        $readyOrders = $orders->filter(fn ($o) => $o->status === OrderStatus::Ready);
        $servedOrders = $orders->filter(fn ($o) => $o->status === OrderStatus::Served);

        // Stats
        $stats = [
            'pending' => $pendingOrders->count(),
            'preparing' => $preparingOrders->count(),
            'ready' => $readyOrders->count(),
            'served' => $servedOrders->count(),
            'total_today' => Order::where('establishment_id', $establishmentId)->today()->count(),
        ];

        return view('livewire.kitchen.display', compact(
            'orders', 'pendingOrders', 'preparingOrders', 'readyOrders', 'servedOrders', 'stats'
        ))->layout('layouts.kds', ['title' => 'Cuisine']);
    }
}
