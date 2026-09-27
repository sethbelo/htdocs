<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\User;
use App\Notifications\OrderNeedsAttention;
use App\Notifications\OrderReadyForClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Oriente chaque étape du workflow vers l'équipe concernée (graphisme, impression, commercial, client).
 */
class NotifyTeamsOnOrderStatus
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        [$recipients, $message] = match ($event->to) {
            OrderStatus::AwaitingPayment => [$this->withPermission('cash.collect', $order->site_id), 'Nouvelle commande à encaisser.'],
            OrderStatus::InDesign => [$this->withPermission('design.approve', $order->site_id), 'Nouvelle commande à concevoir.'],
            OrderStatus::ProofPending => [collect([$order->salesRep])->filter(), 'BAT prêt : à faire valider par le client.'],
            OrderStatus::PrintQueue => [$this->withPermission('production.approve', $order->site_id), 'Commande en file d’impression.'],
            OrderStatus::Rework => [$this->withPermission('production.approve', $order->site_id), 'Commande à refaire (contrôle qualité).'],
            OrderStatus::Ready => [collect([$order->salesRep])->filter()->merge($this->withPermission('deliveries.create', $order->site_id)), 'Commande prête à livrer / retirer.'],
            OrderStatus::Cancelled, OrderStatus::Suspended => [collect([$order->salesRep])->filter(), "Commande {$event->to->label()}."],
            default => [collect(), null],
        };

        $recipients = $recipients->reject(fn (User $user): bool => $user->is($event->user))->unique('id');

        if ($message && $recipients->isNotEmpty()) {
            Notification::send($recipients, new OrderNeedsAttention($order, $message));
        }

        if ($event->to === OrderStatus::Ready) {
            $order->client->notify(new OrderReadyForClient($order));
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function withPermission(string $permission, int $siteId): Collection
    {
        return User::permission($permission)->where('is_active', true)
            ->whereHas('sites', fn ($q) => $q->where('sites.id', $siteId))
            ->get();
    }
}
