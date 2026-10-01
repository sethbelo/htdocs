<div>
    <x-ui.page-header title="Pipeline CRM" eyebrow="Relations clients" subtitle="Suivez chaque opportunité de la première demande jusqu’à la signature. Glissez les cartes d’une étape à l’autre.">
        <x-slot:actions>
            <label class="flex items-center gap-2 text-[13px] text-fg-muted"><input type="checkbox" wire:model.live="mine" class="checkbox"> Mes opportunités</label>
            @if (auth()->user()->canManage('crm'))<x-ui.button variant="accent" icon="plus" wire:click="edit">Opportunité</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Opportunités ouvertes" :value="$stats['count']" icon="funnel" />
        <x-ui.stat label="Montant en jeu" :value="money($stats['value'], 'USD', 0)" icon="banknotes" tone="lagoon" />
        <x-ui.stat label="Prévision pondérée" :value="money($stats['weighted'], 'USD', 0)" icon="scale" tone="sun" hint="montant × probabilité" />
        <x-ui.stat label="Gagné" :value="money($stats['won'], 'USD', 0)" icon="trophy" tone="emerald" />
    </div>

    @if ($pipeline)
        <div class="kanban" x-data="kanban('move')">
            @foreach ($pipeline->stages as $stage)
                <section class="kanban-col" wire:key="st-{{ $stage->id }}">
                    <header class="px-2 pt-1 pb-2.5">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[13px] font-semibold"><span class="size-2.5 rounded-full" style="background: {{ $stage->color }}"></span>{{ $stage->name }}</span>
                            <span class="text-[12px] text-fg-subtle">{{ $stage->opportunities->count() }} · {{ $stage->probability }} %</span>
                        </div>
                        <p class="mt-0.5 text-[12px] text-fg-subtle tabular-nums">{{ money($stage->opportunities->sum('amount'), 'USD', 0) }}</p>
                    </header>
                    <div class="flex min-h-32 flex-col gap-2" data-column="{{ $stage->id }}">
                        @foreach ($stage->opportunities as $o)
                            <article class="kanban-card" data-id="{{ $o->id }}" wire:key="op-{{ $o->id }}">
                                <button type="button" wire:click="edit({{ $o->id }})" class="block w-full text-left">
                                    <p class="text-[13.5px] leading-snug font-semibold">{{ $o->title }}</p>
                                    <p class="mt-1 truncate text-[12px] text-fg-muted">{{ $o->contact?->display_name }}</p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="font-display text-[15px] font-semibold tabular-nums">{{ money($o->amount, 'USD', 0) }}</span>
                                        @if ($o->owner)<x-ui.avatar :name="$o->owner->name" size="xs" />@endif
                                    </div>
                                    @if ($o->expected_close_at)<p @class(['mt-1.5 flex items-center gap-1 text-[11.5px]', 'text-flame-600' => $o->expected_close_at->lt(today()) && ! $stage->is_won, 'text-fg-subtle' => ! $o->expected_close_at->lt(today()) || $stage->is_won])><x-ui.icon name="calendar" class="size-3.5" />{{ $o->expected_close_at->format('d/m/Y') }}</p>@endif
                                </button>
                                @if ($o->contact && auth()->user()->canAccessModule('contacts'))
                                    <a href="{{ route('contacts.show', $o->contact) }}" wire:navigate class="mt-1.5 inline-flex items-center gap-1 text-[11.5px] text-fg-subtle hover:text-brand-600"><x-ui.icon name="user" class="size-3.5" /> Fiche contact</a>
                                @endif
                                @if ($stage->is_won && auth()->user()->canManage('quotes'))
                                    <a href="{{ route('quotes.create', ['contact' => \App\Support\RouteKey::encodeOrEmpty(\App\Models\Contact::class, $o->contact_id)]) }}" wire:navigate class="btn btn-soft btn-sm mt-2 w-full"><x-ui.icon name="document-text" class="size-4" /> Créer le devis</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <x-ui.drawer wire:model="showForm" :title="$editing ? 'Opportunité' : 'Nouvelle opportunité'" icon="funnel">
        <div class="space-y-4">
            <x-ui.input label="Intitulé" wire:model="form.title" required placeholder="Ex. : Mobilier de la nouvelle agence" />
            @if ($newContact)
                <div class="space-y-3 rounded-xl border border-line p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[13px] font-semibold">Nouveau prospect</p>
                        <button type="button" class="link text-[12.5px]" wire:click="$set('newContact', false)">Choisir un contact existant</button>
                    </div>
                    <div class="segmented"><button type="button" wire:click="$set('contact.segment', 'b2c')" aria-pressed="{{ ($contact['segment'] ?? '') === 'b2c' ? 'true' : 'false' }}">Particulier</button><button type="button" wire:click="$set('contact.segment', 'b2b')" aria-pressed="{{ ($contact['segment'] ?? '') === 'b2b' ? 'true' : 'false' }}">Entreprise</button></div>
                    @if (($contact['segment'] ?? '') === 'b2b')<x-ui.input label="Entreprise" wire:model="contact.company" required />@endif
                    <x-ui.input label="Nom du contact" wire:model="contact.name" required />
                    <div class="grid grid-cols-2 gap-3"><x-ui.input label="Téléphone" type="tel" wire:model="contact.phone" required /><x-ui.input label="E-mail" type="email" wire:model="contact.email" /></div>
                </div>
            @else
                <x-ui.select label="Client / prospect" wire:model.live="form.contact_id" placeholder="Choisir…" :options="$contacts->mapWithKeys(fn ($c) => [$c->id => $c->display_name.($c->type->value === 'prospect' ? ' (prospect)' : '')])" required />
                <div class="-mt-2 flex items-center justify-between text-[12.5px]">
                    <button type="button" class="link" wire:click="$set('newContact', true)">+ Nouveau prospect</button>
                    @if (! empty($form['contact_id']) && auth()->user()->canAccessModule('contacts') && ($picked = $contacts->firstWhere('id', (int) $form['contact_id'])))
                        <a href="{{ route('contacts.show', $picked) }}" wire:navigate class="link">Voir la fiche</a>
                    @endif
                </div>
            @endif
            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Montant estimé" type="number" step="100" wire:model="form.amount" suffix="USD" />
                <x-ui.input label="Signature prévue" type="date" wire:model="form.expected_close_at" />
            </div>
            <x-ui.select label="Étape" wire:model="form.stage_id" :options="$pipeline?->stages->pluck('name', 'id') ?? []" />
            <x-ui.select label="Commercial" wire:model="form.owner_id" :options="$users->pluck('name', 'id')" />
            <x-ui.textarea label="Notes" wire:model="form.notes" rows="3" />
        </div>
        <x-slot:footer>
            @if ($editing)<x-ui.button variant="ghost" icon="trash" class="mr-auto text-flame-600" wire:click="delete" wire:confirm="Supprimer cette opportunité ?">Supprimer</x-ui.button>@endif
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
