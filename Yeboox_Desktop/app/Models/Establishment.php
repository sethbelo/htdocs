<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establishment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'uuid', 'name', 'code', 'email', 'phone', 'address',
        'timezone', 'logo_path', 'is_active', 'settings',
    ];

    protected $casts = ['is_active' => 'boolean', 'settings' => 'array'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pointsOfSale(): HasMany
    {
        return $this->hasMany(PointOfSale::class);
    }
}
