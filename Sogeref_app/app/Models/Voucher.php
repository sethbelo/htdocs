<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bon interne soumis à approbation : achat, décaissement ou sortie matériel.
 */
class Voucher extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'currency_code' => 'USD'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['voucher_date' => 'date', 'amount' => 'decimal:2', 'approved_at' => 'datetime', 'executed_at' => 'datetime'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VoucherLine::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function typeLabel(): string
    {
        return config("erp.voucher_types.{$this->type}.label", $this->type);
    }

    public function beneficiaryName(): string
    {
        return $this->beneficiary ?: ($this->employee?->full_name ?? $this->contact?->display_name ?? $this->site?->name ?? '—');
    }
}
