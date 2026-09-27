<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['annual_quota' => 'decimal:1', 'is_paid' => 'boolean', 'requires_document' => 'boolean'];
    }
}
