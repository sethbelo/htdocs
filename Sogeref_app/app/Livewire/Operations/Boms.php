<?php

namespace App\Livewire\Operations;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Bom;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Nomenclatures : composition (matières × quantités) de chaque produit
 * fabriqué. Sert au calcul automatique des listes de matériel.
 */
#[Title('Nomenclatures')]
class Boms extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;

    public ?int $editing = null;

    public ?int $productId = null;

    public string $yield = '1';

    public string $labourHours = '0';

    public string $notes = '';

    public array $lines = [];

    public function create(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->reset(['editing', 'productId', 'notes']);
        $this->yield = '1';
        $this->labourHours = '0';
        $this->lines = [['product_id' => null, 'quantity' => 1]];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $bom = Bom::with('lines')->where('company_id', company_id())->findOrFail($id);
        $this->editing = $bom->id;
        $this->productId = $bom->product_id;
        $this->yield = (string) (float) $bom->yield_quantity;
        $this->labourHours = (string) (float) $bom->labour_hours;
        $this->notes = $bom->notes ?? '';
        $this->lines = $bom->lines->map(fn ($l) => ['product_id' => $l->product_id, 'quantity' => (float) $l->quantity])->all();
        $this->showForm = true;
    }

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => null, 'quantity' => 1];
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function save(): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        $this->validate([
            'productId' => ['required', 'integer'],
            'yield' => ['required', 'numeric', 'gt:0'],
            'labourHours' => ['required', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'different:productId'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], [], ['productId' => 'produit', 'lines.*.product_id' => 'composant', 'lines.*.quantity' => 'quantité']);

        DB::transaction(function () {
            $product = Product::where('company_id', company_id())->findOrFail($this->productId);
            $bom = Bom::updateOrCreate(['id' => $this->editing], [
                'company_id' => company_id(), 'product_id' => $product->id, 'name' => 'Nomenclature '.$product->name,
                'yield_quantity' => $this->yield, 'labour_hours' => $this->labourHours, 'notes' => $this->notes ?: null,
            ]);
            $bom->lines()->delete();
            foreach ($this->lines as $line) {
                $bom->lines()->create($line);
            }
            $product->update(['is_manufactured' => true]);
        });
        $this->showForm = false;
        $this->toast('Nomenclature enregistrée.');
    }

    public function delete(int $id): void
    {
        $this->authorizeModule('manufacturing', 'manage');
        Bom::where('company_id', company_id())->findOrFail($id)->delete();
        $this->toast('Nomenclature supprimée.', 'warning');
    }

    public function render()
    {
        $cid = company_id();
        $components = Product::where('company_id', $cid)->whereIn('type', ['material', 'consumable', 'product'])->where('is_active', true)->orderBy('name')->get();

        return view('livewire.operations.boms', [
            'boms' => Bom::with('product', 'lines.product')->where('company_id', $cid)->get(),
            'finished' => Product::where('company_id', $cid)->whereIn('type', ['finished', 'product'])->where('is_active', true)->orderBy('name')->get(),
            'components' => $components,
            'formCost' => collect($this->lines)->sum(fn ($l) => (float) ($l['quantity'] ?? 0) * (float) ($components->firstWhere('id', $l['product_id'] ?? 0)?->purchase_price ?? 0)),
        ]);
    }
}
