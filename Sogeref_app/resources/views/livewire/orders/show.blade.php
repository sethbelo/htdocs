<div>
    @php $canManage = auth()->user()->canManage('orders'); @endphp
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$order->title" :eyebrow="$order->number.' · '.config('erp.order_kinds.'.$order->kind)" :back="route('orders.index')">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[13px] text-fg-muted">
                <x-ui.status map="order_stage" :value="$order->stage" />
                <x-ui.status map="priority" :value="$order->priority" />
                <span class="flex items-center gap-1"><x-ui.icon name="user" class="size-4" /> {{ $order->contact->display_name }}</span>
                @if ($order->delivery_date)<span class="flex items-center gap-1"><x-ui.icon name="calendar" class="size-4" /> Livraison {{ $order->delivery_date->format('d/m/Y') }}</span>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="printer" :href="route('pdf.order', $order)" :navigate="false" target="_blank">Dossier PDF</x-ui.button>
            @if ($canManage && ! in_array($order->stage, ['cloture', 'annule']))
                <x-ui.button variant="ghost" icon="pencil-square" wire:click="$set('showEdit', true)">Modifier</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. PARCOURS ══════════════════════════════════════════════════════ --}}
    <div class="card mb-6 p-5">
        <x-ui.stepper :steps="$steps" :current="$order->stage" :cancelled="$order->stage === 'annule'" />

        {{-- 2.1 Action suivante --}}
        @if ($order->stage === 'annule')
            <div class="mt-5 flex items-center gap-3 rounded-xl bg-flame-50 p-4 text-flame-700 dark:bg-flame-400/10 dark:text-flame-300"><x-ui.icon name="no-symbol" /> Cette commande a été annulée.</div>
        @elseif ($actions)
            <div class="mt-5 flex flex-col gap-3 rounded-xl border border-sun-300 bg-sun-50 p-4 sm:flex-row sm:items-center dark:border-sun-400/30 dark:bg-sun-400/8">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sun-400 text-brand-900"><x-ui.icon name="bolt" variant="s" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-fg">Prochaine étape</p>
                    <p class="text-[13px] text-fg-muted">
                        @switch($actions[0])
                            @case('validate') Faites valider le devis {{ $order->quote?->number }} par le client pour lancer la facturation. @break
                            @case('invoice') Le client a donné son accord : émettez la facture. @break
                            @case('collect') Encaissez {{ $order->stage === 'facture' ? 'l’acompte ('.erp_setting('orders.deposit_rate', 50).' % minimum)' : 'le solde' }} à la caisse : {{ money($order->balanceDue(), $order->currency_code) }} restant dû. @break
                            @case('launch') {{ $order->kind === 'fabrication' ? 'Transmettez la commande à l’atelier : un ordre de fabrication et la liste de matériel seront générés.' : ($order->kind === 'construction' ? 'Ouvrez le chantier : projet, dossier de construction et liste de matériel seront créés.' : 'Préparez et remettez la commande au client.') }} @break
                            @case('deliver') {{ $order->kind === 'fabrication' ? 'Dès que l’ordre de fabrication est terminé, enregistrez la livraison.' : 'À la réception des travaux, enregistrez la livraison.' }} @break
                            @case('close') Tout est livré et encaissé : clôturez le dossier. @break
                        @endswitch
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($actions as $action)
                        @switch($action)
                            @case('validate')<x-ui.button variant="primary" icon="check-badge" wire:click="validateQuote" wire:confirm="Confirmer l’accord du client sur le devis ?">Valider le devis</x-ui.button>@break
                            @case('invoice')@if ($canManage)<x-ui.button variant="primary" icon="document-currency-dollar" wire:click="issueInvoice">Émettre la facture</x-ui.button>@endif @break
                            @case('collect')@if (auth()->user()->canManage('cash'))<x-ui.button variant="primary" icon="banknotes" wire:click="openPayment">Encaisser</x-ui.button>@endif @break
                            @case('launch')@if ($canManage)<x-ui.button variant="primary" icon="rocket-launch" wire:click="openLaunch">{{ $order->kind === 'fabrication' ? 'Lancer en atelier' : ($order->kind === 'construction' ? 'Ouvrir le chantier' : 'Lancer') }}</x-ui.button>@endif @break
                            @case('deliver')@if ($canManage)<x-ui.button variant="primary" icon="truck" wire:click="deliver" wire:confirm="Enregistrer la livraison au client ?">Marquer livré</x-ui.button>@endif @break
                            @case('close')@if ($canManage)<x-ui.button variant="success" icon="lock-closed" wire:click="close">Clôturer</x-ui.button>@endif @break
                        @endswitch
                    @endforeach
                    @if ($canManage && ! in_array($order->stage, ['livre', 'cloture']))
                        <x-ui.button variant="ghost" icon="x-circle" wire:click="$set('showCancel', true)">Annuler</x-ui.button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- ═══ 3. PIÈCES LIÉES ══════════════════════════════════════════ --}}
            <div class="grid gap-3 sm:grid-cols-2">
                @php
                    $pieces = [
                        ['Devis', 'document-text', $order->quote, $order->quote ? route('documents.show', $order->quote) : null, $order->quote ? erp_status('document', $order->quote->status->value) : null, $order->quote?->number, $order->quote ? money($order->quote->total) : null],
                        ['Facture', 'document-currency-dollar', $order->invoice, $order->invoice ? route('documents.show', $order->invoice) : null, $order->invoice ? erp_status('document', $order->invoice->status->value) : null, $order->invoice?->number ?? 'À émettre', $order->invoice ? money($order->invoice->total) : null],
                    ];
                    if ($order->kind !== 'vente') {
                        $mo = $order->manufacturingOrder;
                        $pieces[] = [$order->kind === 'construction' ? 'Dossier de construction' : 'Ordre de fabrication', 'wrench-screwdriver', $mo, $mo ? route('manufacturing.show', $mo) : null, $mo ? erp_status('manufacturing', $mo->status) : null, $mo?->number ?? 'Après encaissement', $mo ? $mo->progress.' %' : null];
                    }
                    if (in_array($order->kind, ['construction', 'service'])) {
                        $pieces[] = ['Projet', 'rectangle-stack', $order->project, $order->project ? route('projects.show', $order->project) : null, $order->project ? erp_status('project', $order->project->status) : null, $order->project?->code ?? 'Au lancement', $order->project ? $order->project->progress.' %' : null];
                    }
                    if ($order->kind === 'construction') {
                        $pieces[] = ['Chantier', 'home-modern', $order->site, $order->site ? route('sites.show', $order->site) : null, $order->site ? erp_status('site', $order->site->status) : null, $order->site?->code ?? 'Au lancement', $order->site ? $order->site->progress.' %' : null];
                    }
                @endphp
                @foreach ($pieces as [$label, $icon, $model, $url, $status, $ref, $value])
                    <{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" wire:navigate @endif @class(['card flex items-center gap-3 p-4 transition', 'hover:border-brand-200 hover:shadow-md' => $url, 'opacity-60' => ! $model])>
                        <span @class(['grid size-11 shrink-0 place-items-center rounded-xl', 'bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300' => $model, 'bg-ink-100 text-fg-subtle dark:bg-white/5' => ! $model])><x-ui.icon :name="$icon" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12px] text-fg-subtle">{{ $label }}</p>
                            <p class="truncate font-semibold">{{ $ref }}</p>
                        </div>
                        <div class="text-right">
                            @if ($status)<x-ui.badge :tone="$status[1]" dot>{{ $status[0] }}</x-ui.badge>@endif
                            @if ($value)<p class="mt-1 text-[12.5px] font-semibold tabular-nums">{{ $value }}</p>@endif
                        </div>
                    </{{ $url ? 'a' : 'div' }}>
                @endforeach
            </div>

            {{-- ═══ 4. ARTICLES ══════════════════════════════════════════════ --}}
            <x-ui.card title="Articles et prestations" :subtitle="$order->quote ? 'Selon le devis '.$order->quote->number : null" icon="list-bullet" :padded="false">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Désignation</th><th class="num">Qté</th><th class="num">P.U. HT</th><th class="num">TVA</th><th class="num">Total TTC</th></tr></thead>
                        <tbody>
                            @foreach ($order->quote?->items ?? [] as $item)
                                <tr>
                                    <td><p class="font-medium">{{ $item->description }}</p>@if ($item->product)<p class="text-[12px] text-fg-subtle">{{ $item->product->sku }} · {{ $item->product->typeLabel() }}</p>@endif</td>
                                    <td class="num">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                                    <td class="num">{{ money($item->unit_price) }}</td>
                                    <td class="num text-fg-muted">{{ qty($item->tax_rate) }} %</td>
                                    <td class="num font-semibold">{{ money($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- ═══ 5. BONS LIÉS ═════════════════════════════════════════════ --}}
            @if ($order->vouchers->isNotEmpty())
                <x-ui.card title="Bons liés" icon="ticket" :padded="false">
                    <ul>
                        @foreach ($order->vouchers as $voucher)
                            <li class="flex items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                                <x-ui.icon :name="config('erp.voucher_types.'.$voucher->type.'.icon')" class="text-fg-subtle" />
                                <div class="min-w-0 flex-1"><p class="font-semibold">{{ $voucher->number }}</p><p class="truncate text-[12px] text-fg-subtle">{{ $voucher->purpose }}</p></div>
                                <x-ui.status map="voucher" :value="$voucher->status" />
                                <a href="{{ route('pdf.voucher', $voucher) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" aria-label="PDF"><x-ui.icon name="printer" class="size-4" /></a>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            {{-- ═══ 6. JOURNAL DE SUIVI ══════════════════════════════════════ --}}
            <x-ui.card title="Journal de suivi" icon="clock">
                <form wire:submit="addNote" class="mb-5 flex gap-2">
                    <input wire:model="note" class="input" placeholder="Ajouter une note interne (appel client, remarque atelier…)">
                    <x-ui.button type="submit" variant="secondary" icon="paper-airplane">Ajouter</x-ui.button>
                </form>
                <ol class="relative space-y-5 border-l-2 border-line pl-6">
                    @foreach ($order->events as $event)
                        <li class="relative">
                            <span @class(['absolute top-0.5 -left-[33px] grid size-4 place-items-center rounded-full ring-4 ring-surface', 'bg-sun-400' => $event->from_stage !== $event->to_stage, 'bg-ink-300' => $event->from_stage === $event->to_stage])></span>
                            <p class="text-[13.5px] font-semibold">{{ $event->title }}</p>
                            @if ($event->note)<p class="mt-0.5 text-[13px] text-fg-muted">{{ $event->note }}</p>@endif
                            <p class="mt-0.5 text-[12px] text-fg-subtle">{{ $event->created_at->translatedFormat('j M Y · H:i') }} · {{ $event->user?->name ?? 'Système' }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>

        {{-- ═══ 7. COLONNE LATÉRALE ══════════════════════════════════════════ --}}
        <aside class="space-y-6">
            <x-ui.card title="Situation financière" icon="banknotes">
                <dl class="space-y-2.5 text-[13.5px]">
                    <div class="flex justify-between"><dt class="text-fg-muted">Montant TTC</dt><dd class="font-display text-[18px] font-semibold tabular-nums">{{ money($order->total, $order->currency_code) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-muted">Encaissé</dt><dd class="font-semibold text-emerald-600 tabular-nums">{{ money($order->amount_paid, $order->currency_code) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-muted">Reste dû</dt><dd @class(['font-semibold tabular-nums', 'text-flame-600' => $order->balanceDue() > 0])>{{ money($order->balanceDue(), $order->currency_code) }}</dd></div>
                </dl>
                <div class="relative mt-4">
                    <x-ui.progress :value="round($order->paidRatio() * 100)" :tone="$order->balanceDue() > 0 ? 'lagoon' : 'emerald'" :label="'Taux d’encaissement'" />
                    <span class="absolute bottom-0 h-3 w-0.5 bg-sun-500" style="left: {{ erp_setting('orders.deposit_rate', 50) }}%" title="Seuil d’acompte"></span>
                </div>
                <p class="mt-2 text-[12px] text-fg-subtle">Seuil d’acompte pour lancer la production : {{ erp_setting('orders.deposit_rate', 50) }} %.</p>
                @if ($order->invoice?->allocations->isNotEmpty())
                    <ul class="mt-4 space-y-2 border-t border-line pt-3">
                        @foreach ($order->invoice->allocations as $allocation)
                            <li class="flex justify-between text-[12.5px]"><span class="text-fg-muted">{{ $allocation->payment?->paid_at?->format('d/m/Y') }} · {{ $allocation->payment?->method?->label() }}</span><span class="flex items-center gap-2"><span class="font-semibold tabular-nums">{{ money($allocation->amount) }}</span>@if ($allocation->payment && auth()->user()->canAccessModule('cash'))<a href="{{ route('pdf.receipt', $allocation->payment) }}" target="_blank" class="link text-[12px]">Reçu</a>@endif</span></li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Client" icon="user">
                <a href="{{ route('contacts.show', $order->contact) }}" wire:navigate class="flex items-center gap-3">
                    <x-ui.avatar :name="$order->contact->display_name" />
                    <div class="min-w-0"><p class="truncate font-semibold">{{ $order->contact->display_name }}</p><p class="text-[12px] text-fg-subtle">{{ $order->contact->reference }} · {{ strtoupper($order->contact->segment) }}</p></div>
                </a>
                <dl class="mt-4 space-y-1.5 text-[13px]">
                    @if ($order->contact->phone)<div class="flex gap-2"><x-ui.icon name="phone" class="size-4 text-fg-subtle" />{{ $order->contact->phone }}</div>@endif
                    @if ($order->contact->email)<div class="flex gap-2"><x-ui.icon name="envelope" class="size-4 text-fg-subtle" /><span class="truncate">{{ $order->contact->email }}</span></div>@endif
                    @if ($order->contact->loyaltyCard)<div class="flex gap-2"><x-ui.icon name="gift" class="size-4 text-fg-subtle" />Carte {{ $order->contact->loyaltyCard->card_number }} · {{ $order->contact->loyaltyCard->tier }} · {{ $order->contact->loyaltyCard->points }} pts</div>@endif
                </dl>
            </x-ui.card>

            <x-ui.card title="Informations" icon="information-circle">
                <dl class="space-y-2 text-[13px]">
                    <div class="flex justify-between gap-3"><dt class="text-fg-muted">Nature</dt><dd class="font-medium">{{ config('erp.order_kinds.'.$order->kind) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-fg-muted">Canal</dt><dd class="font-medium">{{ strtoupper($order->channel) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-fg-muted">Chargé d’affaires</dt><dd class="font-medium">{{ $order->owner?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-fg-muted">Ouvert le</dt><dd class="font-medium">{{ $order->created_at->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-fg-muted">Validé le</dt><dd class="font-medium">{{ $order->validated_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-fg-muted">Livré le</dt><dd class="font-medium">{{ $order->delivered_at?->format('d/m/Y') ?? '—' }}</dd></div>
                </dl>
                @if ($order->description)<p class="mt-4 border-t border-line pt-3 text-[13px] text-fg-muted">{{ $order->description }}</p>@endif
            </x-ui.card>
        </aside>
    </div>

    {{-- ═══ 8. FENÊTRES ══════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showPayment" title="Encaisser un paiement" :subtitle="$order->invoice?->number" icon="banknotes">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3 rounded-xl bg-surface-2 p-4 text-[13px]">
                <div><p class="text-fg-subtle">Total facture</p><p class="font-semibold">{{ money($order->invoice?->total) }}</p></div>
                <div><p class="text-fg-subtle">Reste dû</p><p class="font-semibold text-flame-600">{{ money($order->invoice?->balance_due) }}</p></div>
            </div>
            <x-ui.input label="Montant encaissé" type="number" step="0.01" wire:model="paymentAmount" suffix="USD" required />
            <x-ui.select label="Mode de paiement" wire:model.live="paymentMethod" :options="['cash' => 'Espèces', 'mobile_money' => 'Mobile money (M-Pesa, Orange, Airtel)', 'bank_transfer' => 'Virement bancaire', 'card' => 'Carte bancaire', 'cheque' => 'Chèque']" />
            <x-ui.select label="Compte de trésorerie" wire:model="paymentAccountId" placeholder="Choisir…" :options="$accounts->pluck('name', 'id')" required />
            <x-ui.input label="Référence (n° transaction, chèque…)" wire:model="paymentReference" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="recordPayment">Enregistrer le paiement</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showLaunch" :title="$order->kind === 'fabrication' ? 'Lancer en atelier' : ($order->kind === 'construction' ? 'Ouvrir le chantier' : 'Lancer la commande')" icon="rocket-launch">
        <div class="space-y-4">
            <p class="rounded-xl bg-brand-50 p-4 text-[13px] text-brand-700 dark:bg-white/6 dark:text-brand-100">
                @if ($order->kind === 'fabrication')
                    Un <strong>ordre de fabrication</strong> sera créé avec la <strong>liste de matériel</strong> calculée depuis les nomenclatures et une gamme opératoire type. La fiche de fabrication sera imprimable immédiatement.
                @elseif ($order->kind === 'construction')
                    Un <strong>chantier</strong>, un <strong>projet</strong> et un <strong>dossier de construction</strong> (métré des matériaux, phases de travaux) seront créés.
                @else
                    Les articles seront sortis du stock et la commande passera à l’état « Livré ».
                @endif
            </p>
            @if ($order->kind !== 'vente')
                <x-ui.select :label="$order->kind === 'fabrication' ? 'Responsable de fabrication' : 'Responsable du chantier'" wire:model="responsibleId" placeholder="À désigner plus tard" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' — '.($e->position?->title ?? '')])" />
            @endif
            @if ($order->kind === 'fabrication')
                <x-ui.select label="Atelier de production" wire:model="warehouseId" :options="$warehouses->pluck('name', 'id')" />
            @endif
            @if (in_array($order->kind, ['construction', 'service']))
                <x-ui.select label="Chef de projet" wire:model="managerUserId" :options="$users->pluck('name', 'id')" />
            @endif
            @if ($order->kind === 'construction')
                <x-ui.input label="Adresse du chantier" wire:model="siteAddress" placeholder="Avenue, numéro, quartier" />
                <x-ui.input label="Commune / ville" wire:model="siteCity" />
            @endif
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="rocket-launch" wire:click="launch">Confirmer le lancement</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showEdit" title="Modifier le dossier" icon="pencil-square">
        <div class="space-y-4">
            <x-ui.input label="Objet de la commande" wire:model="title" required />
            <x-ui.input label="Date de livraison prévue" type="date" wire:model="deliveryDate" />
            <x-ui.select label="Priorité" wire:model="priority" :options="collect(config('erp.statuses.priority'))->map(fn ($p) => $p[0])" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveDetails">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.modal wire:model="showCancel" title="Annuler la commande" icon="exclamation-triangle" tone="danger">
        <p>Cette action est définitive. Indiquez le motif de l’annulation.</p>
        <x-ui.textarea wire:model="cancelReason" rows="3" class="mt-3" placeholder="Motif (ex. : client injoignable, budget refusé…)" />
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Retour</x-ui.button>
            <x-ui.button variant="danger" wire:click="cancel">Annuler la commande</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
