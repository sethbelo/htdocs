<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Crm\Concerns\EditsContacts;
use App\Models\Contact;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Contacts : clients (B2B / B2C), prospects, fournisseurs et partenaires.
 * Création guidée (personne ou entreprise) avec détection des doublons.
 * ?nouveau=1 ouvre directement le formulaire de création.
 */
#[Title('Contacts')]
class Contacts extends Component
{
    use EditsContacts, InteractsWithUi, WithPagination;

    #[Url(as: 'type')]
    public string $type = 'client';

    #[Url(as: 'segment')]
    public string $segment = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        if (request()->boolean('nouveau') && auth()->user()->canManage('contacts')) {
            $this->edit(null, $this->type);
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['type', 'segment', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $cid = company_id();

        return view('livewire.crm.contacts', [
            'contacts' => Contact::with('loyaltyCard')->withCount('salesOrders')->withSum(['documents as invoiced' => fn ($q) => $q->where('type', 'invoice')->whereNot('status', 'cancelled')], 'total')
                ->where('company_id', $cid)->where('type', $this->type)
                ->when($this->segment, fn ($q) => $q->where('segment', $this->segment))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('company_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('first_name', 'like', "%{$this->search}%")->orWhere('reference', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->orderByRaw('COALESCE(company_name, last_name)')->paginate(20),
            'counts' => Contact::where('company_id', $cid)->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type'),
        ]);
    }
}
