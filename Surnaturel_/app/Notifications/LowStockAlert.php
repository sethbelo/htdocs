<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product, public string $warehouseName, public float $balance) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Stock faible',
            'message' => "{$this->product->name} : {$this->balance} restant(s) à {$this->warehouseName} (minimum {$this->product->min_stock}).",
            'url' => route('inventory.index', ['alerte' => 1]),
            'icon' => 'exclamation-triangle',
        ];
    }
}
