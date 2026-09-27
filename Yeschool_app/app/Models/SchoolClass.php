<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'capacity',
        'academic_year',
        'status',
        'enrolled_students',
        'head_teacher_id'
    ];

    public function headTeacher()
    {
        return $this->belongsTo(Teacher::class, 'head_teacher_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'school_class_id');
    }
}
