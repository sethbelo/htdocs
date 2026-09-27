<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'account_id', 'payment_method_id',
        'reference', 'category', 'description', 'amount', 'expense_date',
        'status', 'created_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ── Categories ────────────────────────────────
    public static function categories(): array
    {
        return [
            'loyer' => 'Loyer',
            'salaires' => 'Salaires & charges sociales',
            'fournitures' => 'Fournitures de bureau',
            'transport' => 'Transport & déplacement',
            'entretien' => 'Entretien & réparations',
            'energie' => 'Électricité, eau & gaz',
            'telecom' => 'Téléphone & internet',
            'marketing' => 'Publicité & marketing',
            'assurance' => 'Assurances',
            'impots' => 'Impôts & taxes',
            'divers' => 'Charges diverses',
        ];
    }
}
