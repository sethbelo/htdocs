<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'name', 'color', 'sort_order', 'is_active',
    ];

    protected $casts = ['sort_order' => 'integer', 'is_active' => 'boolean'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
