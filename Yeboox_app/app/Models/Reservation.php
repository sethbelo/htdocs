<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'table_id', 'customer_id',
        'guest_name', 'guest_phone', 'guest_email',
        'reservation_date', 'reservation_time', 'duration_minutes', 'party_size',
        'status', 'special_requests', 'notes',
        'confirmed_by', 'created_by',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'duration_minutes' => 'integer',
        'party_size' => 'integer',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('reservation_date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
            ->orderBy('reservation_date')->orderBy('reservation_time');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function confirm(?string $userId = null): void
    {
        $this->update(['status' => 'confirmed', 'confirmed_by' => $userId ?? auth()->id()]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markSeated(): void
    {
        $this->update(['status' => 'seated']);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markNoShow(): void
    {
        $this->update(['status' => 'no_show']);
    }

    public function getDateTimeAttribute(): string
    {
        return $this->reservation_date->format('d/m/Y').' à '.$this->reservation_time;
    }
}
