<?php

namespace App\Models;

use App\Enums\TableStatus;
use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'zone_id',
        'name', 'number', 'capacity', 'shape', 'status',
        'pos_x', 'pos_y', 'width', 'height', 'rotation',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'status' => TableStatus::class,
        'capacity' => 'integer',
        'pos_x' => 'integer',
        'pos_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'rotation' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', TableStatus::Free)->where('is_active', true);
    }

    public function scopeInZone($query, ?string $zoneId)
    {
        if (! $zoneId) {
            return $query;
        }

        return $query->where('zone_id', $zoneId);
    }

    public function isAvailable(): bool
    {
        return $this->status === TableStatus::Free;
    }

    public function isOccupied(): bool
    {
        return $this->status === TableStatus::Occupied;
    }

    public function occupy(): void
    {
        $this->update(['status' => TableStatus::Occupied]);
    }

    public function release(): void
    {
        $this->update(['status' => TableStatus::Free]);
    }

    public function markCleaning(): void
    {
        $this->update(['status' => TableStatus::Cleaning]);
    }
}
