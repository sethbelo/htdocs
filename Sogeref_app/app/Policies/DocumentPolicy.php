<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->current_company_id === $document->company_id && $user->can('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quotes.create') || $user->can('invoices.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->current_company_id === $document->company_id && $user->can('invoices.create');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->current_company_id === $document->company_id && $user->can('documents.delete');
    }
}
