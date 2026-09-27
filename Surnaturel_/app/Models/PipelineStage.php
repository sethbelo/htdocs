<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_won' => 'boolean', 'is_lost' => 'boolean'];
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class)->orderBy('position');
    }
}
