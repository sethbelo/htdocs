<?php

namespace App\Livewire\Crm\Concerns;

use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Validation\Rule;

/**
 * Formulaire « Nouveau contact / Modifier » partagé (Contacts, Annuaire) :
 * personne ou entreprise, type, téléphones, WhatsApp (si la colonne existe),
 * et détection des doublons avant création.
 *
 * Requiert InteractsWithUi. Vue : livewire.crm.partials.contact-form
 */
trait EditsContacts
{
    public bool $showForm = false;

    public ?int $editing = null;

    public array $form = [];

    /** Doublons potentiels détectés : [[name, reference, phone, email, url]] */
    public array $duplicates = [];

    public function edit(?int $id = null, ?string $type = null): void
    {
        $this->authorizeModule('contacts', 'manage');
        $c = $id ? Contact::where('company_id', company_id())->findOrFail($id) : null;
        $type = $c?->type->value ?? (in_array($type, array_keys(ContactService::PREFIXES), true) ? $type : 'client');
        $this->editing = $c?->id;
        $this->duplicates = [];
        $this->resetValidation();
        $this->form = [
            'type' => $type, 'segment' => $c?->segment ?? ($type === 'supplier' || $type === 'partner' ? 'b2b' : 'b2c'), 'company_name' => $c?->company_name ?? '', 'first_name' => $c?->first_name ?? '',
            'last_name' => $c?->last_name ?? '', 'email' => $c?->email ?? '', 'phone' => $c?->phone ?? '', 'whatsapp' => ContactService::hasColumn('whatsapp') ? ($c?->whatsapp ?? '') : '',
            'city' => $c?->city ?? '', 'address' => data_get($c?->billing_address, 'line1', ''), 'tax_number' => $c?->tax_number ?? '', 'payment_terms' => $c?->payment_terms ?? 30,
            'discount_rate' => (float) ($c?->discount_rate ?? 0), 'credit_limit' => (float) ($c?->credit_limit ?? 0), 'notes' => $c?->notes ?? '', 'is_active' => $c?->is_active ?? true,
            'marketing_opt_in' => ContactService::hasColumn('marketing_opt_in') ? (bool) ($c?->marketing_opt_in ?? true) : true,
        ];
        $this->showForm = true;
    }

    public function save(ContactService $contacts, bool $force = false): void
    {
        $this->authorizeModule('contacts', 'manage');
        $segment = $this->form['segment'] ?? '';
        $data = $this->validate([
            'form.type' => ['required', Rule::in(array_keys(ContactService::PREFIXES))], 'form.segment' => ['required', 'in:b2b,b2c'],
            'form.company_name' => [Rule::requiredIf($segment === 'b2b'), 'nullable', 'string', 'max:160'],
            'form.first_name' => ['nullable', 'string', 'max:80'], 'form.last_name' => [Rule::requiredIf($segment === 'b2c'), 'nullable', 'string', 'max:80'],
            'form.email' => ['nullable', 'email'], 'form.phone' => ['nullable', 'string', 'max:40'], 'form.whatsapp' => ['nullable', 'string', 'max:40'],
            'form.city' => ['nullable', 'string', 'max:80'], 'form.address' => ['nullable', 'string', 'max:255'],
            'form.tax_number' => ['nullable', 'string', 'max:60'], 'form.payment_terms' => ['integer', 'between:0,180'], 'form.discount_rate' => ['numeric', 'between:0,50'],
            'form.credit_limit' => ['numeric', 'min:0'], 'form.notes' => ['nullable', 'string', 'max:2000'], 'form.is_active' => ['boolean'], 'form.marketing_opt_in' => ['boolean'],
        ], [], ['form.company_name' => 'raison sociale', 'form.last_name' => 'nom'])['form'];

        $address = $data['address'];
        unset($data['address']);
        foreach (['whatsapp', 'marketing_opt_in'] as $optional) {
            if (! ContactService::hasColumn($optional)) {
                unset($data[$optional]);
            }
        }
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data) + ['billing_address' => ['line1' => $address, 'city' => $data['city'], 'country' => 'RDC']];

        if (! $force) {
            $name = $data['segment'] === 'b2b' ? $data['company_name'] : trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
            $found = $contacts->duplicates($data['phone'] ?? ($data['whatsapp'] ?? null), $data['email'], $name, $this->editing);
            if ($found->isNotEmpty()) {
                $this->duplicates = $found->map(fn (Contact $c) => [
                    'name' => $c->display_name, 'reference' => $c->reference, 'type' => $c->type->label(), 'phone' => $c->phone, 'email' => $c->email, 'url' => route('contacts.show', $c),
                ])->all();
                $this->toast('Un contact similaire existe déjà : vérifiez avant de continuer.', 'warning');

                return;
            }
        }

        if ($this->editing) {
            $contact = Contact::where('company_id', company_id())->findOrFail($this->editing);
            $contact->update($data);
        } else {
            $contact = $contacts->create($data);
        }
        $this->duplicates = [];
        $this->showForm = false;
        $this->toast($this->editing ? 'Fiche enregistrée.' : "Contact {$contact->display_name} créé ({$contact->reference}).");
        $this->editing = null;
    }

    /** Confirme l'enregistrement malgré les doublons signalés. */
    public function saveAnyway(ContactService $contacts): void
    {
        $this->save($contacts, true);
    }
}
