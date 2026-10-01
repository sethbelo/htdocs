{{-- Formulaire contact partagé (trait App\Livewire\Crm\Concerns\EditsContacts) --}}
@php
    $isCompany = ($form['segment'] ?? '') === 'b2b';
    $hasWhatsapp = \App\Services\ContactService::hasColumn('whatsapp');
    $hasOptIn = \App\Services\ContactService::hasColumn('marketing_opt_in');
@endphp
<x-ui.drawer wire:model="showForm" :title="$editing ? 'Modifier le contact' : 'Nouveau contact'" :subtitle="$editing ? null : 'Personne ou entreprise : client, prospect, fournisseur ou partenaire'" icon="user-plus" size="lg">
    <div class="space-y-5">
        <div>
            <p class="mb-2 text-[12.5px] font-semibold text-fg-muted">Il s’agit d’…</p>
            <div class="segmented">
                <button type="button" wire:click="$set('form.segment', 'b2c')" aria-pressed="{{ $isCompany ? 'false' : 'true' }}"><x-ui.icon name="user" class="size-4" /> Une personne</button>
                <button type="button" wire:click="$set('form.segment', 'b2b')" aria-pressed="{{ $isCompany ? 'true' : 'false' }}"><x-ui.icon name="building-office" class="size-4" /> Une entreprise</button>
            </div>
        </div>
        <div>
            <p class="mb-2 text-[12.5px] font-semibold text-fg-muted">Type de relation</p>
            <div class="segmented flex-wrap">
                @foreach (\App\Enums\ContactType::options() as $k => $l)
                    <button type="button" wire:click="$set('form.type', '{{ $k }}')" aria-pressed="{{ ($form['type'] ?? '') === $k ? 'true' : 'false' }}">{{ $l }}</button>
                @endforeach
            </div>
        </div>

        @if ($duplicates)
            <div class="rounded-xl border border-sun-300 bg-sun-50 p-3 text-[13px] dark:border-sun-400/30 dark:bg-sun-400/10" role="alert">
                <p class="flex items-center gap-2 font-semibold"><x-ui.icon name="exclamation-triangle" class="size-4 text-sun-700" /> Doublon possible</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($duplicates as $d)
                        <li><a href="{{ $d['url'] }}" wire:navigate class="link">{{ $d['name'] }}</a> <span class="text-fg-subtle">· {{ $d['reference'] }} · {{ $d['type'] }} {{ $d['phone'] ? '· '.$d['phone'] : '' }} {{ $d['email'] ? '· '.$d['email'] : '' }}</span></li>
                    @endforeach
                </ul>
                <x-ui.button variant="secondary" size="sm" class="mt-3" icon="check" wire:click="saveAnyway">Ce n’est pas la même personne : enregistrer quand même</x-ui.button>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($isCompany)
                <x-ui.input label="Raison sociale" wire:model="form.company_name" class="sm:col-span-2" required />
                <x-ui.input label="Prénom de l’interlocuteur" wire:model="form.first_name" />
                <x-ui.input label="Nom de l’interlocuteur" wire:model="form.last_name" />
            @else
                <x-ui.input label="Prénom" wire:model="form.first_name" />
                <x-ui.input label="Nom" wire:model="form.last_name" required />
                <x-ui.input label="Société (facultatif)" wire:model="form.company_name" class="sm:col-span-2" />
            @endif
            <x-ui.input label="Téléphone" type="tel" wire:model="form.phone" placeholder="+243 …" />
            @if ($hasWhatsapp)<x-ui.input label="WhatsApp" type="tel" wire:model="form.whatsapp" placeholder="Si différent du téléphone" />@endif
            <x-ui.input label="E-mail" type="email" wire:model="form.email" />
            <x-ui.input label="Commune / ville" wire:model="form.city" />
            <x-ui.input label="Adresse" wire:model="form.address" class="sm:col-span-2" />
            <x-ui.input label="N° Impôt / ID.NAT" wire:model="form.tax_number" />
            <x-ui.input label="Délai de paiement" type="number" wire:model="form.payment_terms" suffix="jours" />
            <x-ui.input label="Remise négociée" type="number" step="0.5" wire:model="form.discount_rate" suffix="%" />
            <x-ui.input label="Plafond de crédit" type="number" wire:model="form.credit_limit" suffix="USD" />
            <div class="sm:col-span-2"><x-ui.textarea label="Notes" wire:model="form.notes" rows="2" /></div>
            <x-ui.toggle wire:model="form.is_active" label="Actif" />
            @if ($hasOptIn)<x-ui.toggle wire:model="form.marketing_opt_in" label="Accepte les communications" />@endif
        </div>
    </div>
    <x-slot:footer>
        <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
        <x-ui.button variant="primary" icon="check" wire:click="save">{{ $editing ? 'Enregistrer' : 'Créer le contact' }}</x-ui.button>
    </x-slot:footer>
</x-ui.drawer>
