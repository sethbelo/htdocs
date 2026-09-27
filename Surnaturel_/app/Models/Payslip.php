<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasEncryptedRouteKey;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'gross_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'days_worked' => 'decimal:1',
            'lines' => 'array',
            'paid_at' => 'datetime',
            'dg_approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function dgApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dg_approved_by');
    }

    public function isApprovedByDirection(): bool
    {
        return $this->dg_status === 'approved';
    }
}
