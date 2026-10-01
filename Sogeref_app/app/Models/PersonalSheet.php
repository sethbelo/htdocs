<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Policies\PersonalItemPolicy;
use App\Support\SheetFormula;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tableur personnel : cellules brutes (texte, nombres, formules) au format JSON
 * et valeurs calculées côté serveur à chaque enregistrement.
 */
#[UsePolicy(PersonalItemPolicy::class)]
class PersonalSheet extends Model
{
    use BelongsToCompany;

    public const MAX_ROWS = 500;

    public const MAX_COLS = 52;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cells' => 'array', 'values' => 'array', 'rows' => 'integer', 'cols' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $sheet): void {
            $sheet->values = SheetFormula::evaluateAll($sheet->cells ?? []);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMine(Builder $query): Builder
    {
        return $query->where('company_id', company_id())->where('user_id', auth()->id());
    }

    /** Valeur affichable d'une cellule (calculée si formule). */
    public function display(string $ref): string
    {
        $value = $this->values[$ref] ?? ($this->cells[$ref] ?? '');

        return is_float($value) || is_int($value) ? SheetFormula::format($value) : (string) $value;
    }

    /** Grille de valeurs calculées (lignes × colonnes), bornée à la zone utilisée. */
    public function matrix(bool $trim = true): array
    {
        $rows = $this->rows;
        $cols = $this->cols;
        if ($trim) {
            [$rows, $cols] = [0, 0];
            foreach (array_keys(array_filter($this->cells ?? [], fn ($v) => $v !== '' && $v !== null)) as $ref) {
                [$c, $r] = SheetFormula::parseRef($ref);
                $rows = max($rows, $r + 1);
                $cols = max($cols, $c + 1);
            }
        }
        $grid = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $ref = SheetFormula::ref($c, $r);
                $grid[$r][$c] = $this->values[$ref] ?? ($this->cells[$ref] ?? '');
            }
        }

        return $grid;
    }
}
