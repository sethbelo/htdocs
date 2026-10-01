<?php

namespace App\Livewire\Reception;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Reception\Concerns\HandlesFrontDesk;
use App\Models\Visitor;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Réception — Registre des visites : arrivée (identité, pièce, objet,
 * personne visitée, badge), départ, historique filtré, statistiques et
 * impression du badge visiteur.
 */
#[Title('Visites')]
class Visits extends Component
{
    use HandlesFrontDesk, InteractsWithUi, WithPagination;

    #[Url(as: 'du')]
    public string $from = '';

    #[Url(as: 'au')]
    public string $to = '';

    #[Url(as: 'etat')]
    public string $state = '';

    #[Url(as: 'hote')]
    public string $host = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->from = $this->validDate($this->from) ?? today()->subDays(6)->toDateString();
        $this->to = $this->validDate($this->to) ?? today()->toDateString();
    }

    public function updating($name): void
    {
        if (in_array($name, ['from', 'to', 'state', 'host', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function period(string $period): void
    {
        [$this->from, $this->to] = match ($period) {
            'today' => [today()->toDateString(), today()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), today()->toDateString()],
            default => [today()->subDays(6)->toDateString(), today()->toDateString()],
        };
        $this->resetPage();
    }

    private function validDate(?string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : null;
    }

    public function render()
    {
        $cid = company_id();
        $from = $this->validDate($this->from) ?? today()->toDateString();
        $to = $this->validDate($this->to) ?? today()->toDateString();
        $visits = Visitor::with('host', 'appointment')->where('company_id', $cid)
            ->whereDate('checked_in_at', '>=', $from)->whereDate('checked_in_at', '<=', $to)
            ->when($this->state === 'present', fn ($q) => $q->whereNull('checked_out_at'))
            ->when($this->state === 'left', fn ($q) => $q->whereNotNull('checked_out_at'))
            ->when(ctype_digit($this->host), fn ($q) => $q->where('host_id', (int) $this->host))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('full_name', 'like', "%{$this->search}%")->orWhere('organization', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")->orWhere('purpose', 'like', "%{$this->search}%")->orWhere('badge_number', 'like', "%{$this->search}%")));

        $period = (clone $visits)->get(['checked_in_at', 'checked_out_at']);
        $durations = $period->filter(fn ($v) => $v->checked_out_at)->map(fn ($v) => $v->checked_in_at->diffInMinutes($v->checked_out_at, true));

        return view('livewire.reception.visits', [
            'visits' => $visits->latest('checked_in_at')->paginate(20),
            'stats' => [
                'present' => Visitor::where('company_id', $cid)->whereNull('checked_out_at')->whereDate('checked_in_at', today())->count(),
                'today' => Visitor::where('company_id', $cid)->whereDate('checked_in_at', today())->count(),
                'period' => $period->count(),
                'avg' => $durations->isNotEmpty() ? (int) round($durations->avg()) : null,
            ],
            'hosts' => $this->hosts(),
        ]);
    }
}
