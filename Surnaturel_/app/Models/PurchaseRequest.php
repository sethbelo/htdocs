<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\PurchaseRequestStatus;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PurchaseRequest extends Model implements Approvable
{
    use BelongsToSite, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PurchaseRequestStatus::class,
            'needed_by' => 'date',
            'estimated_total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function approvalRequest(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    public function approvalWorkflowCode(): string
    {
        return 'purchase_request';
    }

    public function approvalAmount(): float
    {
        return (float) $this->estimated_total;
    }

    public function approvalTitle(): string
    {
        return "Demande d’achat {$this->number}";
    }

    public function onApproved(): void
    {
        $this->update(['status' => PurchaseRequestStatus::Approved]);
    }

    public function onRejected(string $reason): void
    {
        $this->update(['status' => PurchaseRequestStatus::Rejected]);
    }
}
