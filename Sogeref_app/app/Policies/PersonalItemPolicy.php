<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Éléments de l'Espace personnel (notes, tableurs, tâches) :
 * seul leur propriétaire, dans la société courante, peut les consulter ou les modifier.
 */
class PersonalItemPolicy
{
    public function view(User $user, Model $item): bool
    {
        return $this->owns($user, $item);
    }

    public function update(User $user, Model $item): bool
    {
        return $this->owns($user, $item);
    }

    public function delete(User $user, Model $item): bool
    {
        return $this->owns($user, $item);
    }

    private function owns(User $user, Model $item): bool
    {
        return (int) $item->user_id === (int) $user->id && (int) $item->company_id === company_id();
    }
}
