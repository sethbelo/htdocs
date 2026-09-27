<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'number',
        'customer_id',
        'invoice_date',
        'due_date',
        'status',
        'posted_at',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function getBalanceDueAttribute(): float
    {
        // Réutiliser l'agrégat ou la relation préchargée évite une requête par facture.
        $creditedAmount = array_key_exists('credit_notes_sum_total_amount', $this->attributes)
            ? (float) $this->attributes['credit_notes_sum_total_amount']
            : ($this->relationLoaded('creditNotes')
                ? (float) $this->creditNotes->sum('total_amount')
                : (float) $this->creditNotes()->sum('total_amount'));

        return max(0, (float) $this->total_amount - (float) $this->paid_amount - $creditedAmount);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::whereBetween('created_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
            ->orderByDesc('id')
            ->first();
        $sequence = $lastInvoice ? ((int) substr($lastInvoice->number, -4)) + 1 : 1;

        return 'FAC-'.$year.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
