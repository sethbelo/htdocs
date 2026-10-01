<div>
    @php $canManage = auth()->user()->canManage('reception'); @endphp
    <x-ui.page-header title="Accueil du jour" :eyebrow="'Réception · '.ucfirst(now()->translatedFormat('l j F Y'))" subtitle="Visiteurs présents, rendez-vous attendus, courrier à transmettre et demandes des clients.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="megaphone" wire:click="newLead">Demande client</x-ui.button>
                @if (auth()->user()->canManage('appointments'))<x-ui.button variant="secondary" icon="calendar" :href="route('appointments', ['nouveau' => 1])">Nouveau rendez-vous</x-ui.button>@endif
                <x-ui.button variant="secondary" icon="envelope" wire:click="newMail('in')">Enregistrer un courrier</x-ui.button>
                <x-ui.button variant="accent" icon="user-plus" wire:click="newVisitor">Nouveau visiteur</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Visiteurs présents" :value="$present->count()" icon="user-group" tone="sun" :href="route('reception.visits')" />
        <x-ui.stat label="Visites du jour" :value="$todayCount" icon="identification" :href="route('reception.visits')" />
        <x-ui.stat label="Rendez-vous aujourd’hui" :value="$appointments->count()" icon="calendar" tone="lagoon" :hint="$appointments->where('status', 'done')->count().' honorés'" :href="auth()->user()->canAccessModule('appointments') ? route('appointments') : null" />
        <x-ui.stat label="Courrier à transmettre" :value="$pendingMail->count()" icon="envelope" tone="flame" :hint="$mailToday.' enregistré(s) aujourd’hui'" :href="route('reception.mail', ['statut' => 'registered'])" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Visiteurs présents --}}
        <x-ui.card title="Visiteurs dans les locaux" icon="user-group" :padded="false">
            <x-slot:actions><a href="{{ route('reception.visits') }}" wire:navigate class="link text-[13px]">Registre</a></x-slot:actions>
            <ul class="divide-y divide-line">
                @forelse ($present as $v)
                    <li class="flex items-center gap-3 px-4 py-3" wire:key="pv-{{ $v->id }}">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-sun-100 font-display text-[13px] font-bold text-sun-800 dark:bg-sun-400/15 dark:text-sun-300">{{ $v->badge_number ?: '—' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $v->full_name }} @if ($v->organization)<span class="font-normal text-fg-subtle">· {{ $v->organization }}</span>@endif</p>
                            <p class="truncate text-[12.5px] text-fg-muted">{{ $v->purpose }} · {{ $v->host?->name ?? '—' }} · depuis {{ $v->checked_in_at->format('H:i') }}</p>
                        </div>
                        @if ($canManage)<x-ui.button variant="secondary" size="sm" icon="arrow-left-start-on-rectangle" wire:click="checkOut({{ $v->id }})">Sortie</x-ui.button>@endif
                    </li>
                @empty
                    <li><x-ui.empty icon="user-group" title="Aucun visiteur présent" /></li>
                @endforelse
            </ul>
        </x-ui.card>

        {{-- Rendez-vous attendus --}}
        <x-ui.card title="Rendez-vous attendus" icon="calendar" :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($appointments as $a)
                    <li class="flex items-center gap-3 px-4 py-3" wire:key="ap-{{ $a->id }}">
                        <span class="w-12 text-center"><span class="block font-display text-[16px] font-semibold">{{ $a->starts_at->format('H:i') }}</span><span class="text-[11px] text-fg-subtle">{{ $a->ends_at->diffInMinutes($a->starts_at, true) }} min</span></span>
                        <div class="min-w-0 flex-1 border-l border-line pl-3">
                            <p class="truncate font-semibold">{{ $a->whoName() }}</p>
                            <p class="truncate text-[12.5px] text-fg-muted">{{ $a->title }} · avec {{ $a->user?->name }}</p>
                        </div>
                        @if ($canManage && in_array($a->status, ['scheduled', 'confirmed']))
                            <x-ui.button variant="primary" size="sm" icon="arrow-right-end-on-rectangle" wire:click="newVisitor({{ $a->id }})">Arrivé</x-ui.button>
                        @else
                            <x-ui.status map="appointment" :value="$a->status" />
                        @endif
                    </li>
                @empty
                    <li><x-ui.empty icon="calendar" title="Aucun rendez-vous aujourd’hui" /></li>
                @endforelse
            </ul>
        </x-ui.card>

        {{-- Courrier à transmettre --}}
        <x-ui.card title="Courrier à transmettre" icon="inbox-arrow-down" :padded="false">
            <x-slot:actions><a href="{{ route('reception.mail') }}" wire:navigate class="link text-[13px]">Registre</a></x-slot:actions>
            <ul class="divide-y divide-line">
                @forelse ($pendingMail as $m)
                    <li class="flex items-center gap-3 px-4 py-3" wire:key="pm-{{ $m->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $m->subject }}</p>
                            <p class="truncate text-[12.5px] text-fg-muted">{{ $m->reference }} · {{ $m->correspondent }} · {{ $m->record_date->isToday() ? 'aujourd’hui' : $m->record_date->format('d/m') }}{{ $m->department ? ' · '.$m->department->name : '' }}</p>
                        </div>
                        @if ($canManage)<x-ui.button variant="secondary" size="sm" icon="paper-airplane" wire:click="editMail({{ $m->id }})">Transmettre</x-ui.button>@endif
                    </li>
                @empty
                    <li><x-ui.empty icon="envelope" title="Rien à transmettre" /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>

    @include('livewire.reception.partials.visitor-drawer')
    @include('livewire.reception.partials.mail-drawer')

    <x-ui.drawer wire:model="showLead" title="Demande d’un client" subtitle="Transmise automatiquement au pipeline CRM" icon="megaphone">
        <div class="space-y-4">
            <div class="segmented"><button type="button" wire:click="$set('lead.segment', 'b2c')" aria-pressed="{{ ($lead['segment'] ?? '') === 'b2c' ? 'true' : 'false' }}">Particulier</button><button type="button" wire:click="$set('lead.segment', 'b2b')" aria-pressed="{{ ($lead['segment'] ?? '') === 'b2b' ? 'true' : 'false' }}">Entreprise</button></div>
            <x-ui.input label="Nom du contact" wire:model="lead.name" required />
            @if (($lead['segment'] ?? '') === 'b2b')<x-ui.input label="Entreprise" wire:model="lead.company" />@endif
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Téléphone" wire:model="lead.phone" required /><x-ui.input label="E-mail" wire:model="lead.email" /></div>
            <x-ui.input label="Besoin exprimé" wire:model="lead.need" required placeholder="Ex. : Cuisine sur mesure, construction d’une annexe…" />
            <x-ui.input label="Budget indicatif" type="number" wire:model="lead.amount" suffix="USD" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="paper-airplane" wire:click="saveLead">Transmettre</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
