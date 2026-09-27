<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'client_reference',
        'sync_uuid',
        'sync_status',
        'synced_at',
        'source',
        'customer_id',
        'invoice_id',
        'user_id',
        'register_session_id',
        'department', // 'bar', 'salon'
        'fulfillment_type',
        'table_name',
        'delivery_address',
        'delivery_phone',
        'payment_method', // 'cash', 'bank', 'mobile_money', 'yepay'
        'payment_reference',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'paid_amount',
        'points_earned',
        'points_redeemed',
    ];

    protected $casts = ['synced_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            $sale->sync_uuid ??= (string) Str::uuid();

            // Une vente écrite directement dans PostgreSQL est déjà centrale.
            // Seules les ventes du runtime Desktop attendent une réplication.
            if (config('offline-sync.runtime') !== 'desktop') {
                $sale->sync_status ??= 'synced';
                $sale->synced_at ??= now();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function accountingRecords(): HasMany
    {
        return $this->hasMany(AccountingRecord::class);
    }
}
