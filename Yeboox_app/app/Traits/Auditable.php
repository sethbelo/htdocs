<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Auditable
{
    /**
     * Boot the trait — log model changes.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAudit('created');
        });

        static::updated(function ($model) {
            if ($model->wasChanged()) {
                $model->logAudit('updated', $model->getChanges(), $model->getOriginal());
            }
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted');
        });
    }

    /**
     * Log an audit entry.
     */
    protected function logAudit(string $action, array $newValues = [], array $oldValues = []): void
    {
        if (! auth()->check()) {
            return;
        }

        // Filter out timestamps and non-relevant fields
        $exclude = ['updated_at', 'created_at', 'remember_token'];
        $newValues = array_diff_key($newValues, array_flip($exclude));
        $oldValues = array_intersect_key($oldValues, $newValues);

        \DB::table('audit_logs')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'establishment_id' => auth()->user()->establishment_id ?? null,
            'action' => $action,
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'old_values' => ! empty($oldValues) ? json_encode($oldValues) : null,
            'new_values' => ! empty($newValues) ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
