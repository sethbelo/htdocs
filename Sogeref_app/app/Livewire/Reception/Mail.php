<?php

namespace App\Livewire\Reception;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Reception\Concerns\HandlesFrontDesk;
use App\Models\MailRecord;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Réception — Registre du courrier entrant / sortant : référence
 * automatique (CE / CS), service et personne destinataires, circuit
 * enregistré → transmis → traité → archivé, scan joint et impression.
 */
#[Title('Courrier')]
class Mail extends Component
{
    use HandlesFrontDesk, InteractsWithUi, WithPagination;

    #[Url(as: 'sens')]
    public string $direction = '';

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'du')]
    public string $from = '';

    #[Url(as: 'au')]
    public string $to = '';

    #[Url(as: 'service')]
    public string $department = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function updating($name): void
    {
        if (in_array($name, ['direction', 'status', 'from', 'to', 'department', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('direction', 'status', 'from', 'to', 'department', 'search');
        $this->resetPage();
    }

    /** Filtres courants (réutilisés par l'impression du registre). */
    public function filters(): array
    {
        return array_filter(['sens' => $this->direction, 'statut' => $this->status, 'du' => $this->from, 'au' => $this->to, 'service' => $this->department, 'q' => $this->search]);
    }

    public static function registerQuery(int $companyId, array $f): Builder
    {
        $date = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : null;

        return MailRecord::with('department', 'handler')->where('company_id', $companyId)
            ->when(in_array($f['sens'] ?? '', ['in', 'out'], true), fn ($q) => $q->where('direction', $f['sens']))
            ->when(array_key_exists($f['statut'] ?? '', config('erp.statuses.mail')), fn ($q) => $q->where('status', $f['statut']))
            ->when($date($f['du'] ?? null), fn ($q, $d) => $q->whereDate('record_date', '>=', $d))
            ->when($date($f['au'] ?? null), fn ($q, $d) => $q->whereDate('record_date', '<=', $d))
            ->when(ctype_digit((string) ($f['service'] ?? '')), fn ($q) => $q->where('department_id', (int) $f['service']))
            ->when($f['q'] ?? '', fn ($q, $s) => $q->where(fn ($q) => $q->where('reference', 'like', "%{$s}%")->orWhere('correspondent', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%")))
            ->latest('record_date')->latest('id');
    }

    public function render()
    {
        $cid = company_id();
        $base = MailRecord::where('company_id', $cid);

        return view('livewire.reception.mail', [
            'mails' => self::registerQuery($cid, $this->filters())->paginate(20),
            'stats' => [
                'in' => (clone $base)->where('direction', 'in')->whereDate('record_date', '>=', now()->startOfMonth())->count(),
                'out' => (clone $base)->where('direction', 'out')->whereDate('record_date', '>=', now()->startOfMonth())->count(),
                'registered' => (clone $base)->where('status', 'registered')->count(),
                'forwarded' => (clone $base)->where('status', 'forwarded')->count(),
            ],
            'hosts' => $this->hosts(),
            'departments' => $this->departments(),
        ]);
    }
}
