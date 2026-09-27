<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:2',
            'installment' => 'decimal:2',
            'balance' => 'decimal:2',
            'granted_on' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
