<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'name', 'device_name', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function sessions()
    {
        return $this->hasMany(CashSession::class);
    }

    public function activeSession()
    {
        return $this->hasOne(CashSession::class)->where('status', 'open');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
