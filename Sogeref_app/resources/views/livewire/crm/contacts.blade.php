<div>
    <x-ui.page-header title="Clients & fournisseurs" eyebrow="Relation client" subtitle="Entreprises (B2B), particuliers (B2C), prospects et fournisseurs.">
        <x-slot:actions>@if (auth()->user()->canManage('contacts'))<x-ui.button variant="accent" icon="plus" wire:click="edit">Nouveau contact</x-ui.button>@endif</x-slot:actions>
    </x-ui.page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line p-3 lg:flex-row lg:items-center">
            <div class="segmented self-start">
                @foreach (['client' => 'Clients', 'prospect' => 'Prospects', 'supplier' => 'Fournisseurs'] as $k => $l)
                    <button type="button" wire:click="$set('type', '{{ $k }}')" aria-pressed="{{ $type === $k ? 'true' : 'false' }}">{{ $l }} <span class="text-fg-subtle">{{ $counts[$k] ?? 0 }}</span></button>
                @endforeach
            </div>
            @if ($type !== 'supplier')
                <div class="segmented self-start">
                    <button type="button" wire:click="$set('segment', '')" aria-pressed="{{ $segment === '' ? 'true' : 'false' }}">Tous</button>
                    <button type="button" wire:click="$set('segment', 'b2b')" aria-pressed="{{ $segment === 'b2b' ? 'true' : 'false' }}">Entreprises</button>
                    <button type="button" wire:click="$set('segment', 'b2c')" aria-pressed="{{ $segment === 'b2c' ? 'true' : 'false' }}">Particuliers</button>
                </div>
            @endif
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Nom, référence, téléphone…" class="lg:ml-auto" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Contact</th><th>Segment</th><th>Coordonnées</th><th class="num">Commandes</th><th class="num">Facturé</th><th>Fidélité</th><th></th></tr></thead>
                <tbody>
                    @forelse ($contacts as $c)
                        <tr wire:key="ct-{{ $c->id }}">
                            <td><a href="{{ route('contacts.show', $c) }}" wire:navigate class="flex items-center gap-3"><x-ui.avatar :name="$c->display_name" size="sm" /><div class="min-w-0"><p class="truncate font-semibold hover:text-brand-600">{{ $c->display_name }}</p><p class="text-[12px] text-fg-subtle">{{ $c->reference }}@unless ($c->is_active) · inactif @endunless</p></div></a></td>
                            <td><x-ui.badge :tone="$c->segment === 'b2b' ? 'brand' : 'info'">{{ $c->segment === 'b2b' ? 'Entreprise' : 'Particulier' }}</x-ui.badge></td>
                            <td class="text-[12.5px]"><p>{{ $c->phone }}</p><p class="text-fg-subtle">{{ $c->email }}</p></td>
                            <td class="num">{{ $c->sales_orders_count }}</td>
                            <td class="num font-semibold">{{ money($c->invoiced ?? 0, 'USD', 0) }}</td>
                            <td>@if ($c->loyaltyCard)<x-ui.badge tone="warning" icon="gift">{{ $c->loyaltyCard->tier }} · {{ $c->loyaltyCard->points }} pts</x-ui.badge>@endif</td>
                            <td class="text-right whitespace-nowrap">
                                @if (auth()->user()->canManage('contacts'))<x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="edit({{ $c->id }})" />@endif
                                <x-ui.button variant="ghost" size="sm" square icon="chevron-right" :href="route('contacts.show', $c)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty icon="users" title="Aucun contact" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contacts->hasPages())<div class="border-t border-line px-4 py-3">{{ $contacts->links('components.ui.pagination') }}</div>@endif
    </div>

    <x-ui.drawer wire:model="showForm" :title="$editing ? 'Modifier le contact' : 'Nouveau contact'" icon="user" size="lg">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.select label="Type" wire:model.live="form.type" :options="['client' => 'Client', 'prospect' => 'Prospect', 'supplier' => 'Fournisseur']" />
            <x-ui.select label="Segment" wire:model.live="form.segment" :options="['b2b' => 'Entreprise (B2B)', 'b2c' => 'Particulier (B2C)']" />
            <x-ui.input label="Raison sociale" wire:model="form.company_name" class="sm:col-span-2" />
            <x-ui.input label="Prénom" wire:model="form.first_name" />
            <x-ui.input label="Nom" wire:model="form.last_name" />
            <x-ui.input label="Téléphone" wire:model="form.phone" />
            <x-ui.input label="E-mail" type="email" wire:model="form.email" />
            <x-ui.input label="Adresse" wire:model="form.address" />
            <x-ui.input label="Commune / ville" wire:model="form.city" />
            <x-ui.input label="N° Impôt / ID.NAT" wire:model="form.tax_number" />
            <x-ui.input label="Délai de paiement" type="number" wire:model="form.payment_terms" suffix="jours" />
            <x-ui.input label="Remise négociée" type="number" step="0.5" wire:model="form.discount_rate" suffix="%" />
            <x-ui.input label="Plafond de crédit" type="number" wire:model="form.credit_limit" suffix="USD" />
            <div class="sm:col-span-2"><x-ui.textarea label="Notes" wire:model="form.notes" rows="2" /></div>
            <x-ui.toggle wire:model="form.is_active" label="Actif" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
