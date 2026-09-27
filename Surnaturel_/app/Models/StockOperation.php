<?php

namespace App\Models;

use App\Enums\StockOperationReason;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Bon d'entrée ou de sortie magasin : qui l'a saisi, qui a physiquement livré ou retiré la marchandise.
 */
class StockOperation extends Model
{
    use TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'reason' => StockOperationReason::class,
            'operated_at' => 'datetime',
            'total_value' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'source');
    }

    public function isEntry(): bool
    {
        return $this->type === 'entry';
    }

    public function typeLabel(): string
    {
        return $this->isEntry() ? 'Bon d’entrée' : 'Bon de sortie';
    }

    public function counterpartyLabel(): string
    {
        return match ($this->counterparty_type) {
            'supplier' => 'Fournisseur : '.$this->supplier?->name,
            'employee' => 'Employé : '.$this->employee?->fullName(),
            'department' => 'Service : '.$this->department?->name,
            default => 'Autre',
        };
    }
}
