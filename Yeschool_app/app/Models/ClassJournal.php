<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'school_class_id',
        'subject_id',
        'date',
        'start_time',
        'end_time',
        'title',
        'context',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function courseNote()
    {
        return $this->hasOne(CourseNote::class);
    }
}
