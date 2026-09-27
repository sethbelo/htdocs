<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'code', 'name', 'class', 'type',
        'parent_id', 'is_group', 'is_active', 'description',
    ];

    protected $casts = [
        'class' => 'integer',
        'is_group' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ── Relations ────────────────────────────────
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    // ── Scopes ────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDetail($query)
    {
        return $query->where('is_group', false);
    }

    public function scopeByClass($query, int $class)
    {
        return $query->where('class', $class);
    }

    // ── Balance ────────────────────────────────
    public function getBalanceAttribute(): float
    {
        $debits = $this->lines()->sum('debit');
        $credits = $this->lines()->sum('credit');

        // Actif/Charges: solde débiteur (débit - crédit)
        // Passif/Produits/Capitaux: solde créditeur (crédit - débit)
        return in_array($this->type, ['asset', 'expense'])
            ? (float) ($debits - $credits)
            : (float) ($credits - $debits);
    }

    // ── OHADA Class Labels ────────────────────────────────
    public static function classLabel(int $class): string
    {
        return match ($class) {
            1 => 'Comptes de ressources durables',
            2 => 'Comptes d\'actif immobilisé',
            3 => 'Comptes de stocks',
            4 => 'Comptes de tiers',
            5 => 'Comptes de trésorerie',
            6 => 'Comptes de charges',
            7 => 'Comptes de produits',
            8 => 'Comptes des autres charges et produits',
            9 => 'Comptes de la comptabilité analytique',
            default => 'Inconnu',
        };
    }
}
