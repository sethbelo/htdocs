<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['overtime_hours' => 'decimal:2', 'daily_rate' => 'decimal:2'];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /** Fraction de journée payée selon le statut de présence. */
    public function dayFraction(): float
    {
        return match ($this->status) {
            'present', 'late' => 1.0,
            'half' => (float) config('erp.payroll.half_day_ratio', .5),
            default => 0.0,
        };
    }
}
