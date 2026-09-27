<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncReceipt extends Model
{
    protected $fillable = [
        'event_uuid', 'company_id', 'point_of_sale_id', 'sale_id',
        'payload_checksum', 'response', 'received_at',
    ];

    protected $casts = ['response' => 'array', 'received_at' => 'datetime'];
}
