<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_class_id',
        'academic_year_id',
        'date',
        'status',
        'reason',
        'is_justified',
        'whatsapp_sent',
        'whatsapp_log',
    ];

    protected $casts = [
        'date' => 'date',
        'is_justified' => 'boolean',
        'whatsapp_sent' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
