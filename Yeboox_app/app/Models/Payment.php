<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'order_id', 'payment_method_id',
        'amount', 'tendered_amount', 'currency_id', 'exchange_rate', 'reference', 'change_amount',
        'status', 'cash_session_id', 'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'tendered_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'change_amount' => 'decimal:4',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
