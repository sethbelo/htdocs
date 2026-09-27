<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningSlot extends Model
{
    use BelongsToCompany;

    public const SHIFTS = ['journee' => 'Journée', 'matin' => 'Matin', 'apres_midi' => 'Après-midi'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['slot_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function assignmentLabel(): string
    {
        return $this->site?->name ?? $this->manufacturingOrder?->number ?? $this->project?->name ?? ($this->role ?: 'Affectation');
    }
}
