<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model): void {
            if (! $model->company_id && auth()->check()) {
                $model->company_id = auth()->user()->current_company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCurrentCompany(Builder $query): Builder
    {
        $companyId = auth()->user()?->current_company_id;

        return $companyId ? $query->where($query->qualifyColumn('company_id'), $companyId) : $query;
    }
}
