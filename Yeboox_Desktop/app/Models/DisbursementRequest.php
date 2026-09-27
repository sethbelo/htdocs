<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DisbursementRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = ['number', 'requested_date', 'beneficiary', 'purpose', 'amount', 'expense_account', 'treasury_account', 'status', 'notes', 'requested_by', 'approved_by', 'approved_at', 'paid_at'];

    protected $casts = ['requested_date' => 'date', 'approved_at' => 'datetime', 'paid_at' => 'datetime', 'amount' => 'decimal:2'];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
