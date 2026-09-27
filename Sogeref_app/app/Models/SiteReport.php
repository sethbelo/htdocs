<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteReport extends Model
{
    public const WEATHER = ['ensoleille' => 'Ensoleillé', 'nuageux' => 'Nuageux', 'pluie' => 'Pluie', 'orage' => 'Orage'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['report_date' => 'date', 'photos' => 'array'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
