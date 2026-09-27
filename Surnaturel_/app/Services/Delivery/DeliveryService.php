<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryMode;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Services\Sales\OrderWorkflow;
use App\Services\Support\NumberGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Livraisons : À livrer → En cours → Livrée / Échec / Reportée.
 */
class DeliveryService
{
    public function __construct(private OrderWorkflow $workflow, private NumberGenerator $numbers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(Order $order, DeliveryMode $mode, array $data, User $user): Delivery
    {
        if ($order->status !== OrderStatus::Ready) {
            throw new BusinessRuleException('Seule une commande « Prête » peut être planifiée en livraison.');
        }

        return Delivery::query()->create($data + [
            'number' => $this->numbers->next(NumberGenerator::Delivery),
            'order_id' => $order->id,
            'mode' => $mode,
            'status' => DeliveryStatus::Pending,
            'recipient_name' => $data['recipient_name'] ?? $order->client->name,
            'phone' => $data['phone'] ?? $order->client->phone,
            'address' => $data['address'] ?? $order->client->address,
            'city' => $data['city'] ?? $order->client->city,
        ]);
    }

    public function dispatch(Delivery $delivery, User $user): Delivery
    {
        if (! in_array($delivery->status, [DeliveryStatus::Pending, DeliveryStatus::Postponed, DeliveryStatus::Failed], true)) {
            throw new BusinessRuleException('Livraison déjà en cours ou terminée.');
        }

        return DB::transaction(function () use ($delivery, $user): Delivery {
            $delivery->update(['status' => DeliveryStatus::InProgress, 'driver_id' => $delivery->driver_id ?? $user->id]);

            if ($delivery->mode !== DeliveryMode::Pickup && $delivery->order->status === OrderStatus::Ready) {
                $this->workflow->transition($delivery->order, OrderStatus::OutForDelivery, $user, "Livraison {$delivery->number} en cours");
            }

            return $delivery;
        });
    }

    public function complete(Delivery $delivery, User $user, ?UploadedFile $signature = null, ?UploadedFile $photo = null, ?string $observations = null): Delivery
    {
        if ($delivery->order->balanceDue() > 0 && ! $delivery->order->client->allow_credit && ! $user->can('deliveries.release_unpaid')) {
            throw new BusinessRuleException('Commande non soldée : remise impossible sans autorisation.');
        }

        return DB::transaction(function () use ($delivery, $user, $signature, $photo, $observations): Delivery {
            $delivery->update([
                'status' => DeliveryStatus::Delivered,
                'delivered_at' => now(),
                'driver_id' => $delivery->driver_id ?? $user->id,
                'signature_path' => $signature?->store("deliveries/{$delivery->id}", 'local') ?? $delivery->signature_path,
                'photo_path' => $photo?->store("deliveries/{$delivery->id}", 'local') ?? $delivery->photo_path,
                'observations' => $observations ?? $delivery->observations,
            ]);

            $this->workflow->transition($delivery->order->refresh(), OrderStatus::Delivered, $user, "Livraison {$delivery->number} effectuée");

            return $delivery;
        });
    }

    public function fail(Delivery $delivery, User $user, string $reason, bool $postpone = false, ?string $newDate = null): Delivery
    {
        if (blank($reason)) {
            throw new BusinessRuleException('Le motif est obligatoire.');
        }

        return DB::transaction(function () use ($delivery, $user, $reason, $postpone, $newDate): Delivery {
            $delivery->update([
                'status' => $postpone ? DeliveryStatus::Postponed : DeliveryStatus::Failed,
                'failure_reason' => $reason,
                'scheduled_at' => $newDate ?? $delivery->scheduled_at,
            ]);

            if ($delivery->order->status === OrderStatus::OutForDelivery) {
                $this->workflow->transition($delivery->order, OrderStatus::Ready, $user, ($postpone ? 'Livraison reportée : ' : 'Échec de livraison : ').$reason);
            }

            return $delivery;
        });
    }
}
