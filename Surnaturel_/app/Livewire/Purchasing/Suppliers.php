<?php

namespace App\Livewire\Purchasing;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Fournisseurs')]
class Suppliers extends Component
{
    use InteractsWithToasts, WithPagination;

    public string $city = '';

    public string $state = 'active';

    public string $search = '';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function create(): void
    {
        $this->authorize('suppliers.create');
        $this->editingId = null;
        $this->form = ['code' => 'FRN-'.str_pad((string) (Supplier::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT), 'name' => '', 'contact_name' => '', 'phone' => '', 'email' => '', 'address' => '', 'city' => '', 'tax_id' => '', 'payment_terms_days' => 30, 'lead_time_days' => 7, 'notes' => ''];
        $this->modal('supplier')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('suppliers.update');
        $this->editingId = $id;
        $this->form = Supplier::query()->findOrFail($id)->only(['code', 'name', 'contact_name', 'phone', 'email', 'address', 'city', 'tax_id', 'payment_terms_days', 'lead_time_days', 'notes']);
        $this->modal('supplier')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'suppliers.update' : 'suppliers.create');
        $data = $this->validate([
            'form.code' => ['required', 'string', 'max:30', Rule::unique('suppliers', 'code')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.contact_name' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.city' => ['nullable', 'string', 'max:100'],
            'form.tax_id' => ['nullable', 'string', 'max:100'],
            'form.payment_terms_days' => ['required', 'integer', 'min:0'],
            'form.lead_time_days' => ['required', 'integer', 'min:0'],
            'form.notes' => ['nullable', 'string'],
        ])['form'];

        Supplier::query()->updateOrCreate(['id' => $this->editingId], array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->modal('supplier')->close();
        $this->toast('Fournisseur enregistré.');
    }

    public function render()
    {
        return view('livewire.purchasing.suppliers', [
            'suppliers' => Supplier::query()
                ->withCount('purchaseOrders')
                ->withSum(['invoices as invoiced' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
                ->withSum(['invoices as settled' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'amount_paid')
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('contact_name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")))
                ->when($this->city, fn ($q) => $q->where('city', $this->city))
                ->when($this->state !== '', fn ($q) => $q->where('is_active', $this->state === 'active'))
                ->orderBy('name')->paginate(20),
            'cities' => Supplier::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
        ]);
    }
}
