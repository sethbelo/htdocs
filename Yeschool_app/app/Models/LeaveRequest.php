<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['teacher_id', 'start_date', 'end_date', 'reason', 'status', 'interim_teacher_id', 'type'])]
class LeaveRequest extends Model
{
    use HasFactory;

    public static function calculateDays($startDate, $endDate, $type): int
    {
        if (!$startDate || !$endDate) return 0;
        try {
            $start = \Illuminate\Support\Carbon::parse($startDate);
            $end = \Illuminate\Support\Carbon::parse($endDate);
        } catch (\Exception $e) {
            return 0;
        }
        if ($start->gt($end)) return 0;

        if (in_array($type, ['RTT', 'Congé payé légal'])) {
            // Jours ouvrés (Lun-Ven)
            $days = 0;
            $current = $start->copy();
            while ($current->lte($end)) {
                if (!$current->isWeekend()) {
                    $days++;
                }
                $current->addDay();
            }
            return $days;
        } else {
            // Jours calendaires
            return $start->diffInDays($end) + 1;
        }
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function interimTeacher()
    {
        return $this->belongsTo(Teacher::class, 'interim_teacher_id');
    }
}
