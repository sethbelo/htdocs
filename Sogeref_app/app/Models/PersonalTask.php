<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Policies\PersonalItemPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tâche personnelle du jour (to-do), avec créneau horaire et priorité.
 */
#[UsePolicy(PersonalItemPolicy::class)]
class PersonalTask extends Model
{
    use BelongsToCompany;

    public const PRIORITIES = ['basse', 'normale', 'haute', 'urgente'];

    protected $attributes = ['priority' => 'normale', 'is_done' => false, 'position' => 0];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['task_date' => 'date', 'carried_from' => 'date', 'is_done' => 'boolean', 'done_at' => 'datetime', 'position' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMine(Builder $query): Builder
    {
        return $query->where('company_id', company_id())->where('user_id', auth()->id());
    }

    /** Ordre d'affichage : à faire d'abord, puis par position, créneau et priorité. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('is_done')->orderBy('position')->orderByRaw('start_time is null, start_time')->orderBy('id');
    }

    public function timeSlot(): ?string
    {
        if (! $this->start_time) {
            return null;
        }

        return $this->start_time.($this->end_time ? ' – '.$this->end_time : '');
    }
}
