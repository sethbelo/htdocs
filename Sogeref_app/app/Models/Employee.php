<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use BelongsToCompany, HasEncryptedRouteKey, SoftDeletes;

    public const CATEGORIES = ['permanent' => 'Permanent', 'contractuel' => 'Contractuel', 'journalier' => 'Journalier', 'stagiaire' => 'Stagiaire'];

    protected $attributes = ['status' => 'active', 'category' => 'permanent', 'pay_type' => 'monthly', 'currency_code' => 'USD'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'hire_date' => 'date', 'end_date' => 'date', 'base_salary' => 'decimal:2', 'daily_rate' => 'decimal:2', 'transport_allowance' => 'decimal:2', 'housing_allowance' => 'decimal:2', 'leave_balance' => 'decimal:2'];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1)));
    }

    public function isDaily(): bool
    {
        return $this->pay_type === 'daily';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceEntry::class);
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_members')->withPivot(['role', 'daily_rate', 'joined_at', 'left_at'])->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
