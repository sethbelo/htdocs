<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderNeedsAttention extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public string $message) {}

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
            'title' => "Commande {$this->order->number}",
            'message' => $this->message,
            'url' => route('orders.show', $this->order),
            'icon' => 'clipboard-document-list',
        ];
    }
}
