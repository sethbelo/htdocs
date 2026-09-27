<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSchool;

#[Fillable(['school_id', 'name', 'address'])]
class Campus extends Model
{
    use HasFactory, HasSchool;
}
