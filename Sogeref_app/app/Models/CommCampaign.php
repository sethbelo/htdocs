<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campagne de masse. audience = {types[], segments[], cities[], contact_ids[], only_opted_in}
 * stats = {total, sent, failed, delivered}.
 */
class CommCampaign extends Model
{
    use BelongsToCompany, HasEncryptedRouteKey;

    public const STATUSES = ['draft' => 'Brouillon', 'scheduled' => 'Programmée', 'sending' => 'En cours d’envoi', 'sent' => 'Envoyée', 'cancelled' => 'Annulée'];

    protected $guarded = [];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['audience' => 'array', 'stats' => 'array', 'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CommCampaignRecipient::class, 'campaign_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommMessage::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where($query->qualifyColumn('channel'), $channel);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }

    public function stat(string $key): int
    {
        return (int) data_get($this->stats, $key, 0);
    }
}
