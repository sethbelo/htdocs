<?php

namespace App\Models;

use App\Enums\CareType;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bon de prise en charge médicale remis à l'agent ou à son ayant droit.
 */
class CareVoucher extends Model
{
    use HasEncryptedRouteKey, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'care_type' => CareType::class,
            'estimated_amount' => 'decimal:2',
            'issued_on' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function affiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
