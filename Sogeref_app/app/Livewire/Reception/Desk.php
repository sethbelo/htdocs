<?php

namespace App\Livewire\Reception;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Reception\Concerns\HandlesFrontDesk;
use App\Models\Appointment;
use App\Models\MailRecord;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Visitor;
use App\Services\ContactService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Réception — Accueil du jour : visiteurs présents, rendez-vous attendus,
 * courrier du jour à transmettre et actions rapides (visiteur, courrier,
 * rendez-vous, demande client transmise au pipeline CRM).
 *
 * Les registres complets ont leurs écrans : Courrier (reception.mail) et
 * Visites (reception.visits). Les anciens liens ?onglet= y sont redirigés.
 */
#[Title('Accueil du jour')]
class Desk extends Component
{
    use HandlesFrontDesk, InteractsWithUi;

    // Demande client (lead)
    public bool $showLead = false;

    public array $lead = [];

    public function mount(): void
    {
        $target = ['visiteurs' => 'reception.visits', 'courrier' => 'reception.mail'][(string) request()->query('onglet')] ?? null;
        if ($target) {
            $this->redirectRoute($target);
        }
    }

    public function newLead(): void
    {
        $this->authorizeModule('reception', 'manage');
        $this->resetValidation();
        $this->lead = ['name' => '', 'company' => '', 'phone' => '', 'email' => '', 'need' => '', 'amount' => '', 'segment' => 'b2c'];
        $this->showLead = true;
    }

    public function saveLead(ContactService $contacts): void
    {
        $this->authorizeModule('reception', 'manage');
        $data = $this->validate(['lead.name' => ['required', 'string', 'max:120'], 'lead.company' => ['nullable', 'string', 'max:160'], 'lead.phone' => ['required', 'string', 'max:40'], 'lead.email' => ['nullable', 'email'], 'lead.need' => ['required', 'string', 'max:255'], 'lead.amount' => ['nullable', 'numeric', 'min:0'], 'lead.segment' => ['in:b2b,b2c']], [], ['lead.name' => 'nom', 'lead.phone' => 'téléphone', 'lead.need' => 'besoin'])['lead'];
        $existing = DB::transaction(function () use ($data, $contacts) {
            // Réutilise la fiche existante (même téléphone ou e-mail) plutôt que de créer un doublon.
            $contact = $contacts->duplicates($data['phone'], $data['email'] ?: null, null)->first();
            $existing = (bool) $contact;
            if (! $contact) {
                [$first, $last] = array_pad(explode(' ', $data['name'], 2), 2, null);
                $contact = $contacts->create([
                    'type' => 'prospect', 'segment' => $data['segment'], 'company_name' => $data['company'] ?: null, 'first_name' => $last ? $first : null, 'last_name' => $last ?? $first,
                    'phone' => $data['phone'], 'email' => $data['email'] ?: null, 'owner_id' => null,
                ]);
            }
            $pipeline = Pipeline::where('company_id', company_id())->where('is_default', true)->first();
            if ($pipeline) {
                Opportunity::create(['company_id' => company_id(), 'contact_id' => $contact->id, 'pipeline_id' => $pipeline->id, 'stage_id' => $pipeline->stages()->orderBy('position')->value('id'), 'title' => $data['need'], 'amount' => $data['amount'] ?: 0, 'currency_code' => 'USD', 'notes' => 'Demande reçue à l’accueil par '.auth()->user()->name]);
            }

            return $existing;
        });
        $this->showLead = false;
        $this->toast('Demande transmise au service commercial (pipeline CRM)'.($existing ? ' — rattachée à la fiche existante.' : '.'));
    }

    public function render()
    {
        $cid = company_id();
        $pendingMail = MailRecord::with('department', 'handler')->where('company_id', $cid)->where('direction', 'in')->where('status', 'registered')->latest('record_date')->latest('id')->limit(12)->get();

        return view('livewire.reception.desk', [
            'present' => Visitor::with('host')->where('company_id', $cid)->whereNull('checked_out_at')->whereDate('checked_in_at', today())->latest('checked_in_at')->get(),
            'todayCount' => Visitor::where('company_id', $cid)->whereDate('checked_in_at', today())->count(),
            'appointments' => Appointment::with('contact', 'user')->where('company_id', $cid)->whereDate('starts_at', today())->orderBy('starts_at')->get(),
            'pendingMail' => $pendingMail,
            'mailToday' => MailRecord::where('company_id', $cid)->whereDate('record_date', today())->count(),
            'hosts' => $this->hosts(),
            'departments' => $this->departments(),
        ]);
    }
}
