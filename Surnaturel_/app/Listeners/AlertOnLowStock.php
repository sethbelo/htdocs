<?php

namespace App\Listeners;

use App\Events\StockLevelChanged;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Alerte stock faible, au plus une fois par jour et par article/entrepôt.
 */
class AlertOnLowStock
{
    public function handle(StockLevelChanged $event): void
    {
        if ($event->balance > (float) $event->product->min_stock || (float) $event->product->min_stock <= 0) {
            return;
        }

        if (! Cache::add("low-stock:{$event->product->id}:{$event->warehouseId}", true, now()->endOfDay())) {
            return;
        }

        $warehouse = Warehouse::query()->find($event->warehouseId);
        $recipients = User::permission('inventory.create')->where('is_active', true)
            ->whereHas('sites', fn ($q) => $q->where('sites.id', $warehouse?->site_id))
            ->get();

        Notification::send($recipients, new LowStockAlert($event->product, (string) $warehouse?->name, $event->balance));
    }
}
