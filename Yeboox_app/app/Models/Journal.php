<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'code', 'name', 'type', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ────────────────────────────────
    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    // ── Scopes ────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Generate Entry Number ────────────────────────────────
    public static function generateEntryNumber(string $journalCode, string $establishmentId): string
    {
        $year = now()->format('Y');
        $count = JournalEntry::where('establishment_id', $establishmentId)
            ->whereHas('journal', fn ($q) => $q->where('code', $journalCode))
            ->whereYear('entry_date', $year)
            ->count() + 1;

        return "{$journalCode}-{$year}-".str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
