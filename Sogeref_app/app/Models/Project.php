<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use BelongsToCompany, HasEncryptedRouteKey;

    protected $attributes = ['status' => 'active', 'progress' => 0];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'budget' => 'decimal:2'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function site(): HasOne
    {
        return $this->hasOne(Site::class);
    }

    /** Avancement calculé à partir des tâches terminées. */
    public function computedProgress(): int
    {
        $total = $this->tasks()->count();

        return $total ? (int) round($this->tasks()->where('status', 'done')->count() / $total * 100) : (int) $this->progress;
    }
}
