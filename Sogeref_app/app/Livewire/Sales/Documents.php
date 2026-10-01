<?php

namespace App\Livewire\Sales;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des devis ou des factures (le type vient de la route).
 */
class Documents extends Component
{
    use WithPagination;

    public string $type = 'quote';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(string $type = 'quote'): void
    {
        $this->type = $type;
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $cid = company_id();
        $base = Document::where('company_id', $cid)->where('type', $this->type);
        $isQuote = $this->type === 'quote';

        $stats = $isQuote ? [
            ['En attente de réponse', money((clone $base)->whereIn('status', ['draft', 'sent', 'viewed'])->sum('total'), 'USD', 0), 'clock', 'sun'],
            ['Acceptés (12 mois)', money((clone $base)->where('status', 'accepted')->where('issue_date', '>=', now()->subYear())->sum('total'), 'USD', 0), 'check-badge', 'emerald'],
            ['Taux de transformation', ((clone $base)->count() ? round((clone $base)->where('status', 'accepted')->count() / (clone $base)->count() * 100) : 0).' %', 'arrow-trending-up', 'lagoon'],
            ['Devis émis', (clone $base)->count(), 'document-text', 'brand'],
        ] : [
            ['Facturé (mois)', money((clone $base)->where('issue_date', '>=', now()->startOfMonth())->whereNot('status', 'cancelled')->sum('total'), 'USD', 0), 'document-currency-dollar', 'brand'],
            ['Encaissé', money((clone $base)->sum('amount_paid'), 'USD', 0), 'banknotes', 'emerald'],
            ['Reste à encaisser', money((clone $base)->whereNotIn('status', ['paid', 'cancelled', 'draft'])->sum(DB::raw('total - amount_paid')), 'USD', 0), 'clock', 'sun'],
            ['En retard', (clone $base)->whereNotIn('status', ['paid', 'cancelled', 'draft'])->whereDate('due_date', '<', today())->count(), 'exclamation-triangle', 'flame'],
        ];

        $statuses = $isQuote ? ['draft', 'sent', 'accepted', 'rejected'] : ['draft', 'sent', 'partially_paid', 'paid', 'cancelled'];

        return view('livewire.sales.documents', [
            'isQuote' => $isQuote,
            'stats' => $stats,
            'statuses' => $statuses,
            'documents' => (clone $base)->with('contact')
                ->when($this->status === 'overdue', fn ($q) => $q->whereNotIn('status', ['paid', 'cancelled', 'draft'])->whereDate('due_date', '<', today()))
                ->when($this->status && $this->status !== 'overdue', fn ($q) => $q->where('status', $this->status))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")
                    ->orWhereHas('contact', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('first_name', 'like', "%{$this->search}%"))))
                ->latest('issue_date')->latest('id')->paginate(15),
        ])->title($isQuote ? 'Devis & pro forma' : 'Factures');
    }
}
