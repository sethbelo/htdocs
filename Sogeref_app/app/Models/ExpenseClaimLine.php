<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseClaimLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['spent_at' => 'date', 'amount' => 'decimal:2'];
    }
}
