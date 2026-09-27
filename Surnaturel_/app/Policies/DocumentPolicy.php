<?php

namespace App\Policies;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if (! $user->can('documents.view') || ($document->site_id && ! $user->canAccessSite($document->site_id))) {
            return false;
        }

        if ($document->category === DocumentCategory::Hr || $document->is_sensitive) {
            return $user->can('employees.view_sensitive') || $document->uploaded_by === $user->id;
        }

        return true;
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('documents.update') && $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.delete') && $this->view($user, $document);
    }
}
