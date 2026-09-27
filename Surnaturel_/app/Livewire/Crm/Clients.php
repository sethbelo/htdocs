<?php

namespace App\Livewire\Crm;

use App\Enums\ClientType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Client;
use App\Models\User;
use App\Services\Support\NumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Clients')]
class Clients extends Component
{
    use InteractsWithToasts, ScopesToSite, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $type = '';

    #[Url(as: 'segment')]
    public string $segment = '';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('clients.create');
        $this->editingId = null;
        $this->form = ['type' => 'individual', 'segment' => 'b2c', 'name' => '', 'company_name' => '', 'phone' => '', 'whatsapp' => '', 'email' => '', 'address' => '', 'city' => '', 'tax_id' => '', 'sales_rep_id' => auth()->id(), 'site_id' => auth()->user()->default_site_id, 'allow_credit' => false, 'credit_limit' => 0, 'notes' => ''];
        $this->modal('client')->show();
    }

    public function edit(int $id): void
    {
        $client = Client::query()->findOrFail($id);
        $this->authorize('update', $client);
        $this->editingId = $id;
        $this->form = $client->only(['segment', 'name', 'company_name', 'phone', 'whatsapp', 'email', 'address', 'city', 'tax_id', 'sales_rep_id', 'site_id', 'allow_credit', 'credit_limit', 'notes']) + ['type' => $client->type->value];
        $this->modal('client')->show();
    }

    public function save(NumberGenerator $numbers): void
    {
        $this->authorize($this->editingId ? 'clients.update' : 'clients.create');

        $data = $this->validate([
            'form.type' => ['required', Rule::enum(ClientType::class)],
            'form.segment' => ['required', 'in:b2b,b2c'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.company_name' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.whatsapp' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.city' => ['nullable', 'string', 'max:100'],
            'form.tax_id' => ['nullable', 'string', 'max:100'],
            'form.sales_rep_id' => ['nullable', 'exists:users,id'],
            'form.site_id' => ['nullable', Rule::in(auth()->user()->accessibleSiteIds())],
            'form.allow_credit' => ['boolean'],
            'form.credit_limit' => ['nullable', 'numeric', 'min:0'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['form.name' => 'nom'])['form'];

        if (! auth()->user()->can('clients.approve')) {
            unset($data['allow_credit'], $data['credit_limit']);
        }

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->editingId) {
            Client::query()->findOrFail($this->editingId)->update($data);
        } else {
            Client::query()->create($data + ['number' => $numbers->next(NumberGenerator::Client)]);
        }

        $this->modal('client')->close();
        $this->toast('Client enregistré.');
    }

    public function render()
    {
        return view('livewire.crm.clients', [
            'clients' => Client::query()
                ->where(fn (Builder $q) => $q->whereNull('site_id')->orWhereIn('site_id', auth()->user()->accessibleSiteIds()))
                ->forSite($this->siteId)
                ->with('salesRep', 'loyaltyAccount.tier')
                ->withCount('orders')
                ->withSum(['invoices as due' => fn (Builder $q) => $q->whereIn('status', ['issued', 'partially_paid'])], 'total')
                ->withSum(['invoices as paid_on_due' => fn (Builder $q) => $q->whereIn('status', ['issued', 'partially_paid'])], 'amount_paid')
                ->search($this->search)
                ->when($this->type, fn (Builder $q) => $q->where('type', $this->type))
                ->when($this->segment, fn (Builder $q) => $q->where('segment', $this->segment))
                ->latest()->paginate(20),
            'types' => ClientType::options(),
            'salesReps' => User::permission('clients.create')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
