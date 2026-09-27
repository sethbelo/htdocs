<?php

namespace App\Traits;

use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasSchool
{
    /**
     * Boot the trait to add the global scope.
     */
    protected static function bootHasSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function ($model) {
            if (Auth::hasUser() && Auth::user()->school_id && !$model->school_id) {
                $model->school_id = Auth::user()->school_id;
            }
        });
    }

    /**
     * Relationship to the school.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
