<?php

namespace App\Models\Concerns;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Spécifications techniques d'impression partagées par les lignes de devis et de commande.
 */
trait HasPrintSpecifications
{
    /**
     * @var list<string>
     */
    public const SPEC_FIELDS = [
        'format', 'width', 'height', 'dimension_unit', 'paper', 'grammage', 'colors', 'print_sides',
        'finishing', 'binding', 'lamination', 'cutting', 'film_coating', 'pages',
    ];

    public function initializeHasPrintSpecifications(): void
    {
        $this->mergeCasts([
            'quantity' => 'decimal:3',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'lamination' => 'boolean',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'total' => 'decimal:2',
            'specs' => 'array',
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Résumé lisible des spécifications (ticket, écran graphiste, salle d'impression).
     */
    public function specSummary(): string
    {
        return collect([
            $this->format,
            $this->width && $this->height ? rtrim(rtrim((string) $this->width, '0'), '.').'×'.rtrim(rtrim((string) $this->height, '0'), '.').' '.$this->dimension_unit : null,
            $this->paper,
            $this->grammage ? $this->grammage.' g/m²' : null,
            $this->colors,
            match ($this->print_sides) {
                'recto' => 'Recto',
                'recto_verso' => 'Recto/Verso',
                default => null,
            },
            $this->finishing,
            $this->binding ? 'Reliure '.$this->binding : null,
            $this->lamination ? 'Plastifié' : null,
            $this->cutting ? 'Découpe '.$this->cutting : null,
            $this->film_coating ? 'Pelliculage '.$this->film_coating : null,
            $this->pages ? $this->pages.' pages' : null,
        ])->filter()->implode(' · ');
    }

    public function lineSubtotal(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }
}
