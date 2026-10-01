<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Fil de discussion avec un correspondant sur un canal (WhatsApp, SMS, e-mail). */
class CommConversation extends Model
{
    use BelongsToCompany, HasEncryptedRouteKey;

    public const CHANNELS = ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'E-mail'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'unread_count' => 'integer'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(CommMessage::class, 'conversation_id')->latestOfMany();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where($query->qualifyColumn('channel'), $channel);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('status'), 'open');
    }

    /** Nom affiché : contact lié sinon adresse brute. */
    public function getDisplayNameAttribute(): string
    {
        return $this->contact?->display_name ?? $this->address;
    }

    public function markRead(): void
    {
        if ($this->unread_count) {
            $this->update(['unread_count' => 0]);
        }
    }
}
