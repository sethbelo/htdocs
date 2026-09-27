<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Vue 360° d'un client ou fournisseur : commandes, factures, paiements,
 * rendez-vous, opportunités et fidélité.
 */
class ContactShow extends Component
{
    use InteractsWithUi;

    public Contact $contact;

    public function mount(Contact $contact): void
    {
        abort_unless($contact->company_id === company_id(), 404);
        $this->contact = $contact;
    }

    public function issueCard(LoyaltyService $loyalty): void
    {
        $this->authorizeModule('loyalty', 'manage');
        $card = $loyalty->cardFor($this->contact);
        $card ? $this->toast("Carte {$card->card_number} émise.") : $this->toast('Aucun programme de fidélité actif pour ce segment.', 'error');
    }

    public function convertToClient(): void
    {
        $this->authorizeModule('contacts', 'manage');
        $this->contact->update(['type' => 'client']);
        $this->toast('Le prospect est désormais client.');
    }

    public function render()
    {
        $this->contact->load('loyaltyCard.program', 'loyaltyCard.transactions', 'owner');
        $documents = $this->contact->documents()->latest('issue_date')->get();
        $invoices = $documents->where('type.value', 'invoice');

        return view('livewire.crm.contact-show', [
            'orders' => $this->contact->salesOrders()->latest()->get(),
            'documents' => $documents,
            'stats' => [
                'invoiced' => $invoices->whereNotIn('status.value', ['cancelled'])->sum('total'),
                'paid' => $invoices->sum('amount_paid'),
                'due' => $invoices->whereNotIn('status.value', ['cancelled', 'draft'])->sum(fn ($d) => $d->balance_due),
                'orders' => $this->contact->salesOrders()->count(),
            ],
            'opportunities' => $this->contact->opportunities()->with('stage')->get(),
            'appointments' => $this->contact->appointments()->with('user')->latest('starts_at')->limit(8)->get(),
            'payments' => DB::table('payments')->where('contact_id', $this->contact->id)->whereNull('deleted_at')->latest('paid_at')->limit(10)->get(),
        ])->title($this->contact->display_name);
    }
}
