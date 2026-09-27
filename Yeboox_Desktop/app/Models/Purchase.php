<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToTenant;

    protected $fillable = ['number', 'purchase_date', 'supplier', 'description', 'expense_account', 'subtotal', 'tax_amount', 'total_amount', 'status', 'supplier_reference', 'user_id', 'approved_by', 'approved_at'];

    protected $casts = ['purchase_date' => 'date', 'approved_at' => 'datetime', 'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
