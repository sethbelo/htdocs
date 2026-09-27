<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['salary'];

    protected function casts(): array
    {
        return [
            'type' => EmploymentStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'salary' => 'encrypted',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
