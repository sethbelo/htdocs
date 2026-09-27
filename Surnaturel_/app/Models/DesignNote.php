<?php

namespace App\Models;

use App\Enums\DesignNoteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignNote extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => DesignNoteType::class, 'is_done' => 'boolean'];
    }

    public function designJob(): BelongsTo
    {
        return $this->belongsTo(DesignJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
