<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view') && $user->canAccessSite($order->site_id);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update') && $user->canAccessSite($order->site_id) && ! $order->status->isTerminal();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.cancel') && $user->canAccessSite($order->site_id) && ! $order->status->isTerminal();
    }

    public function print(User $user, Order $order): bool
    {
        return ($user->can('orders.print') || $user->can('cash.collect')) && $user->canAccessSite($order->site_id);
    }
}
