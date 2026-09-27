<?php

namespace App\Models;

use App\Enums\DesignJobStatus;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DesignJob extends Model
{
    use TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => DesignJobStatus::class,
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DesignVersion::class)->orderByDesc('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DesignVersion::class)->ofMany('version', 'max');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(Proof::class)->latest();
    }
}
