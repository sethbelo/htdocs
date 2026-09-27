<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    use BelongsToCompany;

    protected $table = 'equipment';

    protected $attributes = ['status' => 'available'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['purchase_value' => 'decimal:2', 'daily_rent_price' => 'decimal:2', 'is_rentable' => 'boolean'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
