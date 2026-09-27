<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EquityMovement extends Model
{
    use BelongsToTenant;

    protected $fillable = ['number', 'movement_date', 'partner', 'type', 'amount', 'equity_account', 'treasury_account', 'description', 'user_id'];

    protected $casts = ['movement_date' => 'date', 'amount' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
