<div>
    @php
        $canCreate = auth()->user()->canManage('contacts');
        $canOpen = auth()->user()->canAccessModule('contacts');
        $typeTones = ['client' => 'brand', 'prospect' => 'info', 'supplier' => 'warning', 'partner' => 'success'];
    @endphp
    <x-ui.page-header title="Annuaire" eyebrow="Relations clients" subtitle="Tous vos contacts par ordre alphabétique : appelez, écrivez sur WhatsApp ou par e-mail en un clic.">
        <x-slot:actions>
            @unless ($internal)
                <a href="{{ route('directory.print', $this->filters()) }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="printer" class="size-[18px]" /> Imprimer</a>
                <a href="{{ route('directory.export', $this->filters()) }}" class="btn btn-secondary"><x-ui.icon name="arrow-down-tray" class="size-[18px]" /> Excel</a>
                @if ($canCreate)<x-ui.button variant="accent" icon="user-plus" wire:click="edit">Créer un contact</x-ui.button>@endif
            @endunless
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card overflow-hidden">
        <div class="px-4"><x-ui.tabs model="view" :current="$view" :tabs="['contacts' => ['label' => 'Contacts', 'icon' => 'users'], 'interne' => ['label' => 'Annuaire interne', 'icon' => 'building-office']]" /></div>

        <div class="flex flex-col gap-2 border-b border-line p-3 xl:flex-row xl:flex-wrap xl:items-center">
            @if ($internal)
                <select wire:model.live="department" class="input h-9 w-auto" aria-label="Service"><option value="">Tous les services</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
            @else
                <select wire:model.live="type" class="input h-9 w-auto" aria-label="Type"><option value="">Tous les types</option>@foreach ($types as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <select wire:model.live="segment" class="input h-9 w-auto" aria-label="Segment"><option value="">Entreprises et particuliers</option><option value="b2b">Entreprises</option><option value="b2c">Particuliers</option></select>
                <select wire:model.live="city" class="input h-9 w-auto" aria-label="Ville"><option value="">Toutes les villes</option>@foreach ($cities as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select>
            @endif
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Nom, téléphone, e-mail…" class="xl:ml-auto" />
        </div>

        {{-- Barre alphabétique --}}
        <nav class="flex flex-wrap gap-1 border-b border-line px-3 py-2" aria-label="Lettres">
            <button type="button" wire:click="$set('letter', '')" @class(['btn btn-sm', 'btn-soft' => $letter === '', 'btn-ghost' => $letter !== ''])>Tous</button>
            @foreach ([...range('A', 'Z'), ...($internal ? [] : ['#'])] as $l)
                @php $n = $l === '#' ? $letters->reject(fn ($c, $k) => preg_match('/^[A-Z]$/', (string) $k))->sum() : ($letters[$l] ?? 0); @endphp
                <button type="button" wire:click="$set('letter', '{{ $l }}')" @disabled(! $n && $letter !== $l) title="{{ $n }} contact(s)"
                        @class(['btn btn-sm btn-icon font-semibold', 'btn-soft' => $letter === $l, 'btn-ghost' => $letter !== $l, 'opacity-35' => ! $n])>{{ $l }}</button>
            @endforeach
        </nav>

        <div class="p-4">
            @if ($internal)
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @forelse ($employees as $e)
                        <article class="rounded-xl border border-line p-4" wire:key="emp-{{ $e->id }}">
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$e->full_name" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $e->full_name }}</p>
                                    <p class="truncate text-[12.5px] text-fg-muted">{{ $e->position?->title ?? '—' }}</p>
                                    <p class="truncate text-[12px] text-fg-subtle">{{ $e->department?->name }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @if ($tel = \App\Support\Phone::tel($e->phone))<a href="{{ $tel }}" class="btn btn-soft btn-sm"><x-ui.icon name="phone" class="size-4" /> {{ $e->phone }}</a>@endif
                                @if ($e->email)<a href="mailto:{{ $e->email }}" class="btn btn-ghost btn-sm" title="{{ $e->email }}"><x-ui.icon name="envelope" class="size-4" /> E-mail</a>@endif
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full"><x-ui.empty icon="users" title="Aucun collaborateur" /></div>
                    @endforelse
                </div>
                @if ($employees->hasPages())<div class="mt-4">{{ $employees->links('components.ui.pagination') }}</div>@endif
            @else
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @forelse ($contacts as $c)
                        @php
                            $person = trim($c->first_name.' '.$c->last_name);
                            $wa = \App\Support\Phone::whatsapp(($hasWhatsapp ? $c->whatsapp : null) ?: $c->phone);
                        @endphp
                        <article class="flex flex-col rounded-xl border border-line p-4" wire:key="dc-{{ $c->id }}">
                            <div class="flex items-start gap-3">
                                <x-ui.avatar :name="$c->display_name" />
                                <div class="min-w-0 flex-1">
                                    @if ($canOpen)
                                        <a href="{{ route('contacts.show', $c) }}" wire:navigate class="block truncate font-semibold hover:text-brand-600">{{ $c->display_name }}</a>
                                    @else
                                        <p class="truncate font-semibold">{{ $c->display_name }}</p>
                                    @endif
                                    @if ($c->company_name && $person)<p class="truncate text-[12.5px] text-fg-muted">{{ $person }}</p>@endif
                                    <p class="mt-1 flex flex-wrap items-center gap-1.5 text-[12px] text-fg-subtle">
                                        <x-ui.badge :tone="$typeTones[$c->type->value] ?? 'neutral'">{{ $c->type->label() }}</x-ui.badge>
                                        {{ $c->segment === 'b2b' ? 'Entreprise' : 'Particulier' }}@if ($c->city) · {{ $c->city }}@endif
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 space-y-1 text-[13px]">
                                @if ($c->phone)<p class="flex items-center gap-2"><x-ui.icon name="phone" class="size-4 text-fg-subtle" />{{ $c->phone }}</p>@endif
                                @if ($c->email)<p class="flex items-center gap-2 truncate"><x-ui.icon name="envelope" class="size-4 text-fg-subtle" /><span class="truncate">{{ $c->email }}</span></p>@endif
                            </div>
                            <div class="mt-auto flex flex-wrap gap-1.5 pt-3">
                                @if ($tel = \App\Support\Phone::tel($c->phone))<a href="{{ $tel }}" class="btn btn-soft btn-sm" title="Appeler"><x-ui.icon name="phone" class="size-4" /> Appeler</a>@endif
                                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="WhatsApp"><x-ui.icon name="chat-bubble-left-right" class="size-4" /> WhatsApp</a>@endif
                                @if ($c->email)<a href="mailto:{{ $c->email }}" class="btn btn-ghost btn-sm btn-icon" title="E-mail"><x-ui.icon name="envelope" class="size-4" /></a>@endif
                                <a href="{{ route('directory.vcard', $c) }}" class="btn btn-ghost btn-sm btn-icon" title="Télécharger la vCard (.vcf)"><x-ui.icon name="identification" class="size-4" /></a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full"><x-ui.empty icon="book-open" title="Aucun contact" text="Aucun contact ne correspond à ces critères." /></div>
                    @endforelse
                </div>
                @if ($contacts->hasPages())<div class="mt-4">{{ $contacts->links('components.ui.pagination') }}</div>@endif
            @endif
        </div>
    </div>

    @if ($canCreate)@include('livewire.crm.partials.contact-form')@endif
</div>
