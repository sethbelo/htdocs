<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasEncryptedRouteKey, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => CashSessionStatus::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'expected_balance' => 'decimal:2',
            'counted_balance' => 'decimal:2',
            'difference' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'denominations' => 'array',
            'accounting_synced_at' => 'datetime',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'deposit_treasury_account_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CashSessionStatus::Open;
    }

    /**
     * Solde espèces théorique = ouverture + entrées espèces - sorties espèces.
     */
    public function theoreticalCashBalance(): float
    {
        $in = (float) $this->movements()->where('payment_method', 'cash')->where('direction', 'in')->where('type', '!=', 'opening')->sum('amount');
        $out = (float) $this->movements()->where('payment_method', 'cash')->where('direction', 'out')->sum('amount');

        return round((float) $this->opening_balance + $in - $out, 2);
    }

    /**
     * @return array<string, float>
     */
    public function totalsByMethod(): array
    {
        return $this->movements()
            ->where('type', '!=', 'opening')
            ->selectRaw("payment_method, SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as total")
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($value): float => (float) $value)
            ->all();
    }
}
