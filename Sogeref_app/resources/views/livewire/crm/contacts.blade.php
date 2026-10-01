<div>
    <x-ui.page-header title="Contacts" eyebrow="Relations clients" subtitle="Entreprises (B2B), particuliers (B2C), prospects, fournisseurs et partenaires.">
        <x-slot:actions>
            @if (auth()->user()->canAccessModule('directory'))<x-ui.button variant="secondary" icon="book-open" :href="route('directory')">Annuaire</x-ui.button>@endif
            @if (auth()->user()->canManage('contacts'))<x-ui.button variant="accent" icon="user-plus" wire:click="edit(null, '{{ $type }}')">Nouveau contact</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line p-3 lg:flex-row lg:items-center">
            <div class="segmented self-start">
                @foreach (['client' => 'Clients', 'prospect' => 'Prospects', 'supplier' => 'Fournisseurs', 'partner' => 'Partenaires'] as $k => $l)
                    <button type="button" wire:click="$set('type', '{{ $k }}')" aria-pressed="{{ $type === $k ? 'true' : 'false' }}">{{ $l }} <span class="text-fg-subtle">{{ $counts[$k] ?? 0 }}</span></button>
                @endforeach
            </div>
            @if (! in_array($type, ['supplier', 'partner'], true))
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
                            <td class="text-[12.5px]"><p>@if ($c->phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $c->phone) }}" class="hover:text-brand-600">{{ $c->phone }}</a>@endif</p><p class="text-fg-subtle">@if ($c->email)<a href="mailto:{{ $c->email }}" class="hover:text-brand-600">{{ $c->email }}</a>@endif</p></td>
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

    @include('livewire.crm.partials.contact-form')
</div>
