<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header title="Caisse" eyebrow="Finance" subtitle="Encaissements des factures, décaissements approuvés et contrôle de fin de journée.">
        <x-slot:actions>
            @if ($session)
                <x-ui.badge tone="success" dot class="h-8 px-3">Session {{ $session->reference }} · ouverte à {{ $session->opened_at->format('H:i') }}</x-ui.badge>
                <x-ui.button variant="secondary" icon="lock-closed" wire:click="prepareClose">Clôturer la caisse</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($lastPayment)
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13.5px] text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200">
            <x-ui.icon name="check-circle" class="size-5" />
            <span class="flex-1">Encaissement {{ $lastPayment->reference }} de <strong>{{ money($lastPayment->amount, $lastPayment->currency_code) }}</strong> enregistré.</span>
            <a href="{{ route('pdf.receipt', $lastPayment) }}" target="_blank" class="btn btn-primary btn-sm"><x-ui.icon name="printer" class="size-4" />Imprimer le reçu</a>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="$set('lastPaymentId', null)" aria-label="Masquer"><x-ui.icon name="x-mark" class="size-4" /></button>
        </div>
    @endif

    @if (! $session)
        {{-- ═══ 2A. OUVERTURE DE SESSION ═════════════════════════════════════ --}}
        <div class="grid gap-6 lg:grid-cols-[420px_1fr]">
            <x-ui.card title="Ouvrir ma caisse" icon="lock-open">
                <p class="mb-4 text-[13.5px] text-fg-muted">Comptez le fonds de caisse présent dans le tiroir avant de commencer les encaissements.</p>
                <div class="space-y-4">
                    <x-ui.select label="Caisse" wire:model="accountId" :options="$cashAccounts->pluck('name', 'id')" />
                    <x-ui.input label="Fonds de caisse initial" type="number" step="0.01" wire:model="openingAmount" suffix="USD" />
                    <x-ui.button variant="primary" icon="lock-open" class="w-full" wire:click="open">Ouvrir la session</x-ui.button>
                </div>
            </x-ui.card>
            <x-ui.card title="Dernières clôtures" icon="clock" :padded="false">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Session</th><th>Caissier</th><th>Caisse</th><th>Clôture</th><th class="num">Théorique</th><th class="num">Compté</th><th class="num">Écart</th></tr></thead>
                        <tbody>
                            @forelse ($history as $h)
                                @php $gap = (float) $h->closing_amount - (float) $h->expected_amount; @endphp
                                <tr>
                                    <td class="font-semibold">{{ $h->reference }}</td><td>{{ $h->user->name }}</td><td class="text-fg-muted">{{ $h->account->name }}</td>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $h->closed_at->format('d/m/Y H:i') }}</td>
                                    <td class="num">{{ money($h->expected_amount) }}</td><td class="num">{{ money($h->closing_amount) }}</td>
                                    <td class="num font-semibold {{ $gap == 0 ? 'text-emerald-600' : 'text-flame-600' }}">{{ $gap == 0 ? '✓' : money($gap) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-ui.empty icon="banknotes" title="Aucune session clôturée" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    @else
        {{-- ═══ 2B. SESSION OUVERTE ══════════════════════════════════════════ --}}
        @php
            $in = $movements->where('amount', '>', 0)->sum('amount');
            $out = abs($movements->where('amount', '<', 0)->sum('amount'));
        @endphp
        <div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
            <x-ui.stat label="Fonds initial" :value="money($session->opening_amount)" icon="inbox" />
            <x-ui.stat label="Entrées de la session" :value="money($in)" icon="arrow-down-tray" tone="emerald" :hint="$movements->where('amount', '>', 0)->count().' opération(s)'" />
            <x-ui.stat label="Sorties de la session" :value="money($out)" icon="arrow-up-tray" tone="flame" :hint="$movements->where('amount', '<', 0)->count().' opération(s)'" />
            <x-ui.stat label="Solde théorique" :value="money($expected)" icon="calculator" tone="sun" :hint="$session->account->name" />
        </div>

        <div class="card">
            <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['encaisser' => ['label' => 'À encaisser', 'icon' => 'banknotes', 'count' => $invoices->count()], 'decaisser' => ['label' => 'Décaissements approuvés', 'icon' => 'arrow-up-tray', 'count' => $disbursements->count()], 'mouvements' => ['label' => 'Mouvements de la session', 'icon' => 'queue-list']]" /></div>

            @if ($tab === 'encaisser')
                <div class="border-b border-line p-3"><x-ui.search wire:model.live.debounce.300ms="search" placeholder="Facture ou client…" /></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Facture</th><th>Client</th><th>Dossier</th><th>Échéance</th><th class="num">Total</th><th class="num">Reste dû</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($invoices as $inv)
                                @php $o = $orders->get($inv->id); @endphp
                                <tr>
                                    <td><a href="{{ route('documents.show', $inv) }}" wire:navigate class="link">{{ $inv->number }}</a></td>
                                    <td class="max-w-[220px] truncate">{{ $inv->contact->display_name }}</td>
                                    <td>@if ($o)<a href="{{ route('orders.show', $o) }}" wire:navigate class="text-[12.5px] font-medium text-fg-muted hover:text-fg">{{ $o->number }}</a> <x-ui.status map="order_stage" :value="$o->stage" />@else<span class="text-fg-subtle">—</span>@endif</td>
                                    <td @class(['whitespace-nowrap', 'font-semibold text-flame-600' => $inv->due_date?->lt(today()), 'text-fg-muted' => ! $inv->due_date?->lt(today())])>{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="num">{{ money($inv->total, $inv->currency_code) }}</td>
                                    <td class="num font-semibold">{{ money($inv->balance_due, $inv->currency_code) }}</td>
                                    <td class="text-right"><x-ui.button variant="primary" size="sm" icon="banknotes" wire:click="startCollect({{ $inv->id }})">Encaisser</x-ui.button></td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-ui.empty icon="check-circle" title="Aucune facture à encaisser" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($tab === 'decaisser')
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Bon</th><th>Bénéficiaire</th><th>Objet</th><th>Chantier</th><th class="num">Montant</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($disbursements as $v)
                                <tr>
                                    <td><a href="{{ route('pdf.voucher', $v) }}" target="_blank" class="link">{{ $v->number }}</a></td>
                                    <td>{{ $v->beneficiaryName() }}</td>
                                    <td class="max-w-[280px] truncate text-fg-muted">{{ $v->purpose }}</td>
                                    <td class="text-fg-muted">{{ $v->site?->code ?? '—' }}</td>
                                    <td class="num font-semibold">{{ money($v->amount) }}</td>
                                    <td class="text-right">
                                        @if (auth()->user()->isMaster() || auth()->user()->can('vouchers.execute'))
                                            <x-ui.button variant="danger" size="sm" icon="arrow-up-tray" wire:click="disburse({{ $v->id }})" wire:confirm="Remettre {{ money($v->amount) }} en espèces à {{ $v->beneficiaryName() }} ?">Décaisser</x-ui.button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="ticket" title="Aucun bon de décaissement approuvé" text="Les bons approuvés par la direction apparaissent ici pour paiement." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Heure</th><th>Référence</th><th>Libellé</th><th class="num">Entrée</th><th class="num">Sortie</th></tr></thead>
                        <tbody>
                            @forelse ($movements as $m)
                                <tr>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $m->created_at->format('H:i') }}</td>
                                    <td class="font-medium">{{ $m->reference }}</td>
                                    <td class="max-w-[360px] truncate">{{ $m->description }}</td>
                                    <td class="num font-semibold text-emerald-600">{{ $m->amount > 0 ? money($m->amount) : '' }}</td>
                                    <td class="num font-semibold text-flame-600">{{ $m->amount < 0 ? money(abs($m->amount)) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-ui.empty icon="queue-list" title="Aucun mouvement pour l’instant" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══ 3. FENÊTRES ══════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showCollect" title="Encaisser une facture" :subtitle="$invoice ? $invoice->number.' · '.$invoice->contact->display_name : null" icon="banknotes">
        @if ($invoice)
            <div class="mb-5 grid grid-cols-3 gap-2 rounded-xl bg-surface-2 p-4 text-[12.5px]">
                <div><p class="text-fg-subtle">Total</p><p class="font-semibold">{{ money($invoice->total) }}</p></div>
                <div><p class="text-fg-subtle">Déjà payé</p><p class="font-semibold text-emerald-600">{{ money($invoice->amount_paid) }}</p></div>
                <div><p class="text-fg-subtle">Reste dû</p><p class="font-semibold text-flame-600">{{ money($invoice->balance_due) }}</p></div>
            </div>
        @endif
        <div class="space-y-4">
            <x-ui.input label="Montant reçu" type="number" step="0.01" wire:model="amount" suffix="USD" required />
            <div>
                <p class="label">Mode de paiement</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (['cash' => ['Espèces', 'banknotes'], 'mobile_money' => ['Mobile money', 'device-phone-mobile'], 'card' => ['Carte', 'credit-card'], 'bank_transfer' => ['Virement', 'building-library']] as $key => [$label, $icon])
                        <button type="button" wire:click="$set('method', '{{ $key }}')" @class(['flex flex-col items-center gap-1.5 rounded-xl border p-3 text-[12.5px] font-semibold transition', 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-white' => $method === $key, 'border-line text-fg-muted hover:border-line-strong' => $method !== $key])>
                            <x-ui.icon :name="$icon" />{{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <x-ui.select label="Compte crédité" wire:model="collectAccountId" :options="$accounts->pluck('name', 'id')" placeholder="Choisir…" />
            @if ($method !== 'cash')<x-ui.input label="Référence de la transaction" wire:model="reference" placeholder="N° M-Pesa, bordereau…" />@endif
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="collect">Valider l’encaissement</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.modal wire:model="showClose" title="Clôturer la caisse" icon="lock-closed" tone="sun">
        <p>Solde théorique : <strong class="text-fg">{{ money($expected) }}</strong>. Comptez les espèces du tiroir et saisissez le montant réel.</p>
        <div class="mt-4 space-y-3">
            <x-ui.input label="Montant compté" type="number" step="0.01" wire:model.live="countedAmount" suffix="USD" />
            @php $gap = round((float) $countedAmount - $expected, 2); @endphp
            @if ($countedAmount !== '')
                <p @class(['rounded-lg px-3 py-2 text-[13px] font-semibold', 'bg-emerald-50 text-emerald-700' => $gap == 0, 'bg-flame-50 text-flame-700' => $gap != 0])>{{ $gap == 0 ? 'Aucun écart ✓' : 'Écart : '.money($gap) }}</p>
            @endif
            <x-ui.textarea label="Observations" wire:model="closingNotes" rows="2" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="lock-closed" wire:click="close">Clôturer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
