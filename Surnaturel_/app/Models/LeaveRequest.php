<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => LeaveStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'days' => 'decimal:1',
            'manager_approved_at' => 'datetime',
            'hr_approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
