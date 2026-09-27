<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingRecord extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'reference',
        'journal_code',
        'entry_type',
        'record_date',
        'syscohada_code',
        'debit',
        'credit',
        'description',
        'user_id',
        'sale_id',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'record_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
