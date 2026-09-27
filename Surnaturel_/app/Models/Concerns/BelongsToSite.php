<?php

namespace App\Models\Concerns;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cloisonnement multi-sièges : un utilisateur ne voit que les enregistrements de ses sièges,
 * sauf s'il dispose de la vue consolidée (permission « sites.view_all »).
 */
trait BelongsToSite
{
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasConsolidatedView()) {
            return $query;
        }

        return $query->whereIn($this->qualifyColumn('site_id'), $user->accessibleSiteIds());
    }

    public function scopeForSite(Builder $query, ?int $siteId): Builder
    {
        return $query->when($siteId, fn (Builder $q) => $q->where($this->qualifyColumn('site_id'), $siteId));
    }
}
