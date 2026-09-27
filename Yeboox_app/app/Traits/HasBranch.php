<?php

namespace App\Traits;

use App\Models\Branch;

trait HasBranch
{
    /**
     * Boot the trait — auto-scope to current user's branch.
     */
    protected static function bootHasBranch(): void
    {
        static::creating(function ($model) {
            if (empty($model->branch_id) && auth()->check()) {
                $model->branch_id = auth()->user()->branch_id;
            }
        });
    }

    /**
     * Relationship: belongs to a branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope: filter by branch.
     */
    public function scopeForBranch($query, ?string $branchId = null)
    {
        $branchId = $branchId ?? auth()->user()?->branch_id;

        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
