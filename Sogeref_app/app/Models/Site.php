<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    use BelongsToCompany;

    protected $attributes = ['status' => 'planned', 'progress' => 0];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'planned_end_date' => 'date', 'actual_end_date' => 'date', 'budget' => 'decimal:2'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id')->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'site_members')->withPivot(['id', 'role', 'daily_rate', 'joined_at', 'left_at'])->withTimestamps();
    }

    public function equipmentAssignments(): HasMany
    {
        return $this->hasMany(SiteEquipment::class)->latest('assigned_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(SiteReport::class)->latest('report_date');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(SiteEvaluation::class)->latest('evaluated_at');
    }

    public function attendanceSheets(): HasMany
    {
        return $this->hasMany(AttendanceSheet::class)->latest('work_date');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class)->latest('voucher_date');
    }

    public function warehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class);
    }

    public function isDelayed(): bool
    {
        return $this->planned_end_date && $this->planned_end_date->lt(today()) && $this->status !== 'completed';
    }
}
