<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    use HasUuid;

    protected $fillable = [
        'journal_entry_id', 'account_id',
        'debit', 'credit', 'label',
        'third_party_type', 'third_party_id',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    // ── Relations ────────────────────────────────
    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
