<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number', 'expense_date', 'supplier', 'description', 'expense_account',
        'payment_method', 'credit_account', 'subtotal', 'tax_amount', 'total_amount',
        'receipt_reference', 'notes', 'status', 'posted_at', 'user_id',
    ];

    protected $casts = [
        'expense_date' => 'date', 'posted_at' => 'datetime',
        'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
