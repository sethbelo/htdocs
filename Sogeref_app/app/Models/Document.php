<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => DocumentType::class, 'status' => DocumentStatus::class, 'issue_date' => 'date', 'due_date' => 'date', 'valid_until' => 'date', 'exchange_rate' => 'decimal:8', 'subtotal' => 'decimal:2', 'tax_total' => 'decimal:2', 'total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'sent_at' => 'datetime', 'accepted_at' => 'datetime', 'meta' => 'array'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('position');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'converted_from_id');
    }

    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'source');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Dossier de commande lié (via le devis ou la facture). */
    public function relatedOrder(): ?SalesOrder
    {
        return SalesOrder::query()
            ->where($this->type === DocumentType::Invoice ? 'invoice_id' : 'quote_id', $this->id)
            ->first();
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }
}
