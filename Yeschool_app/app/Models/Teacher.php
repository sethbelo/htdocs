<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['first_name', 'last_name', 'avatar', 'cv_path', 'cover_letter_path', 'email', 'phone', 'specialty', 'salary', 'annual_leave_allowance', 'rtt_allowance', 'status', 'contract_signed_at', 'contract_ended_at', 'recruitment_notes', 'date_of_birth', 'civil_status', 'spouse_name', 'children_count', 'children_names', 'academic_qualification', 'job_type', 'folder_number', 'manager_name', 'place_of_birth', 'acting_as', 'job', 'department_id'])]
class Teacher extends Model
{
    use HasFactory;

    protected $casts = [
        'contract_signed_at' => 'date',
        'contract_ended_at' => 'date',
        'date_of_birth' => 'date',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
