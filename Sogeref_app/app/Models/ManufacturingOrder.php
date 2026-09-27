<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturingOrder extends Model
{
    use BelongsToCompany;

    public const FLOW = ['draft', 'confirmed', 'in_progress', 'quality', 'done'];

    protected $attributes = ['status' => 'draft', 'priority' => 'normale', 'type' => 'fabrication', 'progress' => 0];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['specifications' => 'array', 'quantity' => 'decimal:4', 'planned_start' => 'date', 'planned_end' => 'date', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'labour_cost' => 'decimal:2'];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_id')->withTrashed();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ManufacturingMaterial::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ManufacturingOperation::class)->orderBy('position');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function materialCost(): float
    {
        return round($this->materials->sum(fn ($m) => (float) $m->quantity_planned * (float) $m->unit_cost), 2);
    }

    public function isDelayed(): bool
    {
        return $this->planned_end && $this->planned_end->lt(today()) && ! in_array($this->status, ['done', 'cancelled'], true);
    }
}
