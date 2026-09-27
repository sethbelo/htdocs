<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\DeliveryStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasEncryptedRouteKey, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'mode' => DeliveryMode::class,
            'status' => DeliveryStatus::class,
            'scheduled_at' => 'datetime',
            'delivered_at' => 'datetime',
            'fee' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
