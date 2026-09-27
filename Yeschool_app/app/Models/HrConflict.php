<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'employee_involved_id',
    'reporter_id',
    'title',
    'description',
    'date_reported',
    'severity',
    'status',
    'resolution_notes'
])]
class HrConflict extends Model
{
    /**
     * Get the employee involved in the conflict.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'employee_involved_id');
    }

    /**
     * Get the user who reported/handled the conflict.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
