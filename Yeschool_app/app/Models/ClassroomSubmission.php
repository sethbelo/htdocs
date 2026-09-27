<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassroomSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_assessment_id',
        'student_id',
        'status',
        'answers',
        'score',
        'teacher_comments',
        'parent_signature'
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'float'
    ];

    public function assessment()
    {
        return $this->belongsTo(ClassroomAssessment::class, 'classroom_assessment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
