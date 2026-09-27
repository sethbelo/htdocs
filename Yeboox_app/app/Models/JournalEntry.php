<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'journal_id', 'fiscal_year_id',
        'entry_number', 'entry_date', 'reference', 'description',
        'status', 'created_by', 'validated_by', 'validated_at',
        'source_type', 'source_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'validated_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ── Scopes ────────────────────────────────
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ── Validation ────────────────────────────────
    public function isBalanced(): bool
    {
        $totalDebit = $this->lines()->sum('debit');
        $totalCredit = $this->lines()->sum('credit');

        return abs($totalDebit - $totalCredit) < 0.01;
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    // ── Post ────────────────────────────────
    public function post(string $userId): bool
    {
        if (! $this->isBalanced()) {
            return false;
        }

        $this->update([
            'status' => 'posted',
            'validated_by' => $userId,
            'validated_at' => now(),
        ]);

        return true;
    }
}
