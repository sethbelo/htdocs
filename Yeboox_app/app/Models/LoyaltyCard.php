<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class LoyaltyCard extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'customer_id', 'card_number', 'type', 'nfc_uid',
        'token', 'reader_type', 'status', 'issued_at', 'last_read_at', 'created_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'last_read_at' => 'datetime',
    ];

    protected $hidden = ['token'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function isNfc(): bool
    {
        return $this->type === 'nfc';
    }
}
