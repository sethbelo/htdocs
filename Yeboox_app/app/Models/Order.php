<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Traits\Auditable;
use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use Auditable, HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'order_number', 'offline_reference', 'table_id',
        'customer_id', 'user_id', 'cash_session_id',
        'type', 'status', 'subtotal', 'discount_amount', 'discount_type',
        'tax_amount', 'service_charge', 'total', 'currency_id',
        'notes', 'priority', 'is_paid',
    ];

    protected $casts = [
        'type' => OrderType::class,
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'service_charge' => 'decimal:4',
        'total' => 'decimal:4',
        'is_paid' => 'boolean',
    ];

    // ── Relations ────────────────────────────────
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    // ── Scopes ────────────────────────────────
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', OrderStatus::Completed);
    }

    // ── Order Number Generator ────────────────────────────────
    public static function generateNumber(string $establishmentId): string
    {
        $today = now()->format('ymd');
        $count = static::where('establishment_id', $establishmentId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return "CMD-{$today}-".str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Recalculate Totals ────────────────────────────────
    public function recalculate(): void
    {
        $subtotal = $this->items->sum('total');
        $taxAmount = $this->items->sum('tax_amount');

        $discountAmount = $this->discount_amount;
        if ($this->discount_type === 'percentage' && $this->discount_amount > 0) {
            $discountAmount = $subtotal * ($this->discount_amount / 100);
        }

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal - $discountAmount + $taxAmount + $this->service_charge,
        ]);
    }

    // ── Payment Helpers ────────────────────────────────
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total - $this->total_paid);
    }

    public function markPaid(): void
    {
        $this->update(['is_paid' => true, 'status' => OrderStatus::Completed]);
    }
}
