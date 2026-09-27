<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number', 'invoice_id', 'customer_id', 'credit_date', 'status',
        'subtotal', 'tax_amount', 'total_amount', 'reason', 'posted_at', 'user_id',
    ];

    protected $casts = [
        'credit_date' => 'date', 'posted_at' => 'datetime',
        'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
