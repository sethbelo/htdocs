<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Événement durable écrit avec la vente locale avant toute tentative réseau. */
class SyncOutbox extends Model
{
    protected $table = 'sync_outbox';

    protected $fillable = [
        'event_uuid', 'company_id', 'establishment_id', 'point_of_sale_id',
        'aggregate_type', 'aggregate_uuid', 'event_type', 'payload', 'status',
        'attempts', 'available_at', 'last_attempt_at', 'synced_at', 'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
