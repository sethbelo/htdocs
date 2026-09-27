<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['yield_quantity' => 'decimal:4', 'labour_hours' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class);
    }

    public function materialCost(): float
    {
        return round($this->lines->sum(fn ($line) => (float) $line->quantity * (float) $line->product?->purchase_price), 2);
    }
}
