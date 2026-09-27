<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'slug', 'legal_name', 'email', 'phone', 'address',
        'rccm', 'nif', 'tva_number', 'base_currency', 'secondary_currency',
        'exchange_rate', 'timezone', 'status', 'logo_path', 'settings',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'settings' => 'array',
    ];

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }

    public function pointsOfSale(): HasMany
    {
        return $this->hasMany(PointOfSale::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
