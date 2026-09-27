<?php

namespace App\Livewire\Inventory;

use App\Enums\StockOperationReason;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StockOperation;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Inventory\StockOperationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Espace magasinier : bons d'entrée et de sortie, avec la personne qui livre ou retire la marchandise.
 */
#[Title('Entrées & sorties')]
class Operations extends Component
{
    use InteractsWithToasts, WithPagination;

    #[Url(as: 'type')]
    public string $type = '';

    #[Url(as: 'entrepot')]
    public ?int $warehouseId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'du')]
    public ?string $from = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var list<array{product_id: string, quantity: string, unit_cost: string}> */
    public array $lines = [];

    public ?int $viewingId = null;

    public function mount(): void
    {
        $this->warehouseId ??= $this->warehouses->first()?->id;
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['type', 'warehouseId', 'search', 'from'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return Collection<int, Warehouse>
     */
    #[Computed]
    public function warehouses(): Collection
    {
        return Warehouse::query()->visibleTo(auth()->user())->where('is_active', true)->orderBy('name')->get();
    }

    public function create(string $type): void
    {
        $this->authorize('inventory.create');
        $this->form = [
            'type' => $type,
            'warehouse_id' => (string) ($this->warehouseId ?? $this->warehouses->first()?->id),
            'reason' => $type === 'entry' ? StockOperationReason::SupplierDelivery->value : StockOperationReason::WorkshopConsumption->value,
            'counterparty_type' => $type === 'entry' ? 'supplier' : 'employee',
            'supplier_id' => '', 'employee_id' => '', 'department_id' => '',
            'performed_by_name' => '', 'performed_by_phone' => '', 'reference' => '', 'notes' => '',
            'operated_at' => now()->format('Y-m-d\TH:i'),
        ];
        $this->lines = [['product_id' => '', 'quantity' => '', 'unit_cost' => '']];
        $this->modal('operation')->show();
    }

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => '', 'quantity' => '', 'unit_cost' => ''];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Pré-remplit la personne quand un employé est choisi.
     */
    public function updatedFormEmployeeId(mixed $value): void
    {
        if ($value && ($employee = Employee::query()->find($value))) {
            $this->form['performed_by_name'] = $employee->fullName();
            $this->form['performed_by_phone'] = (string) $employee->phone;
            $this->form['department_id'] = (string) $employee->department_id;
        }
    }

    public function save(StockOperationService $operations): void
    {
        $this->authorize('inventory.create');
        $type = $this->form['type'] ?? 'entry';

        $data = $this->validate([
            'form.warehouse_id' => ['required', Rule::in($this->warehouses->pluck('id')->all())],
            'form.reason' => ['required', Rule::in(array_keys(StockOperationReason::optionsFor($type)))],
            'form.counterparty_type' => ['required', 'in:supplier,employee,department,other'],
            'form.supplier_id' => ['nullable', 'required_if:form.counterparty_type,supplier', 'exists:suppliers,id'],
            'form.employee_id' => ['nullable', 'required_if:form.counterparty_type,employee', 'exists:employees,id'],
            'form.department_id' => ['nullable', 'required_if:form.counterparty_type,department', 'exists:departments,id'],
            'form.performed_by_name' => ['required', 'string', 'max:255'],
            'form.performed_by_phone' => ['nullable', 'string', 'max:30'],
            'form.reference' => ['nullable', 'string', 'max:100'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'form.operated_at' => ['required', 'date', 'before_or_equal:now'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'form.performed_by_name' => $type === 'entry' ? 'livré par' : 'retiré par',
            'form.supplier_id' => 'fournisseur', 'form.employee_id' => 'employé', 'form.department_id' => 'service',
            'lines.*.product_id' => 'article', 'lines.*.quantity' => 'quantité',
        ]);

        $form = array_map(fn ($value) => $value === '' ? null : $value, $data['form']);

        $operation = $this->attempt(fn () => $operations->record(
            Warehouse::query()->findOrFail($form['warehouse_id']),
            StockOperationReason::from($form['reason']),
            $form,
            $data['lines'],
            auth()->user(),
        ));

        if ($operation) {
            $this->modal('operation')->close();
            $this->toast("{$operation->typeLabel()} {$operation->number} enregistré.");
            $this->js('window.open('.json_encode(route('print.stock-operation', $operation)).', "_blank")');
        }
    }

    public function show(int $id): void
    {
        $this->viewingId = $id;
        $this->modal('detail')->show();
    }

    public function render()
    {
        $warehouseIds = $this->warehouseId ? [$this->warehouseId] : $this->warehouses->pluck('id')->all();
        $query = StockOperation::query()->with('warehouse', 'recorder', 'supplier', 'employee', 'department')
            ->withCount('movements')
            ->whereIn('warehouse_id', $warehouseIds)
            ->when($this->type, fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->from, fn (Builder $q) => $q->whereDate('operated_at', '>=', $this->from))
            ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('number', 'like', "%{$this->search}%")
                ->orWhere('performed_by_name', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%")));

        $today = StockOperation::query()->whereIn('warehouse_id', $warehouseIds)->whereDate('operated_at', today());

        return view('livewire.inventory.operations', [
            'operations' => (clone $query)->latest('operated_at')->paginate(20),
            'stats' => [
                'entries_today' => (clone $today)->where('type', 'entry')->count(),
                'exits_today' => (clone $today)->where('type', 'exit')->count(),
                'value_in' => (float) (clone $today)->where('type', 'entry')->sum('total_value'),
                'value_out' => (float) (clone $today)->where('type', 'exit')->sum('total_value'),
            ],
            'reasons' => StockOperationReason::optionsFor($this->form['type'] ?? 'entry'),
            'products' => Product::query()->where('is_stockable', true)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->visibleTo(auth()->user())->where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'viewing' => $this->viewingId ? StockOperation::query()->with('movements.product', 'warehouse', 'recorder', 'supplier', 'employee', 'department')->find($this->viewingId) : null,
        ]);
    }
}
