<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->can('clients.create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->current_company_id === $contact->company_id && $user->can('clients.update');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->current_company_id === $contact->company_id && $user->can('clients.delete');
    }
}
