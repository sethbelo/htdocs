<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Clients (B2B / B2C), prospects et fournisseurs.
 */
#[Title('Clients & fournisseurs')]
class Contacts extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url(as: 'type')]
    public string $type = 'client';

    #[Url(as: 'segment')]
    public string $segment = '';

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editing = null;

    public array $form = [];

    public function updating($name): void
    {
        if (in_array($name, ['type', 'segment', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function edit(?int $id = null): void
    {
        $this->authorizeModule('contacts', 'manage');
        $c = $id ? Contact::where('company_id', company_id())->findOrFail($id) : null;
        $this->editing = $c?->id;
        $this->form = [
            'type' => $c?->type->value ?? ($this->type ?: 'client'), 'segment' => $c?->segment ?? 'b2b', 'company_name' => $c?->company_name ?? '', 'first_name' => $c?->first_name ?? '',
            'last_name' => $c?->last_name ?? '', 'email' => $c?->email ?? '', 'phone' => $c?->phone ?? '', 'city' => $c?->city ?? '', 'address' => data_get($c?->billing_address, 'line1', ''),
            'tax_number' => $c?->tax_number ?? '', 'payment_terms' => $c?->payment_terms ?? 30, 'discount_rate' => (float) ($c?->discount_rate ?? 0), 'credit_limit' => (float) ($c?->credit_limit ?? 0),
            'notes' => $c?->notes ?? '', 'is_active' => $c?->is_active ?? true,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeModule('contacts', 'manage');
        $data = $this->validate([
            'form.type' => ['required', 'in:client,supplier,prospect'], 'form.segment' => ['required', 'in:b2b,b2c'],
            'form.company_name' => [Rule::requiredIf(($this->form['segment'] ?? '') === 'b2b' || ($this->form['type'] ?? '') === 'supplier'), 'nullable', 'string', 'max:160'],
            'form.first_name' => ['nullable', 'string', 'max:80'], 'form.last_name' => [Rule::requiredIf(($this->form['segment'] ?? '') === 'b2c' && ($this->form['type'] ?? '') !== 'supplier'), 'nullable', 'string', 'max:80'],
            'form.email' => ['nullable', 'email'], 'form.phone' => ['nullable', 'string', 'max:40'], 'form.city' => ['nullable', 'string', 'max:80'], 'form.address' => ['nullable', 'string', 'max:255'],
            'form.tax_number' => ['nullable', 'string', 'max:60'], 'form.payment_terms' => ['integer', 'between:0,180'], 'form.discount_rate' => ['numeric', 'between:0,50'],
            'form.credit_limit' => ['numeric', 'min:0'], 'form.notes' => ['nullable', 'string', 'max:2000'], 'form.is_active' => ['boolean'],
        ], [], ['form.company_name' => 'raison sociale', 'form.last_name' => 'nom'])['form'];
        $address = $data['address'];
        unset($data['address']);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data) + ['billing_address' => ['line1' => $address, 'city' => $data['city'], 'country' => 'RDC']];

        if ($this->editing) {
            Contact::where('company_id', company_id())->findOrFail($this->editing)->update($data);
        } else {
            $prefix = ['client' => 'CU', 'supplier' => 'FRN', 'prospect' => 'PRS'][$data['type']];
            $count = Contact::withTrashed()->where('company_id', company_id())->where('type', $data['type'])->count() + 1;
            Contact::create($data + ['company_id' => company_id(), 'reference' => $prefix.now()->format('ym').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT), 'currency_code' => 'USD', 'owner_id' => auth()->id()]);
        }
        $this->showForm = false;
        $this->toast('Fiche enregistrée.');
    }

    public function render()
    {
        $cid = company_id();

        return view('livewire.crm.contacts', [
            'contacts' => Contact::with('loyaltyCard')->withCount('salesOrders')->withSum(['documents as invoiced' => fn ($q) => $q->where('type', 'invoice')->whereNot('status', 'cancelled')], 'total')
                ->where('company_id', $cid)->where('type', $this->type)
                ->when($this->segment, fn ($q) => $q->where('segment', $this->segment))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('company_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('first_name', 'like', "%{$this->search}%")->orWhere('reference', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")))
                ->orderByRaw('COALESCE(company_name, last_name)')->paginate(20),
            'counts' => Contact::where('company_id', $cid)->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type'),
        ]);
    }
}
