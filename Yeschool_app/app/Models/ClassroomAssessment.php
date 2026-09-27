<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassroomAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_class_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'title',
        'type',
        'max_score',
        'due_date',
        'instructions',
        'questions'
    ];

    protected $casts = [
        'questions' => 'array',
        'due_date' => 'datetime'
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function submissions()
    {
        return $this->hasMany(ClassroomSubmission::class, 'classroom_assessment_id');
    }
}
