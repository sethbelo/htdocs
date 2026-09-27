<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'name', 'abbreviation',
        'base_unit_id', 'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
    ];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits()
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Convert a quantity from this unit to the base unit.
     */
    public function convertToBase(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert a quantity from the base unit to this unit.
     */
    public function convertFromBase(float $quantity): float
    {
        return $this->conversion_factor > 0
            ? $quantity / $this->conversion_factor
            : $quantity;
    }
}
