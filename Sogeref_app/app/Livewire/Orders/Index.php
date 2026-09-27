<?php

namespace App\Livewire\Orders;

use App\Models\SalesOrder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Suivi des commandes : vue tableau de bord (kanban par étape) ou liste.
 */
#[Title('Suivi des commandes')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'vue')]
    public string $view = 'kanban';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $stage = '';

    #[Url(as: 'nature')]
    public string $kind = '';

    public function mount(): void
    {
        if ($this->stage) {
            $this->view = 'liste';
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'stage', 'kind'], true)) {
            $this->resetPage();
        }
    }

    private function query()
    {
        return SalesOrder::with('contact', 'owner', 'manufacturingOrder', 'site')
            ->where('company_id', company_id())
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")->orWhere('title', 'like', "%{$this->search}%")
                ->orWhereHas('contact', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%"))))
            ->when($this->kind, fn ($q) => $q->where('kind', $this->kind));
    }

    public function render()
    {
        $stages = collect(config('erp.statuses.order_stage'))->except(['annule']);
        $counts = SalesOrder::where('company_id', company_id())->selectRaw('stage, count(*) as c, sum(total) as t, sum(amount_paid) as p')->groupBy('stage')->get()->keyBy('stage');

        return view('livewire.orders.index', [
            'stages' => $stages,
            'counts' => $counts,
            'columns' => $this->view === 'kanban'
                ? $this->query()->whereNotIn('stage', ['cloture', 'annule'])->orderByRaw("CASE priority WHEN 'urgente' THEN 0 WHEN 'haute' THEN 1 WHEN 'normale' THEN 2 ELSE 3 END")->latest('updated_at')->get()->groupBy('stage')
                : collect(),
            'orders' => $this->view === 'liste'
                ? $this->query()->when($this->stage, fn ($q) => $q->where('stage', $this->stage))->latest('updated_at')->paginate(15)
                : null,
        ]);
    }
}
