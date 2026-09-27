<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturingOperation extends Model
{
    protected $attributes = ['status' => 'todo'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['planned_hours' => 'decimal:2', 'actual_hours' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
