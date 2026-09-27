<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdvance extends Model
{
    use BelongsToCompany;

    protected $attributes = ['status' => 'open'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'granted_at' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
