<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSyncCredential extends Model
{
    protected $fillable = [
        'company_id', 'point_of_sale_id', 'name', 'token_hash', 'token_hint',
        'is_active', 'last_used_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = ['is_active' => 'boolean', 'last_used_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }
}
