<div>
    @php $canManage = auth()->user()->canManage('reception'); @endphp
    <x-ui.page-header title="Réception" :eyebrow="ucfirst(now()->translatedFormat('l j F Y'))" subtitle="Accueil des visiteurs, rendez-vous du jour, courrier et demandes des clients.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="megaphone" wire:click="newLead">Demande client</x-ui.button>
                <x-ui.button variant="secondary" icon="envelope" wire:click="newMail('in')">Courrier</x-ui.button>
                <x-ui.button variant="accent" icon="user-plus" wire:click="newVisitor">Enregistrer un visiteur</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Visiteurs présents" :value="$present->count()" icon="user-group" tone="sun" />
        <x-ui.stat label="Visites du jour" :value="$todayVisitors->count()" icon="identification" />
        <x-ui.stat label="Rendez-vous aujourd’hui" :value="$appointments->count()" icon="calendar" tone="lagoon" :hint="$appointments->where('status', 'done')->count().' honorés'" />
        <x-ui.stat label="Courrier à traiter" :value="$mails->whereIn('status', ['registered', 'forwarded'])->count()" icon="envelope" tone="flame" />
    </div>

    <div class="card">
        <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['accueil' => ['label' => 'Accueil', 'icon' => 'building-office'], 'visiteurs' => ['label' => 'Registre des visites', 'icon' => 'identification'], 'courrier' => ['label' => 'Courrier', 'icon' => 'envelope']]" /></div>

        @if ($tab === 'accueil')
            <div class="grid gap-6 p-5 lg:grid-cols-2">
                <section>
                    <p class="mb-3 font-display text-[16px] font-semibold">Rendez-vous du jour</p>
                    <ul class="space-y-2">
                        @forelse ($appointments as $a)
                            <li class="flex items-center gap-3 rounded-xl border border-line p-3">
                                <span class="w-14 text-center"><span class="block font-display text-[17px] font-semibold">{{ $a->starts_at->format('H:i') }}</span><span class="text-[11px] text-fg-subtle">{{ $a->ends_at->diffInMinutes($a->starts_at) }} min</span></span>
                                <div class="min-w-0 flex-1 border-l border-line pl-3">
                                    <p class="truncate font-semibold">{{ $a->whoName() }}</p>
                                    <p class="truncate text-[12.5px] text-fg-muted">{{ $a->title }} · avec {{ $a->user?->name }}</p>
                                </div>
                                <x-ui.status map="appointment" :value="$a->status" />
                                @if ($canManage && in_array($a->status, ['scheduled', 'confirmed']))
                                    <x-ui.button variant="primary" size="sm" icon="arrow-right-end-on-rectangle" wire:click="newVisitor({{ $a->id }})">Arrivé</x-ui.button>
                                @endif
                            </li>
                        @empty
                            <li><x-ui.empty icon="calendar" title="Aucun rendez-vous aujourd’hui" /></li>
                        @endforelse
                    </ul>
                </section>
                <section>
                    <p class="mb-3 font-display text-[16px] font-semibold">Visiteurs dans les locaux</p>
                    <ul class="space-y-2">
                        @forelse ($present as $v)
                            <li class="flex items-center gap-3 rounded-xl border border-line p-3">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-sun-100 font-display text-[13px] font-bold text-sun-800 dark:bg-sun-400/15 dark:text-sun-300">{{ $v->badge_number }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold">{{ $v->full_name }} @if ($v->organization)<span class="font-normal text-fg-subtle">· {{ $v->organization }}</span>@endif</p>
                                    <p class="truncate text-[12.5px] text-fg-muted">{{ $v->purpose }} · reçu par {{ $v->host?->name ?? '—' }} · depuis {{ $v->checked_in_at->format('H:i') }}</p>
                                </div>
                                @if ($canManage)<x-ui.button variant="secondary" size="sm" icon="arrow-left-start-on-rectangle" wire:click="checkOut({{ $v->id }})">Sortie</x-ui.button>@endif
                            </li>
                        @empty
                            <li><x-ui.empty icon="user-group" title="Aucun visiteur présent" /></li>
                        @endforelse
                    </ul>
                </section>
            </div>

        @elseif ($tab === 'visiteurs')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Badge</th><th>Visiteur</th><th>Objet</th><th>Reçu par</th><th>Arrivée</th><th>Départ</th></tr></thead>
                    <tbody>
                        @forelse ($todayVisitors as $v)
                            <tr>
                                <td><x-ui.badge tone="warning">{{ $v->badge_number }}</x-ui.badge></td>
                                <td><p class="font-semibold">{{ $v->full_name }}</p><p class="text-[12px] text-fg-subtle">{{ $v->organization }} {{ $v->phone }}</p></td>
                                <td class="max-w-[260px] truncate">{{ $v->purpose }}</td>
                                <td class="text-fg-muted">{{ $v->host?->name }}</td>
                                <td>{{ $v->checked_in_at->format('H:i') }}</td>
                                <td>{{ $v->checked_out_at?->format('H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-ui.empty icon="identification" title="Aucune visite aujourd’hui" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            @if ($canManage)
                <div class="flex gap-2 border-b border-line p-3">
                    <x-ui.button variant="soft" size="sm" icon="inbox-arrow-down" wire:click="newMail('in')">Courrier entrant</x-ui.button>
                    <x-ui.button variant="soft" size="sm" icon="paper-airplane" wire:click="newMail('out')">Courrier sortant</x-ui.button>
                </div>
            @endif
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Référence</th><th>Date</th><th>Correspondant</th><th>Objet</th><th>Service</th><th>Statut</th></tr></thead>
                    <tbody>
                        @forelse ($mails as $m)
                            <tr>
                                <td><span class="flex items-center gap-1.5 font-semibold"><x-ui.icon :name="$m->direction === 'in' ? 'inbox-arrow-down' : 'paper-airplane'" class="size-4 text-fg-subtle" />{{ $m->reference }}</span></td>
                                <td class="text-fg-muted">{{ $m->record_date->format('d/m/Y') }}</td>
                                <td>{{ $m->correspondent }}</td>
                                <td class="max-w-[280px] truncate">{{ $m->subject }}</td>
                                <td class="text-fg-muted">{{ $m->department?->name }} {{ $m->handler ? '· '.$m->handler->name : '' }}</td>
                                <td>
                                    @if ($canManage)
                                        <select wire:change="mailStatus({{ $m->id }}, $event.target.value)" class="input h-8 w-36 text-[12.5px]">@foreach (config('erp.statuses.mail') as $k => [$l])<option value="{{ $k }}" @selected($m->status === $k)>{{ $l }}</option>@endforeach</select>
                                    @else
                                        <x-ui.status map="mail" :value="$m->status" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-ui.empty icon="envelope" title="Registre vide" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-ui.drawer wire:model="showVisitor" title="Enregistrer un visiteur" icon="user-plus">
        <div class="space-y-4">
            <x-ui.input label="Nom complet" wire:model="visitor.full_name" required />
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Organisation" wire:model="visitor.organization" /><x-ui.input label="Téléphone" wire:model="visitor.phone" /></div>
            <x-ui.input label="Pièce d’identité" wire:model="visitor.id_document" placeholder="Carte d’électeur, passeport…" />
            <x-ui.input label="Objet de la visite" wire:model="visitor.purpose" required />
            <x-ui.select label="Personne visitée" wire:model="visitor.host_id" placeholder="—" :options="$hosts->pluck('name', 'id')" />
            <x-ui.input label="N° de badge" wire:model="visitor.badge_number" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="checkIn">Enregistrer l’arrivée</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showMail" :title="($mail['direction'] ?? 'in') === 'in' ? 'Courrier entrant' : 'Courrier sortant'" icon="envelope">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Date" type="date" wire:model="mail.record_date" /><x-ui.select label="Canal" wire:model="mail.channel" :options="['physique' => 'Dépôt physique', 'email' => 'E-mail', 'coursier' => 'Coursier']" /></div>
            <x-ui.input :label="($mail['direction'] ?? 'in') === 'in' ? 'Expéditeur' : 'Destinataire'" wire:model="mail.correspondent" required />
            <x-ui.input label="Objet" wire:model="mail.subject" required />
            <x-ui.select label="Service destinataire" wire:model="mail.department_id" placeholder="—" :options="$departments->pluck('name', 'id')" />
            <x-ui.select label="Transmis à" wire:model="mail.handled_by" placeholder="—" :options="$hosts->pluck('name', 'id')" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveMail">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

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
