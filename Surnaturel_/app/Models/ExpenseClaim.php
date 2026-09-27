<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\ExpenseClaimStatus;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ExpenseClaim extends Model implements Approvable
{
    use BelongsToSite, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ExpenseClaimStatus::class,
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function receipts(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function approvalRequest(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    public function approvalWorkflowCode(): string
    {
        return 'expense_claim';
    }

    public function approvalAmount(): float
    {
        return (float) $this->amount;
    }

    public function approvalTitle(): string
    {
        return "Note de frais {$this->number}";
    }

    public function onApproved(): void
    {
        $this->update(['status' => ExpenseClaimStatus::FinanceApproved]);
    }

    public function onRejected(string $reason): void
    {
        $this->update(['status' => ExpenseClaimStatus::Rejected]);
    }
}
