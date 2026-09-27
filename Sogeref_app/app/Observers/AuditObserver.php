<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->write('created', $model, [], array_diff_key($model->getAttributes(), array_flip(['password', 'remember_token'])));
    }

    public function updated(Model $model): void
    {
        $changes = array_diff_key($model->getChanges(), array_flip(['updated_at', 'password', 'remember_token']));
        if ($changes) {
            $this->write('updated', $model, array_intersect_key($model->getOriginal(), $changes), $changes);
        }
    }

    public function deleted(Model $model): void
    {
        $this->write('deleted', $model, $model->getOriginal(), []);
    }

    private function write(string $event, Model $model, array $old, array $new): void
    {
        $companyId = $model->getAttribute('company_id') ?? auth()->user()?->current_company_id;
        if (! $companyId) {
            return;
        }
        AuditLog::create(['company_id' => $companyId, 'user_id' => auth()->id(), 'event' => $event, 'auditable_type' => $model->getMorphClass(), 'auditable_id' => $model->getKey(), 'old_values' => $old, 'new_values' => $new, 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
    }
}
