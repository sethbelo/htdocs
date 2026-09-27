<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['daily_price' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
