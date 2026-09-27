<?php

namespace App\Livewire\Concerns;

use App\Models\Site;
use Illuminate\Support\Collection;

/**
 * Filtre par siège commun aux écrans : restreint aux sièges accessibles de l'utilisateur.
 */
trait ScopesToSite
{
    public ?int $siteId = null;

    /**
     * @return Collection<int, Site>
     */
    public function accessibleSites(): Collection
    {
        return Site::query()->whereIn('id', auth()->user()->accessibleSiteIds())->orderBy('name')->get();
    }

    public function updatedSiteId(?int $value): void
    {
        if ($value && ! auth()->user()->canAccessSite($value)) {
            $this->siteId = null;
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
