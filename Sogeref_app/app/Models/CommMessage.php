<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Message entrant ou sortant (statuts : queued, sent, delivered, read, failed, received). */
class CommMessage extends Model
{
    use BelongsToCompany;

    public const STATUSES = ['queued' => 'En file', 'sent' => 'Envoyé', 'delivered' => 'Distribué', 'read' => 'Lu', 'failed' => 'Échec', 'received' => 'Reçu'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['media' => 'array', 'sent_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommCampaign::class, 'campaign_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where($query->qualifyColumn('channel'), $channel);
    }

    public function isInbound(): bool
    {
        return $this->direction === 'in';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
