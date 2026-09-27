<?php

namespace App\Models;

use App\Enums\PrintJobStatus;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintJob extends Model
{
    use TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PrintJobStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'quality_passed' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(Proof::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function printRoom(): BelongsTo
    {
        return $this->belongsTo(PrintRoom::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(PrintJobConsumption::class);
    }

    public function durationMinutes(): ?int
    {
        return $this->started_at && $this->finished_at ? (int) $this->started_at->diffInMinutes($this->finished_at) : null;
    }
}
