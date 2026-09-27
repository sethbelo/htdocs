<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function view(User $user, Client $client): bool
    {
        return $user->can('clients.view') && ($client->site_id === null || $user->canAccessSite($client->site_id));
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can('clients.update') && ($client->site_id === null || $user->canAccessSite($client->site_id));
    }
}
