<?php

namespace App\Livewire\Communication;

use App\Livewire\Communication\Concerns\CommunicationUi;
use App\Models\CommCampaign;
use App\Models\CommConversation;
use App\Models\CommMessage;
use App\Services\Communication\IntegrationSettings;
use App\Services\Communication\WhatsAppNumberPool;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Vue d’ensemble Communication : indicateurs par canal (30 jours),
 * état des intégrations, charge des 3 numéros WhatsApp, activité récente
 * et dernières campagnes.
 */
#[Title('Communication')]
class Overview extends Component
{
    use CommunicationUi;

    public function render()
    {
        $since = now()->subDays(30);
        $company = company_id();

        $messageStats = CommMessage::query()->where('company_id', $company)->where('created_at', '>=', $since)
            ->selectRaw('channel, direction, status, count(*) as total')->groupBy('channel', 'direction', 'status')->get();
        $conversationStats = CommConversation::query()->where('company_id', $company)->where('status', 'open')
            ->selectRaw('channel, count(*) as open_count, coalesce(sum(unread_count), 0) as unread')->groupBy('channel')->get()->keyBy('channel');

        $settings = app(IntegrationSettings::class);
        $simulation = $settings->isSimulation();

        $kpis = collect(self::channels())->map(function (array $meta, string $channel) use ($messageStats, $conversationStats, $settings, $simulation) {
            $rows = $messageStats->where('channel', $channel);
            $out = $rows->where('direction', 'out');
            $sent = (int) $out->sum('total');
            $delivered = (int) $out->whereIn('status', ['delivered', 'read'])->sum('total');
            $failed = (int) $out->where('status', 'failed')->sum('total');
            $settled = (int) $out->whereIn('status', ['sent', 'delivered', 'read', 'failed'])->sum('total');

            return $meta + [
                'sent' => $sent,
                'received' => (int) $rows->where('direction', 'in')->sum('total'),
                'failed' => $failed,
                'rate' => $settled ? (int) round(($sent - $failed) / max(1, $sent) * 100) : null,
                'delivered_rate' => $settled ? (int) round($delivered / $settled * 100) : null,
                'open' => (int) ($conversationStats[$channel]->open_count ?? 0),
                'unread' => (int) ($conversationStats[$channel]->unread ?? 0),
                'configured' => $configured = $settings->configured($channel),
                'integration' => $simulation ? 'simulation' : ($configured ? 'configured' : 'missing'),
            ];
        });

        return view('livewire.communication.overview', [
            'kpis' => $kpis,
            'simulation' => $simulation,
            'numbers' => collect(app(WhatsAppNumberPool::class)->numbers()),
            'recent' => CommMessage::query()->where('company_id', $company)->with(['conversation.contact', 'sender:id,name'])->latest('id')->limit(10)->get(),
            'campaigns' => CommCampaign::query()->where('company_id', $company)->latest('id')->limit(5)->get(),
            'isMaster' => auth()->user()->isMaster(),
            'canSend' => $this->canSend(),
            'canCampaign' => $this->canCampaign(),
        ]);
    }
}
