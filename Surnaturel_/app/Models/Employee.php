<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use BelongsToSite, HasFactory, SoftDeletes, TracksActivity;

    /**
     * Données RH sensibles : chiffrées en base, masquées à la sérialisation, exclues du journal d'activité.
     *
     * @var list<string>
     */
    public const SENSITIVE_ATTRIBUTES = ['national_id', 'base_salary', 'bank_name', 'bank_account', 'mobile_money_number'];

    protected $guarded = ['id'];

    protected $hidden = self::SENSITIVE_ATTRIBUTES;

    /** @var list<string> */
    protected array $activityLogExcept = self::SENSITIVE_ATTRIBUTES;

    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
            'birth_date' => 'date',
            'hired_at' => 'date',
            'left_at' => 'date',
            'is_active' => 'boolean',
            'national_id' => 'encrypted',
            'base_salary' => 'encrypted',
            'bank_name' => 'encrypted',
            'bank_account' => 'encrypted',
            'mobile_money_number' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class)->latest('starts_on');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class)->latest('starts_on');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function baseSalary(): float
    {
        return (float) ($this->base_salary ?? 0);
    }
}
