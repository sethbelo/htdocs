<?php

namespace App\Livewire\Stock;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Document;
use App\Services\ReplenishmentService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Achats : propositions de réapprovisionnement automatique (seuils) et
 * suivi des bons de commande fournisseurs jusqu'à la réception.
 */
#[Title('Achats & réapprovisionnement')]
class Purchases extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url(as: 'onglet')]
    public string $tab = 'reappro';

    public array $selected = [];

    public string $search = '';

    public function generate(ReplenishmentService $service): void
    {
        $this->authorizeModule('purchases', 'manage');
        $orders = $this->attempt(fn () => $service->generate($this->selected ?: null));
        if ($orders) {
            $this->toast($orders->count().' bon(s) de commande fournisseur généré(s) en brouillon.');
            $this->selected = [];
            $this->tab = 'commandes';
        }
    }

    public function render(ReplenishmentService $service)
    {
        $proposals = $this->tab === 'reappro' ? $service->proposals() : collect();

        return view('livewire.stock.purchases', [
            'proposals' => $proposals,
            'proposalTotal' => $proposals->sum(fn ($p) => $p->suggested * (float) $p->purchase_price),
            'orders' => $this->tab === 'commandes' ? Document::with('contact', 'items')->where('company_id', company_id())->where('type', 'purchase_order')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")->orWhereHas('contact', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%"))))
                ->latest('issue_date')->latest('id')->paginate(15) : null,
            'openCount' => Document::where('company_id', company_id())->where('type', 'purchase_order')->whereNotIn('status', ['cancelled', 'accepted'])->count(),
        ]);
    }
}
