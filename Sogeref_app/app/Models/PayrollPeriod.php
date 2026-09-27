<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use BelongsToCompany;

    protected $attributes = ['status' => 'draft'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'exchange_rate' => 'decimal:4', 'total_gross' => 'decimal:2', 'total_net' => 'decimal:2', 'total_employer' => 'decimal:2', 'validated_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['validated', 'paid'], true);
    }
}
