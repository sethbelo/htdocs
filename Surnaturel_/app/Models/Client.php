<?php

namespace App\Models;

use App\Enums\ClientType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use BelongsToSite, HasEncryptedRouteKey, HasFactory, Notifiable, SoftDeletes, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'allow_credit' => 'boolean',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function loyaltyAccount(): HasOne
    {
        return $this->hasOne(LoyaltyAccount::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Solde client = factures émises non soldées.
     */
    public function outstandingBalance(): float
    {
        return (float) $this->invoices()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as due')
            ->value('due');
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->whatsapp ?: $this->phone;
    }

    public function displayName(): string
    {
        return $this->company_name ? "{$this->company_name} ({$this->name})" : $this->name;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->where('name', 'like', "%{$term}%")
            ->orWhere('company_name', 'like', "%{$term}%")
            ->orWhere('number', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")));
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(ClientInteraction::class)->latest('occurred_at');
    }

    public function followUpOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_owner_id');
    }

    /**
     * Numéro WhatsApp international (chiffres uniquement), RDC par défaut.
     */
    public function whatsappNumber(): ?string
    {
        $raw = preg_replace('/\D+/', '', (string) ($this->whatsapp ?: $this->phone));

        if ($raw === '') {
            return null;
        }

        return match (true) {
            str_starts_with($raw, '00') => substr($raw, 2),
            str_starts_with($raw, '0') => '243'.substr($raw, 1),
            strlen($raw) === 9 => '243'.$raw,
            default => $raw,
        };
    }
}
