<?php

namespace App\Models;

use App\Enums\InteractionChannel;
use App\Enums\InteractionOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientInteraction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'channel' => InteractionChannel::class,
            'outcome' => InteractionOutcome::class,
            'occurred_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
