<div>
    @php $c = $contact; @endphp
    <x-ui.page-header :title="$c->display_name" :eyebrow="$c->reference.' · '.['client' => 'Client', 'supplier' => 'Fournisseur', 'prospect' => 'Prospect'][$c->type->value].' '.strtoupper($c->segment)" :back="route('contacts', ['type' => $c->type->value])">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[13px] text-fg-muted">
                @if ($c->phone)<span class="flex items-center gap-1"><x-ui.icon name="phone" class="size-4" />{{ $c->phone }}</span>@endif
                @if ($c->email)<span class="flex items-center gap-1"><x-ui.icon name="envelope" class="size-4" />{{ $c->email }}</span>@endif
                @if ($c->city)<span class="flex items-center gap-1"><x-ui.icon name="map-pin" class="size-4" />{{ $c->city }}</span>@endif
                @if ($c->owner)<span class="flex items-center gap-1"><x-ui.icon name="user" class="size-4" />Suivi par {{ $c->owner->name }}</span>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            @if ($c->type->value === 'prospect' && auth()->user()->canManage('contacts'))<x-ui.button variant="secondary" icon="check-badge" wire:click="convertToClient">Convertir en client</x-ui.button>@endif
            @if ($c->type->value !== 'supplier' && auth()->user()->canManage('quotes'))<x-ui.button variant="accent" icon="document-text" :href="route('quotes.create', ['contact' => $c])">Nouveau devis</x-ui.button>@endif
            @if ($c->type->value === 'supplier' && auth()->user()->canManage('purchases'))<x-ui.button variant="accent" icon="truck" :href="route('purchases.create', ['contact' => $c])">Bon de commande</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Facturé" :value="money($stats['invoiced'], 'USD', 0)" icon="document-currency-dollar" />
        <x-ui.stat label="Encaissé" :value="money($stats['paid'], 'USD', 0)" icon="banknotes" tone="emerald" />
        <x-ui.stat label="Reste dû" :value="money($stats['due'], 'USD', 0)" icon="clock" :tone="$stats['due'] > 0 ? 'flame' : 'emerald'" />
        <x-ui.stat label="Dossiers de commande" :value="$stats['orders']" icon="arrow-path-rounded-square" tone="sun" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Dossiers de commande" icon="arrow-path-rounded-square" :padded="false">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Commande</th><th>Nature</th><th>Étape</th><th class="num">Montant</th><th class="num">Encaissé</th></tr></thead>
                        <tbody>
                            @forelse ($orders as $o)
                                <tr><td><a href="{{ route('orders.show', $o) }}" wire:navigate class="link">{{ $o->number }}</a><p class="max-w-[260px] truncate text-[12px] text-fg-subtle">{{ $o->title }}</p></td><td class="text-fg-muted">{{ config('erp.order_kinds.'.$o->kind) }}</td><td><x-ui.status map="order_stage" :value="$o->stage" /></td><td class="num font-semibold">{{ money($o->total) }}</td><td class="num">{{ round($o->paidRatio() * 100) }} %</td></tr>
                            @empty
                                <tr><td colspan="5"><x-ui.empty icon="arrow-path-rounded-square" title="Aucun dossier" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <x-ui.card title="Documents" icon="document-text" :padded="false">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>N°</th><th>Type</th><th>Date</th><th>Statut</th><th class="num">Total</th><th class="num">Reste dû</th></tr></thead>
                        <tbody>
                            @forelse ($documents as $d)
                                <tr><td><a href="{{ route('documents.show', $d) }}" wire:navigate class="link">{{ $d->number }}</a></td><td class="text-fg-muted">{{ $d->type->label() }}</td><td class="text-fg-muted">{{ $d->issue_date->format('d/m/Y') }}</td><td><x-ui.status map="document" :value="$d->status" /></td><td class="num">{{ money($d->total) }}</td><td class="num {{ $d->type->value === 'invoice' && $d->balance_due > 0 ? 'font-semibold text-flame-600' : 'text-fg-subtle' }}">{{ $d->type->value === 'invoice' ? money($d->balance_due) : '—' }}</td></tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="document-text" title="Aucun document" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
        <aside class="space-y-6">
            @if ($c->type->value !== 'supplier')
                <x-ui.card title="Fidélité" icon="gift">
                    @if ($card = $c->loyaltyCard)
                        <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-5 text-white">
                            <div class="flex items-start justify-between"><x-ui.mark class="size-8" /><span class="rounded-full bg-sun-400 px-2.5 py-0.5 text-[11px] font-bold text-brand-900">{{ $card->tier }}</span></div>
                            <p class="mt-6 font-display text-[28px] font-semibold">{{ number_format($card->points, 0, ',', ' ') }} <span class="text-[14px] font-normal text-white/70">points</span></p>
                            <p class="mt-1 text-[12px] tracking-widest text-white/70">{{ $card->card_number }}</p>
                            <p class="mt-3 text-[11.5px] text-white/70">{{ $card->program->name }} · cumul {{ money($card->total_spent, 'USD', 0) }}</p>
                        </div>
                        <ul class="mt-4 space-y-2 text-[12.5px]">
                            @foreach ($card->transactions->take(5) as $t)<li class="flex justify-between"><span class="text-fg-muted">{{ $t->created_at->format('d/m/Y') }} · {{ $t->note }}</span><span @class(['font-semibold', 'text-emerald-600' => $t->points > 0, 'text-flame-600' => $t->points < 0])>{{ $t->points > 0 ? '+' : '' }}{{ $t->points }}</span></li>@endforeach
                        </ul>
                    @else
                        <x-ui.empty icon="gift" title="Pas encore de carte" text="Émettez une carte pour cumuler des points à chaque achat.">
                            @if (auth()->user()->canManage('loyalty'))<x-ui.button variant="soft" size="sm" icon="plus" wire:click="issueCard">Émettre une carte</x-ui.button>@endif
                        </x-ui.empty>
                    @endif
                </x-ui.card>
            @endif
            <x-ui.card title="Conditions commerciales" icon="document-check">
                <dl class="space-y-2 text-[13px]">
                    <div class="flex justify-between"><dt class="text-fg-muted">Paiement</dt><dd class="font-medium">{{ $c->payment_terms ? $c->payment_terms.' jours' : 'Comptant' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-muted">Remise négociée</dt><dd class="font-medium">{{ qty($c->discount_rate) }} %</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-muted">Plafond de crédit</dt><dd class="font-medium">{{ money($c->credit_limit, 'USD', 0) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-muted">N° Impôt</dt><dd class="font-medium">{{ $c->tax_number ?? '—' }}</dd></div>
                </dl>
                @if ($c->notes)<p class="mt-3 border-t border-line pt-3 text-[13px] text-fg-muted">{{ $c->notes }}</p>@endif
            </x-ui.card>
            @if ($opportunities->isNotEmpty())
                <x-ui.card title="Opportunités" icon="funnel" :padded="false">
                    <ul>@foreach ($opportunities as $o)<li class="flex items-center justify-between gap-2 border-b border-line px-5 py-3 text-[13px] last:border-0"><span class="truncate">{{ $o->title }}</span><span class="shrink-0 font-semibold">{{ money($o->amount, 'USD', 0) }}</span></li>@endforeach</ul>
                </x-ui.card>
            @endif
            @if ($appointments->isNotEmpty())
                <x-ui.card title="Rendez-vous" icon="calendar" :padded="false">
                    <ul>@foreach ($appointments as $a)<li class="border-b border-line px-5 py-3 text-[13px] last:border-0"><p class="font-medium">{{ $a->title }}</p><p class="text-[12px] text-fg-subtle">{{ $a->starts_at->translatedFormat('j M Y · H:i') }} · {{ $a->user?->name }}</p></li>@endforeach</ul>
                </x-ui.card>
            @endif
        </aside>
    </div>
</div>
