<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['min_amount' => 'decimal:2'];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function canBeActedOnBy(User $user): bool
    {
        if ($this->approver_user_id !== null) {
            return $this->approver_user_id === $user->id;
        }

        return $this->approver_role !== null && $user->hasRole($this->approver_role);
    }
}
