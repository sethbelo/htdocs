<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    public function view(User $user, CashSession $session): bool
    {
        return $session->user_id === $user->id || ($user->can('cash.view') && $user->canAccessSite($session->register->site_id));
    }
}
