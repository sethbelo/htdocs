<?php

namespace App\Livewire\Marketing;

use App\Enums\InteractionChannel;
use App\Enums\InteractionOutcome;
use App\Exports\TableExport;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Client;
use App\Models\ClientInteraction;
use App\Models\User;
use App\Services\Marketing\FollowUpService;
use App\Services\Printing\PdfRenderer;
use App\Services\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Outil marketing de suivi des clients : relances du jour, appels, messages WhatsApp, notes et rapports d'activité.
 */
#[Title('Suivi clients')]
class FollowUps extends Component
{
    use InteractsWithToasts, WithPagination;

    #[Url(as: 'vue')]
    public string $tab = 'today';

    #[Url(as: 'q')]
    public string $search = '';

    public bool $mineOnly = false;

    public ?int $clientId = null;

    /** @var array<string, mixed> */
    public array $log = [];

    public string $template = '';

    public string $message = '';

    public string $from = '';

    public string $to = '';

    public ?int $agentId = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->mineOnly = ! auth()->user()->can('followups.approve') && ! auth()->user()->hasRole(['responsable-marketing', 'responsable-commercial', 'directeur-general']);
        $this->resetLog();
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['tab', 'search', 'mineOnly'], true)) {
            $this->resetPage();
        }
    }

    private function resetLog(string $channel = 'call'): void
    {
        $this->log = ['channel' => $channel, 'outcome' => '', 'subject' => '', 'notes' => '', 'duration_minutes' => '', 'next_follow_up_at' => ''];
    }

    private function clients(): Builder
    {
        return Client::query()->visibleTo(auth()->user())->where('is_active', true)
            ->when($this->mineOnly, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('follow_up_owner_id', auth()->id())->orWhere('sales_rep_id', auth()->id())));
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'today' => $this->clients()->whereNotNull('next_follow_up_at')->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'late' => $this->clients()->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now()->startOfDay())->count(),
            'upcoming' => $this->clients()->where('next_follow_up_at', '>', now()->endOfDay())->count(),
            'dormant' => $this->clients()->where(fn (Builder $q) => $q->whereNull('last_contacted_at')->orWhere('last_contacted_at', '<', now()->subDays(60)))->whereNull('next_follow_up_at')->count(),
        ];
    }

    public function portfolio()
    {
        $query = $this->clients()->with('followUpOwner', 'salesRep')->search($this->search ?: null);

        return match ($this->tab) {
            'today' => $query->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()])->orderBy('next_follow_up_at'),
            'late' => $query->where('next_follow_up_at', '<', now()->startOfDay())->orderBy('next_follow_up_at'),
            'upcoming' => $query->where('next_follow_up_at', '>', now()->endOfDay())->orderBy('next_follow_up_at'),
            'dormant' => $query->where(fn (Builder $q) => $q->whereNull('last_contacted_at')->orWhere('last_contacted_at', '<', now()->subDays(60)))->whereNull('next_follow_up_at')->orderBy('last_contacted_at'),
            default => $query->orderByDesc('last_contacted_at'),
        };
    }

    #[Computed]
    public function client(): ?Client
    {
        return $this->clientId ? $this->clients()->with(['interactions' => fn ($q) => $q->with('user')->limit(30), 'orders' => fn ($q) => $q->latest()->limit(5)])->find($this->clientId) : null;
    }

    public function select(int $id): void
    {
        $this->clientId = $id;
        $this->resetLog();
        $this->reset('template', 'message');
        unset($this->client);
    }

    public function startCall(int $id): void
    {
        $this->select($id);
        $this->resetLog('call');
        $this->log['outcome'] = 'reached';
    }

    public function startWhatsApp(int $id): void
    {
        $this->select($id);
        $this->resetLog('whatsapp');
        $this->template = '0';
        $this->updatedTemplate('0');
        $this->modal('whatsapp')->show();
    }

    public function updatedTemplate(string $value): void
    {
        $template = app(FollowUpService::class)->templates()[(int) $value] ?? null;
        if ($template && $this->client) {
            $this->message = app(FollowUpService::class)->renderTemplate($template['body'], $this->client, auth()->user());
            $this->log['subject'] = $template['name'];
        }
    }

    /**
     * Ouvre WhatsApp (application ou WhatsApp Web) avec le message pré-rempli, puis journalise l'envoi.
     */
    public function sendWhatsApp(FollowUpService $followUps): void
    {
        $this->authorize('followups.create');
        $this->validate(['message' => ['required', 'string', 'max:1500']], [], ['message' => 'message']);

        $url = $followUps->whatsappUrl($this->client, $this->message);
        if (! $url) {
            $this->toast('Ce client n’a pas de numéro WhatsApp ou de téléphone.', 'danger');

            return;
        }

        $this->attempt(fn () => $followUps->record($this->client, auth()->user(), ['channel' => 'whatsapp', 'subject' => $this->log['subject'] ?: 'Message WhatsApp', 'notes' => $this->message, 'outcome' => $this->log['outcome'] ?: null, 'next_follow_up_at' => $this->log['next_follow_up_at'] ?: null]));
        $this->js('window.open('.json_encode($url).', "_blank")');
        $this->modal('whatsapp')->close();
        $this->resetLog();
        unset($this->client, $this->counts);
    }

    public function record(FollowUpService $followUps): void
    {
        $this->authorize('followups.create');
        $this->validate([
            'log.channel' => ['required', 'in:'.implode(',', array_column(InteractionChannel::cases(), 'value'))],
            'log.outcome' => ['nullable', 'in:'.implode(',', array_column(InteractionOutcome::cases(), 'value'))],
            'log.subject' => ['nullable', 'string', 'max:255'],
            'log.notes' => ['nullable', 'string', 'max:5000'],
            'log.duration_minutes' => ['nullable', 'numeric', 'min:0', 'max:600'],
            'log.next_follow_up_at' => ['nullable', 'date'],
        ], [], ['log.notes' => 'compte rendu', 'log.next_follow_up_at' => 'date de relance']);

        if ($this->attempt(fn () => $followUps->record($this->client, auth()->user(), $this->log), 'Échange enregistré.')) {
            $this->resetLog();
            unset($this->client, $this->counts);
        }
    }

    public function snooze(int $id, int $days): void
    {
        $this->authorize('followups.update');
        $client = $this->clients()->findOrFail($id);
        $client->forceFill(['next_follow_up_at' => now()->addDays($days)->setTime(9, 0)])->save();
        $this->toast("Relance reportée au {$client->next_follow_up_at->translatedFormat('l j F')}.");
        unset($this->counts);
    }

    /**
     * @return array{kpis: list<array{label: string, value: string|int}>, byAgent: Collection<int, array<int, mixed>>, byChannel: Collection<int, array<int, mixed>>, byOutcome: Collection<int, array<int, mixed>>, rows: Collection<int, ClientInteraction>}
     */
    #[Computed]
    public function report(): array
    {
        $interactions = ClientInteraction::query()->with('client', 'user')
            ->whereHas('client', fn (Builder $q) => $q->visibleTo(auth()->user()))
            ->whereBetween('occurred_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
            ->when($this->agentId, fn (Builder $q) => $q->where('user_id', $this->agentId))
            ->when($this->mineOnly, fn (Builder $q) => $q->where('user_id', auth()->id()))
            ->latest('occurred_at')->get();

        $converted = $interactions->where('outcome', InteractionOutcome::Converted)->count();
        $calls = $interactions->where('channel', InteractionChannel::Call);

        return [
            'kpis' => [
                ['label' => 'Échanges', 'value' => $interactions->count()],
                ['label' => 'Clients contactés', 'value' => $interactions->pluck('client_id')->unique()->count()],
                ['label' => 'Appels', 'value' => $calls->count()],
                ['label' => 'Messages WhatsApp', 'value' => $interactions->where('channel', InteractionChannel::WhatsApp)->count()],
                ['label' => 'Conversions', 'value' => $converted],
                ['label' => 'Taux de conversion', 'value' => $interactions->count() ? round($converted / $interactions->count() * 100, 1).' %' : '0 %'],
            ],
            'byAgent' => $interactions->groupBy('user_id')->map(fn (Collection $items) => [
                $items->first()->user->name, $items->count(), $items->where('channel', InteractionChannel::Call)->count(), $items->where('channel', InteractionChannel::WhatsApp)->count(),
                $items->pluck('client_id')->unique()->count(), $items->where('outcome', InteractionOutcome::Converted)->count(),
            ])->sortByDesc(1)->values(),
            'byChannel' => collect(InteractionChannel::cases())->map(fn (InteractionChannel $c) => [$c->label(), $interactions->where('channel', $c)->count()])->filter(fn ($r) => $r[1] > 0)->values(),
            'byOutcome' => collect(InteractionOutcome::cases())->map(fn (InteractionOutcome $o) => [$o->label(), $interactions->where('outcome', $o)->count()])->filter(fn ($r) => $r[1] > 0)->values(),
            'rows' => $interactions,
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('followups.export');

        $rows = $this->report['rows']->map(fn (ClientInteraction $i) => [
            $i->occurred_at->format('d/m/Y H:i'), $i->client->displayName(), $i->client->phone, $i->user->name, $i->channel->label(),
            $i->outcome?->label(), $i->subject, $i->notes, $i->next_follow_up_at?->format('d/m/Y H:i'),
        ]);

        return Excel::download(new TableExport(['Date', 'Client', 'Téléphone', 'Agent', 'Canal', 'Résultat', 'Objet', 'Compte rendu', 'Prochaine relance'], $rows, 'Suivi clients'), "suivi-clients-{$this->from}-{$this->to}.xlsx");
    }

    public function exportPdf(PdfRenderer $renderer): StreamedResponse
    {
        $this->authorize('followups.print');
        $report = $this->report;

        $pdf = $renderer->make('pdf.report', [
            'title' => 'Rapport de suivi clients',
            'from' => $this->from, 'to' => $this->to,
            'site' => $this->agentId ? 'Agent : '.User::query()->find($this->agentId)?->name : ($this->mineOnly ? 'Agent : '.auth()->user()->name : 'Toute l’équipe'),
            'kpis' => $report['kpis'],
            'signatures' => 'followup_report',
            'sections' => [
                ['title' => 'Activité par agent', 'headings' => ['Agent', 'Échanges', 'Appels', 'WhatsApp', 'Clients', 'Conversions'], 'rows' => $report['byAgent']->all()],
                ['title' => 'Répartition par canal', 'headings' => ['Canal', 'Échanges'], 'rows' => $report['byChannel']->all()],
                ['title' => 'Résultats des échanges', 'headings' => ['Résultat', 'Échanges'], 'rows' => $report['byOutcome']->all()],
                ['title' => 'Journal des échanges', 'headings' => ['Date', 'Client', 'Agent', 'Canal', 'Résultat', 'Compte rendu'], 'rows' => $report['rows']->take(300)->map(fn (ClientInteraction $i) => [
                    $i->occurred_at->format('d/m H:i'), $i->client->displayName(), $i->user->name, $i->channel->label(), $i->outcome?->label() ?? '-', Str::limit((string) ($i->notes ?? $i->subject), 90),
                ])->all()],
            ],
        ], 'a4');

        return response()->streamDownload(fn () => print ($pdf->output()), "rapport-suivi-clients-{$this->from}-{$this->to}.pdf");
    }

    public function render(FollowUpService $followUps)
    {
        return view('livewire.marketing.follow-ups', [
            'clients' => $this->tab === 'report' ? collect() : $this->portfolio()->paginate(15),
            'channels' => InteractionChannel::cases(),
            'outcomes' => InteractionOutcome::cases(),
            'templates' => $followUps->templates(),
            'agents' => User::permission('followups.create')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'callUrl' => $this->client ? $followUps->callUrl($this->client) : null,
            'money' => fn ($v) => Money::format($v),
        ]);
    }
}
