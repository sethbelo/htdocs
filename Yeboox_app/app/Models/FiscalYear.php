<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'name', 'start_date', 'end_date',
        'status', 'closed_by', 'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────
    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // ── Scopes ────────────────────────────────
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())->where('end_date', '>=', now());
    }

    // ── Helpers ────────────────────────────────
    public function isCurrent(): bool
    {
        return $this->start_date <= now() && $this->end_date >= now();
    }
}
