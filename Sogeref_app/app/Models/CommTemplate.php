<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Modèle de message réutilisable (placeholders {prenom}, {nom}, {societe}…). */
class CommTemplate extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where($query->qualifyColumn('channel'), $channel);
    }
}
