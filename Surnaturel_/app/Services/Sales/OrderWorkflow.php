<?php

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Machine à états des commandes d'imprimerie : contrôle des transitions et historisation systématique.
 */
class OrderWorkflow
{
    public function transition(Order $order, OrderStatus $target, ?User $user = null, ?string $comment = null): Order
    {
        $from = $order->status;

        if ($from === $target) {
            return $order;
        }

        if (! $from->canTransitionTo($target)) {
            throw new BusinessRuleException("Transition impossible : {$from->label()} → {$target->label()}.");
        }

        if ($target === OrderStatus::Cancelled && blank($comment)) {
            throw new BusinessRuleException('Le motif d’annulation est obligatoire.');
        }

        return $this->apply($order, $target, $user, $comment, [
            'status_before_suspension' => $target === OrderStatus::Suspended ? $from : $order->status_before_suspension,
            'cancellation_reason' => $target === OrderStatus::Cancelled ? $comment : $order->cancellation_reason,
            'closed_at' => $target === OrderStatus::Closed ? now() : $order->closed_at,
        ]);
    }

    /**
     * Reprise d'une commande suspendue sur son statut précédent.
     */
    public function resume(Order $order, ?User $user = null, ?string $comment = null): Order
    {
        if ($order->status !== OrderStatus::Suspended || $order->status_before_suspension === null) {
            throw new BusinessRuleException('Seule une commande suspendue peut être reprise.');
        }

        return $this->apply($order, $order->status_before_suspension, $user, $comment ?? 'Reprise après suspension', [
            'status_before_suspension' => null,
        ]);
    }

    /**
     * Enchaîne plusieurs transitions (ex. Payée → Conception), en ignorant celles déjà atteintes.
     *
     * @param  list<OrderStatus>  $path
     */
    public function advanceThrough(Order $order, array $path, ?User $user = null, ?string $comment = null): Order
    {
        foreach ($path as $status) {
            if ($order->status !== $status) {
                $order = $this->transition($order, $status, $user, $comment);
            }
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function apply(Order $order, OrderStatus $target, ?User $user, ?string $comment, array $attributes): Order
    {
        $from = $order->status;
        $user ??= auth()->user();

        DB::transaction(function () use ($order, $target, $user, $comment, $attributes, $from): void {
            $order->update(['status' => $target] + $attributes);

            $order->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $target,
                'user_id' => $user?->id,
                'comment' => $comment,
                'created_at' => now(),
            ]);
        });

        OrderStatusChanged::dispatch($order, $from, $target, $user);

        return $order;
    }
}
