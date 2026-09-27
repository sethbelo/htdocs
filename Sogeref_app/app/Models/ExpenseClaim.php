<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseClaim extends Model
{
    use BelongsToCompany;

    protected $attributes = ['status' => 'draft', 'currency_code' => 'USD', 'total' => 0];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'approved_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseClaimLine::class)->orderBy('spent_at');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
