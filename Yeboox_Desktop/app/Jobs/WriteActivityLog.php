<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Enregistre l'audit hors du temps de réponse des écrans Livewire. */
class WriteActivityLog implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $action,
        public readonly string $description,
        public readonly ?int $userId,
        public readonly ?string $ipAddress,
        public readonly ?int $companyId = null,
        public readonly ?int $establishmentId = null,
        public readonly ?int $pointOfSaleId = null,
    ) {
        $this->onQueue('audit');
        $this->afterCommit();
    }

    public function handle(): void
    {
        ActivityLog::query()->create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'description' => $this->description,
            'ip_address' => $this->ipAddress,
            'company_id' => $this->companyId,
            'establishment_id' => $this->establishmentId,
            'point_of_sale_id' => $this->pointOfSaleId,
        ]);
    }
}
