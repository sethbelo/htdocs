<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    use BelongsToCompany;

    protected $attributes = ['status' => 'reserved'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'returned_at' => 'date', 'deposit' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RentalLine::class);
    }

    public function isLate(): bool
    {
        return $this->status === 'ongoing' && $this->end_date->lt(today());
    }
}
