<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cahier de conception : brief créatif et fiche technique d'impression d'un travail graphique.
 */
class DesignBrief extends Model
{
    /**
     * Contrôles pré-presse à cocher avant de transmettre à la salle d'impression.
     *
     * @var array<string, string>
     */
    public const CHECKLIST = [
        'cmyk' => 'Couleurs converties en CMJN',
        'resolution' => 'Images en 300 dpi minimum',
        'bleed' => 'Fonds perdus et traits de coupe',
        'fonts' => 'Polices vectorisées / incorporées',
        'proofread' => 'Textes relus (orthographe, coordonnées)',
        'client_ok' => 'Validation client (BAT) obtenue',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:1',
            'height_mm' => 'decimal:1',
            'bleed_mm' => 'decimal:1',
            'checklist' => 'array',
            'validated_for_print_at' => 'datetime',
        ];
    }

    public function designJob(): BelongsTo
    {
        return $this->belongsTo(DesignJob::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_for_print_by');
    }

    public function isValidatedForPrint(): bool
    {
        return $this->validated_for_print_at !== null;
    }

    /**
     * « 210 × 297 mm (A4) · 4/4 CMJN · Couché mat 135 g · 500 ex. »
     */
    public function printSummary(): string
    {
        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');

        return collect([
            $this->width_mm && $this->height_mm ? $num($this->width_mm).' × '.$num($this->height_mm).' mm'.($this->final_format ? " ({$this->final_format})" : '') : $this->final_format,
            $this->colors,
            trim(($this->paper ?? '').($this->grammage ? " {$this->grammage} g" : '')) ?: null,
            $this->finishing,
            $this->pages ? "{$this->pages} pages" : null,
            $this->quantity ? number_format($this->quantity, 0, ',', ' ').' ex.' : null,
        ])->filter()->implode(' · ');
    }
}
