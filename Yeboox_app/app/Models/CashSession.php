<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    use HasUuid;

    protected $fillable = [
        'cash_register_id', 'user_id', 'opening_amount', 'closing_amount',
        'expected_amount', 'difference', 'opening_note', 'difference_note', 'status',
        'opened_at', 'closed_at', 'validated_by', 'cash_count',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:4',
        'closing_amount' => 'decimal:4',
        'expected_amount' => 'decimal:4',
        'difference' => 'decimal:4',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'cash_count' => 'array',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function close(float $closingAmount, ?string $note = null): void
    {
        $expectedAmount = (float) $this->opening_amount + (float) $this->payments()
            ->where('payments.status', 'completed')
            ->whereHas('paymentMethod', fn ($query) => $query->where('code', 'cash'))
            ->sum('amount');
        $this->update([
            'closing_amount' => $closingAmount,
            'expected_amount' => $expectedAmount,
            'difference' => $closingAmount - $expectedAmount,
            'difference_note' => $note,
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
