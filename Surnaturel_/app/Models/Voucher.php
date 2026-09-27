<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Voucher extends Model implements Approvable
{
    use BelongsToSite, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'status' => VoucherStatus::class,
            'amount' => 'decimal:2',
            'lines' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function voucherable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalRequest(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    public function approvalWorkflowCode(): string
    {
        return $this->type === VoucherType::DisbursementVoucher ? 'disbursement' : 'voucher';
    }

    public function approvalAmount(): float
    {
        return (float) $this->amount;
    }

    public function approvalTitle(): string
    {
        return "{$this->type->label()} {$this->number}";
    }

    public function onApproved(): void
    {
        $this->update(['status' => VoucherStatus::Approved, 'approved_at' => now()]);
    }

    public function onRejected(string $reason): void
    {
        $this->update(['status' => VoucherStatus::Rejected]);
    }
}
