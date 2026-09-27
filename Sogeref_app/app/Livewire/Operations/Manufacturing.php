<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Employee;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Services\ManufacturingService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Atelier : tableau des ordres de fabrication et dossiers de construction.
 * Sur tablette, les cartes se déplacent au doigt d'une colonne à l'autre.
 */
#[Title('Atelier & fabrication')]
class Manufacturing extends Component
{
    use InteractsWithUi;

    #[Url(as: 'type')]
    public string $type = '';

    public string $search = '';

    public bool $showCreate = false;

    public ?int $productId = null;

    public string $quantity = '1';

    public ?int $responsibleId = null;

    public string $plannedEnd = '';

    public string $priority = 'normale';

    public const COLUMNS = ['confirmed', 'in_progress', 'quality', 'done'];

    /** Glisser-déposer : applique la transition métier correspondante. */
    public function move(int $id, string $status, array $orderedIds = []): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $mo = ManufacturingOrder::where('company_id', company_id())->findOrFail($id);
        if ($mo->status === $status) {
            return;
        }
        $service = app(ManufacturingService::class);
        $this->attempt(fn () => match ($status) {
            'in_progress' => $service->start($mo),
            'quality' => $mo->status === 'confirmed' ? throw new \DomainException('Démarrez la production avant le contrôle qualité.') : $service->toQuality($mo),
            'done' => $service->finish($mo),
            default => throw new \DomainException('Retour en arrière impossible depuis le tableau : utilisez la fiche de l’ordre.'),
        }, "{$mo->number} → ".erp_status('manufacturing', $status)[0]);
    }

    public function openCreate(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->reset(['productId', 'responsibleId', 'priority']);
        $this->quantity = '1';
        $this->plannedEnd = now()->addDays(10)->toDateString();
        $this->showCreate = true;
    }

    public function create(ManufacturingService $service)
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->validate(['productId' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0'], 'plannedEnd' => ['required', 'date']], [], ['productId' => 'produit', 'quantity' => 'quantité', 'plannedEnd' => 'date de fin']);
        $product = Product::where('company_id', company_id())->findOrFail($this->productId);
        $mo = $service->createForStock($product, (float) $this->quantity, ['responsible_id' => $this->responsibleId, 'planned_end' => $this->plannedEnd, 'priority' => $this->priority]);
        session()->flash('toast', ['type' => 'success', 'message' => "Ordre {$mo->number} créé avec sa liste de matériel."]);

        return $this->redirectRoute('manufacturing.show', $mo, navigate: true);
    }

    public function render()
    {
        $cid = company_id();
        $orders = ManufacturingOrder::with('salesOrder.contact', 'responsible', 'product')
            ->where('company_id', $cid)
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")->orWhere('title', 'like', "%{$this->search}%")))
            ->where(fn ($q) => $q->where('status', '!=', 'done')->orWhere('finished_at', '>=', now()->subDays(30)))
            ->whereNot('status', 'cancelled')
            ->orderByRaw("CASE priority WHEN 'urgente' THEN 0 WHEN 'haute' THEN 1 WHEN 'normale' THEN 2 ELSE 3 END")->orderBy('planned_end')
            ->get();

        return view('livewire.operations.manufacturing', [
            'columns' => $orders->groupBy(fn ($o) => $o->status === 'draft' ? 'confirmed' : $o->status),
            'stats' => [
                'planned' => $orders->whereIn('status', ['draft', 'confirmed'])->count(),
                'progress' => $orders->where('status', 'in_progress')->count(),
                'quality' => $orders->where('status', 'quality')->count(),
                'late' => $orders->filter->isDelayed()->count(),
            ],
            'products' => Product::where('company_id', $cid)->where('is_manufactured', true)->where('is_active', true)->has('bom')->orderBy('name')->get(),
            'employees' => Employee::where('company_id', $cid)->where('status', 'active')->where('pay_type', 'monthly')->orderBy('last_name')->get(),
        ]);
    }
}
