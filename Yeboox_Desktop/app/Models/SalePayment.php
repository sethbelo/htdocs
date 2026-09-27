<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'sale_id', 'payment_method_id', 'method', 'provider', 'reference',
        'amount', 'currency', 'points_redeemed', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'points_redeemed' => 'integer',
        'metadata' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
