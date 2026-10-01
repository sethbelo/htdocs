<?php

namespace App\Jobs;

use App\Models\CommCampaign;
use App\Services\Communication\CampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envoi d'une campagne en tâche de fond. Avec QUEUE_CONNECTION=sync,
 * l'envoi se fait immédiatement dans la requête.
 */
class SendCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(public int $campaignId) {}

    public function handle(CampaignService $campaigns): void
    {
        $campaign = CommCampaign::withoutGlobalScopes()->find($this->campaignId);
        if ($campaign) {
            $campaigns->process($campaign, app()->runningUnitTests() ? 0 : 50);
        }
    }
}
