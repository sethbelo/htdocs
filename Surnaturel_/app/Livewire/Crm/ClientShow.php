<?php

namespace App\Livewire\Crm;

use App\Enums\DocumentCategory;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Client;
use App\Services\Collaboration\CommentService;
use App\Services\Crm\LoyaltyService;
use App\Services\Documents\DocumentService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Fiche client')]
class ClientShow extends Component
{
    use InteractsWithToasts, WithFileUploads;

    public Client $client;

    public string $tab = 'orders';

    public string $note = '';

    public $document = null;

    public function mount(Client $client): void
    {
        $this->authorize('view', $client);
        $this->client = $client;
    }

    public function enrollLoyalty(LoyaltyService $loyalty): void
    {
        $this->authorize('loyalty.create');
        $loyalty->accountFor($this->client);
        $this->toast('Compte fidélité créé.');
    }

    public function addNote(CommentService $comments): void
    {
        $this->validate(['note' => ['required', 'string', 'max:3000']]);
        $comments->post($this->client, auth()->user(), $this->note);
        $this->reset('note');
    }

    public function uploadDocument(DocumentService $documents): void
    {
        $this->authorize('documents.create');
        $this->validate(['document' => ['required', 'file', 'max:51200']]);
        $documents->store($this->document, DocumentCategory::Administrative, auth()->user(), null, $this->client, siteId: $this->client->site_id);
        $this->reset('document');
        $this->toast('Document ajouté.');
    }

    public function render()
    {
        $this->client->load(['salesRep', 'loyaltyAccount.tier', 'loyaltyAccount.transactions', 'comments.user', 'documents.latestVersion']);

        return view('livewire.crm.client-show', [
            'orders' => $this->client->orders()->with('site')->latest('ordered_at')->limit(50)->get(),
            'invoices' => $this->client->invoices()->latest('issued_at')->limit(50)->get(),
            'payments' => $this->client->payments()->latest('paid_at')->limit(50)->get(),
            'sales' => $this->client->sales()->latest('sold_at')->limit(30)->get(),
            'balance' => $this->client->outstandingBalance(),
            'lifetime' => (float) $this->client->payments()->where('direction', 'in')->sum('amount') + (float) $this->client->sales()->sum('total'),
        ])->title($this->client->displayName());
    }
}
