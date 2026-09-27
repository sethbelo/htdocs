<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToTenant, HasFactory;

    public function tenantScopeLevel(): string
    {
        return 'establishment';
    }

    protected $fillable = [
        'customer_id',
        'employee_id',
        'service_id',
        'date_time',
        'status', // pending, confirmed, completed, cancelled
        'notes',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'service_id');
    }
}
