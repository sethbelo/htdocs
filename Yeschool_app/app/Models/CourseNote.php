<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_journal_id',
        'teacher_id',
        'title',
        'content',
        'is_published',
        'is_archived',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function classJournal()
    {
        return $this->belongsTo(ClassJournal::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
