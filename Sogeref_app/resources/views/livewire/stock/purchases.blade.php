<div>
    <x-ui.page-header title="Achats & réapprovisionnement" eyebrow="Stock & achats" subtitle="Le système propose les commandes dès qu’un article passe sous son seuil ; vous validez, puis réceptionnez la marchandise.">
        <x-slot:actions>
            @if (auth()->user()->canManage('purchases'))<x-ui.button variant="accent" icon="plus" :href="route('purchases.create')">Bon de commande</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card">
        <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['reappro' => ['label' => 'Réapprovisionnement automatique', 'icon' => 'bolt'], 'commandes' => ['label' => 'Bons de commande', 'icon' => 'truck', 'count' => $openCount]]" /></div>

        @if ($tab === 'reappro')
            <div class="flex flex-col gap-3 border-b border-line p-4 md:flex-row md:items-center">
                <div class="flex-1">
                    <p class="font-semibold">{{ $proposals->count() }} article(s) sous le seuil</p>
                    <p class="text-[13px] text-fg-subtle">Quantité proposée = max(quantité de réappro, 2 × seuil − stock) − commandes déjà en cours. Regroupement par fournisseur habituel.</p>
                </div>
                @if ($proposals->isNotEmpty() && auth()->user()->canManage('purchases'))
                    <p class="text-[13px] text-fg-muted">Montant estimé : <strong class="text-fg">{{ money($proposalTotal) }}</strong></p>
                    <x-ui.button variant="primary" icon="bolt" wire:click="generate">{{ $selected ? 'Commander la sélection ('.count($selected).')' : 'Générer toutes les commandes' }}</x-ui.button>
                @endif
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th class="w-10"></th><th>Article</th><th>Fournisseur</th><th class="num">Stock</th><th class="num">Seuil</th><th class="num">En commande</th><th class="num">À commander</th><th class="num">Montant</th></tr></thead>
                    <tbody>
                        @forelse ($proposals as $p)
                            <tr>
                                <td><input type="checkbox" value="{{ $p->id }}" wire:model.live="selected" class="checkbox"></td>
                                <td><p class="font-semibold">{{ $p->name }}</p><p class="text-[12px] text-fg-subtle">{{ $p->sku }} · délai {{ $p->lead_time_days }} j</p></td>
                                <td class="text-fg-muted">{{ $p->supplier?->company_name ?? 'Non défini' }}</td>
                                <td class="num font-semibold text-flame-600">{{ qty($p->stock_quantity) }} {{ $p->unit }}</td>
                                <td class="num text-fg-muted">{{ qty($p->reorder_level) }}</td>
                                <td class="num text-fg-muted">{{ qty($p->incoming) }}</td>
                                <td class="num font-semibold">{{ qty($p->suggested) }} {{ $p->unit }}</td>
                                <td class="num">{{ money($p->suggested * (float) $p->purchase_price) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-ui.empty icon="check-circle" title="Aucun besoin de réapprovisionnement" text="Tous les articles suivis sont au-dessus de leur seuil." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="border-b border-line p-3"><x-ui.search wire:model.live.debounce.300ms="search" placeholder="N° ou fournisseur…" /></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>N°</th><th>Fournisseur</th><th>Date</th><th>Livraison prévue</th><th>Statut</th><th>Réception</th><th class="num">Montant</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($orders as $o)
                            @php
                                $ordered = $o->items->sum('quantity');
                                $received = $o->items->sum('received_quantity');
                            @endphp
                            <tr>
                                <td><a href="{{ route('documents.show', $o) }}" wire:navigate class="link">{{ $o->number }}</a>@if (data_get($o->meta, 'auto_replenishment'))<p class="text-[11.5px] text-lagoon-600">Réappro auto</p>@endif</td>
                                <td class="max-w-[220px] truncate">{{ $o->contact->display_name }}</td>
                                <td class="text-fg-muted">{{ $o->issue_date->format('d/m/Y') }}</td>
                                <td class="text-fg-muted">{{ $o->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td><x-ui.status map="document" :value="$o->status" /></td>
                                <td class="w-40"><x-ui.progress :value="$ordered ? round($received / $ordered * 100) : 0" tone="lagoon" /></td>
                                <td class="num font-semibold">{{ money($o->total, $o->currency_code) }}</td>
                                <td class="text-right"><x-ui.button variant="ghost" size="sm" square icon="chevron-right" :href="route('documents.show', $o)" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-ui.empty icon="truck" title="Aucun bon de commande" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="border-t border-line px-4 py-3">{{ $orders->links('components.ui.pagination') }}</div>@endif
        @endif
    </div>
</div>
