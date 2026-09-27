<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'supplier_id', 'warehouse_id',
        'reference', 'status', 'order_date', 'expected_date', 'received_date',
        'subtotal', 'tax_amount', 'discount_amount', 'total', 'currency',
        'notes', 'created_by', 'approved_by', 'received_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeSearch($query, ?string $s)
    {
        if (! $s) {
            return $query;
        }

        return $query->where('reference', 'like', "%{$s}%");
    }

    public function recalculate(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->tax_amount = $this->items->sum(fn ($i) => $i->total * $i->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->saveQuietly();
    }

    public static function generateReference(string $establishmentId): string
    {
        $count = static::where('establishment_id', $establishmentId)->count() + 1;

        return 'PO-'.now()->format('Ym').'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
