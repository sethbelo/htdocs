<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'name',
    'description',
    'manager_id'
])]
class Department extends Model
{
    /**
     * Get the employees associated with the department.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'department_id');
    }

    /**
     * Get the manager of the department.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'manager_id');
    }
}
