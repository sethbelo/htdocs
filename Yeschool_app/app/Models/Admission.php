<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'requested_class',
        'status',
        'score',
        'color',
        'initials',
        'date_of_birth',
        'gender',
        'address',
        'previous_school',
        'parent_name',
        'parent_phone',
        'parent_email',
        'medical_notes',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
