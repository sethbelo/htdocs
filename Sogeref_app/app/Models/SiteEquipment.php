<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteEquipment extends Model
{
    protected $table = 'site_equipment';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['assigned_at' => 'date', 'returned_at' => 'date'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
