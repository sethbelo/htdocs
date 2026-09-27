<div>
    @php $canManage = auth()->user()->canManage('loyalty'); @endphp
    <x-ui.page-header title="Fidélité" eyebrow="Relation client" subtitle="Programmes B2C et B2B : points cumulés à chaque achat (devis encaissés et point de vente), paliers et remises automatiques.">
        <x-slot:actions>@if ($canManage)<x-ui.button variant="accent" icon="plus" wire:click="editProgram">Programme</x-ui.button>@endif</x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 md:grid-cols-2">
        @foreach ($programs as $p)
            <x-ui.card :title="$p->name" :subtitle="['all' => 'Tous clients', 'b2b' => 'Entreprises', 'b2c' => 'Particuliers'][$p->segment].($p->is_active ? '' : ' · inactif')" icon="gift">
                <x-slot:actions>@if ($canManage)<x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="editProgram({{ $p->id }})" />@endif</x-slot:actions>
                <div class="grid grid-cols-3 gap-3 text-[12.5px]">
                    <div><p class="text-fg-subtle">Cumul</p><p class="font-semibold">{{ qty($p->points_per_unit) }} pt / 1 $</p></div>
                    <div><p class="text-fg-subtle">Valeur d’un point</p><p class="font-semibold">{{ money($p->point_value, 'USD', 2) }}</p></div>
                    <div><p class="text-fg-subtle">Cartes · points</p><p class="font-semibold">{{ $p->cards_count }} · {{ number_format($p->cards_sum_points ?? 0, 0, ',', ' ') }}</p></div>
                </div>
                <div class="mt-4 flex gap-2">
                    @foreach ($p->tiers ?? [] as $tier)
                        <div class="flex-1 rounded-xl border border-line p-3 text-center"><p class="font-display font-semibold">{{ $tier['name'] }}</p><p class="text-[11.5px] text-fg-subtle">dès {{ money($tier['threshold'], 'USD', 0) }}</p><p class="mt-1 text-[13px] font-semibold text-emerald-600">{{ (float) $tier['discount'] ? '−'.qty($tier['discount']).' %' : '—' }}</p></div>
                    @endforeach
                </div>
            </x-ui.card>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between gap-2 border-b border-line p-3"><p class="font-display text-[15px] font-semibold">Cartes de fidélité</p><x-ui.search wire:model.live.debounce.300ms="search" placeholder="N° de carte ou client…" /></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Carte</th><th>Client</th><th>Programme</th><th>Palier</th><th class="num">Points</th><th class="num">Cumul d’achats</th><th></th></tr></thead>
                <tbody>
                    @forelse ($cards as $card)
                        <tr>
                            <td class="font-semibold tracking-wide">{{ $card->card_number }}</td>
                            <td><a href="{{ route('contacts.show', $card->contact) }}" wire:navigate class="hover:text-brand-600">{{ $card->contact->display_name }}</a></td>
                            <td class="text-fg-muted">{{ $card->program->name }}</td>
                            <td><x-ui.badge tone="warning">{{ $card->tier }}</x-ui.badge></td>
                            <td class="num font-semibold">{{ number_format($card->points, 0, ',', ' ') }}</td>
                            <td class="num">{{ money($card->total_spent, 'USD', 0) }}</td>
                            <td class="text-right">@if ($canManage)<x-ui.button variant="ghost" size="sm" icon="adjustments-horizontal" wire:click="$set('adjustCard', {{ $card->id }})">Ajuster</x-ui.button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty icon="gift" title="Aucune carte émise" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cards->hasPages())<div class="border-t border-line px-4 py-3">{{ $cards->links('components.ui.pagination') }}</div>@endif
    </div>

    <x-ui.drawer wire:model="showProgram" :title="$editing ? 'Modifier le programme' : 'Nouveau programme'" icon="gift" size="lg">
        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2"><x-ui.input label="Nom" wire:model="program.name" required /><x-ui.select label="Cible" wire:model="program.segment" :options="['b2c' => 'Particuliers (B2C)', 'b2b' => 'Entreprises (B2B)', 'all' => 'Tous les clients']" /></div>
            <div class="grid gap-3 sm:grid-cols-3">
                <x-ui.input label="Points pour 1 $" type="number" step="0.1" wire:model="program.points_per_unit" />
                <x-ui.input label="Valeur d’un point" type="number" step="0.01" wire:model="program.point_value" suffix="USD" />
                <x-ui.input label="Minimum à convertir" type="number" wire:model="program.min_redeem_points" suffix="pts" />
            </div>
            <div>
                <p class="label">Paliers (seuil de cumul d’achats → remise automatique)</p>
                @foreach ($program['tiers'] ?? [] as $i => $tier)
                    <div class="mb-2 flex gap-2" wire:key="tier-{{ $i }}">
                        <input wire:model="program.tiers.{{ $i }}.name" class="input" placeholder="Nom">
                        <input type="number" wire:model="program.tiers.{{ $i }}.threshold" class="input w-32" placeholder="Seuil $">
                        <input type="number" step="0.5" wire:model="program.tiers.{{ $i }}.discount" class="input w-24" placeholder="%">
                        <button type="button" wire:click="removeTier({{ $i }})" class="btn btn-ghost btn-icon text-fg-subtle"><x-ui.icon name="x-mark" /></button>
                    </div>
                @endforeach
                <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addTier">Ajouter un palier</x-ui.button>
            </div>
            <x-ui.toggle wire:model="program.is_active" label="Programme actif" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveProgram">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    @if ($adjustCard)
        <div class="fixed inset-0 z-50 grid place-items-center p-4">
            <div class="overlay" wire:click="$set('adjustCard', null)"></div>
            <div class="modal max-w-sm p-5">
                <p class="font-display text-[17px] font-semibold">Ajuster les points</p>
                <div class="mt-4 space-y-3">
                    <x-ui.input label="Points (+ ou −)" type="number" wire:model="adjustPoints" />
                    <x-ui.input label="Motif" wire:model="adjustNote" placeholder="Geste commercial, correction…" />
                </div>
                <div class="mt-5 flex justify-end gap-2"><x-ui.button variant="ghost" wire:click="$set('adjustCard', null)">Annuler</x-ui.button><x-ui.button variant="primary" wire:click="adjust">Valider</x-ui.button></div>
            </div>
        </div>
    @endif
</div>
