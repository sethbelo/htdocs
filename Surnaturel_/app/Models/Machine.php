<?php

namespace App\Models;

use App\Enums\MachineStatus;
use App\Enums\MachineType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use BelongsToSite, SoftDeletes, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => MachineType::class,
            'status' => MachineStatus::class,
            'last_maintenance_at' => 'date',
            'next_maintenance_at' => 'date',
            'acquired_at' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    public function printRoom(): BelongsTo
    {
        return $this->belongsTo(PrintRoom::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(MachineMaintenance::class)->latest();
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    public function isMaintenanceDue(): bool
    {
        return $this->next_maintenance_at !== null && $this->next_maintenance_at->lte(today()->addDays(7));
    }
}
