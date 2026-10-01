<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use BelongsToCompany, HasEncryptedRouteKey;

    protected $attributes = ['status' => 'draft'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['lines' => 'array', 'days_worked' => 'decimal:2', 'overtime_hours' => 'decimal:2', 'gross' => 'decimal:2', 'taxable' => 'decimal:2', 'cnss_employee' => 'decimal:2', 'ipr' => 'decimal:2', 'other_deductions' => 'decimal:2', 'net' => 'decimal:2', 'employer_charges' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
