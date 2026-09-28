<div class="space-y-6">
    @if (session('print_ticket'))
        <div x-data x-init="window.open(@js(route('print.order-ticket', $order)), '_blank')"></div>
    @endif

    <x-page-header :title="'Commande '.$order->number">
        <x-slot:subtitle>
            {{ $order->site->name }} · créée le {{ $order->ordered_at->translatedFormat('d F Y à H:i') }} par {{ $order->salesRep?->name ?? '—' }}
        </x-slot:subtitle>
        <x-slot:actions>
            <x-status-badge :status="$order->status" size="lg" />
            <flux:badge :color="$order->priority->color()">Priorité {{ Str::lower($order->priority->label()) }}</flux:badge>
            @can('print', $order)
                <flux:button size="sm" icon="printer" :href="route('print.order-ticket', $order)" target="_blank">Ticket 80 mm</flux:button>
            @endcan
            @if ($order->status === \App\Enums\OrderStatus::Suspended)
                <flux:button size="sm" icon="play" wire:click="resume" variant="primary">Reprendre</flux:button>
            @endif
            @if ($possibleTransitions->isNotEmpty())
                <flux:dropdown align="end">
                    <flux:button size="sm" icon-trailing="chevron-down">Actions</flux:button>
                    <flux:menu>
                        @foreach ($possibleTransitions as $status)
                            <flux:menu.item wire:click="openTransition('{{ $status->value }}')" @class(['text-red-600' => $status === \App\Enums\OrderStatus::Cancelled])>
                                {{ match ($status) {
                                    \App\Enums\OrderStatus::AwaitingPayment => 'Transmettre à la caisse',
                                    \App\Enums\OrderStatus::Suspended => 'Suspendre',
                                    \App\Enums\OrderStatus::Cancelled => 'Annuler la commande',
                                    \App\Enums\OrderStatus::Closed => 'Clôturer',
                                    \App\Enums\OrderStatus::Returned => 'Déclarer un retour',
                                    \App\Enums\OrderStatus::Rework => 'Marquer « À refaire »',
                                    default => 'Passer à « '.$status->label().' »',
                                } }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Frise de progression --}}
    <div class="panel overflow-x-auto p-4">
        @php($currentIndex = array_search($order->status, $flow, true))
        <ol class="flex min-w-max items-center gap-1 text-xs">
            @foreach ($flow as $index => $step)
                <li class="flex items-center gap-1">
                    <span @class([
                        'rounded-full px-2.5 py-1 font-medium',
                        'bg-brand-600 text-white' => $step === $order->status,
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $currentIndex !== false && $index < $currentIndex,
                        'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' => $currentIndex === false || $index > $currentIndex,
                    ])>{{ $step->label() }}</span>
                    @unless ($loop->last)<flux:icon.chevron-right class="size-3 text-zinc-300" />@endunless
                </li>
            @endforeach
        </ol>
        @if ($currentIndex === false)
            <flux:callout class="mt-3" variant="warning" icon="exclamation-triangle" :heading="'Statut hors flux : '.$order->status->label()" :text="$order->cancellation_reason" />
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="panel p-4">
                <flux:heading>Produits & spécifications</flux:heading>
                <flux:table class="mt-2">
                    <flux:table.columns>
                        <flux:table.column>Désignation</flux:table.column>
                        <flux:table.column align="end">Qté</flux:table.column>
                        <flux:table.column align="end">P.U.</flux:table.column>
                        <flux:table.column align="end">Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($order->items as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell class="whitespace-normal!">
                                    <div class="font-medium">{{ $item->description }}</div>
                                    <div class="text-xs text-zinc-500">{{ $item->specSummary() ?: 'Aucune spécification' }}</div>
                                </flux:table.cell>
                                <flux:table.cell align="end">{{ (float) $item->quantity }}</flux:table.cell>
                                <flux:table.cell align="end"><x-money :amount="$item->unit_price" /></flux:table.cell>
                                <flux:table.cell align="end"><x-money :amount="$item->total" /></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                @if ($order->technical_notes)
                    <flux:callout class="mt-3" icon="information-circle" heading="Commentaire technique" :text="$order->technical_notes" />
                @endif
            </div>

            <div class="panel p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading>Fichiers client</flux:heading>
                </div>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($order->files as $file)
                        <li class="flex items-center justify-between py-2 text-sm" wire:key="f-{{ $file->id }}">
                            <div class="flex items-center gap-2"><flux:icon.paper-clip class="size-4 text-zinc-400" />{{ $file->original_name }} <span class="text-zinc-400">· {{ Number::fileSize($file->size) }}</span></div>
                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray" :href="route('files.order', $file)" />
                        </li>
                    @empty
                        <li class="py-2 text-sm text-zinc-500">Aucun fichier.</li>
                    @endforelse
                </ul>
                @can('update', $order)
                    <form wire:submit="uploadFiles" class="flex flex-wrap items-center gap-2">
                        <input type="file" wire:model="uploads" multiple class="text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 dark:file:bg-zinc-800" />
                        <flux:button type="submit" size="sm" icon="arrow-up-tray" wire:loading.attr="disabled">Téléverser</flux:button>
                        <flux:error name="uploads.*" />
                    </form>
                @endcan
            </div>

            @if ($order->designJob || $order->proofs->isNotEmpty())
                <div class="panel p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading>Conception & BAT</flux:heading>
                        @if ($order->designJob)
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-zinc-500">Graphiste :</span> {{ $order->designJob->designer?->name ?? 'non assigné' }}
                                <x-status-badge :status="$order->designJob->status" />
                            </div>
                        @endif
                    </div>
                    @if ($order->designJob?->blocked_reason)
                        <flux:callout variant="warning" icon="exclamation-triangle" :heading="$order->designJob->blocked_reason" :text="$order->designJob->report" />
                    @endif
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($order->proofs as $proof)
                            <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700" wire:key="p-{{ $proof->id }}">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $proof->number }} · v{{ $proof->version->version }}</span>
                                    <x-status-badge :status="$proof->status" />
                                </div>
                                <div class="mt-1 text-zinc-500">{{ $proof->version->original_name }}</div>
                                @if ($proof->validated_at)
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ $proof->status->value === 'approved' ? 'Validé' : 'Traité' }} le {{ $proof->validated_at->format('d/m/Y H:i') }}
                                        @if ($proof->validator_type) par {{ $proof->validator_type->label() }} ({{ $proof->validator_name }})@endif
                                        @if ($proof->comment) — « {{ $proof->comment }} »@endif
                                    </div>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <flux:button size="xs" icon="eye" :href="route('files.design', $proof->version)" target="_blank">Aperçu</flux:button>
                                    <flux:button size="xs" icon="printer" :href="route('print.proof', $proof)" target="_blank">Fiche BAT</flux:button>
                                    @if ($proof->status->value === 'pending' && (auth()->user()->can('proofs.validate') || auth()->user()->can('proofs.approve')))
                                        <flux:button size="xs" variant="primary" icon="check" wire:click="openProof({{ $proof->id }})">Valider / refuser</flux:button>
                                    @endif
                                    @if ($proof->is_locked)
                                        <flux:badge size="sm" color="emerald" icon="lock-closed">Verrouillé</flux:badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($order->printJobs->isNotEmpty())
                <div class="panel p-4">
                    <flux:heading>Production</flux:heading>
                    <flux:table class="mt-2">
                        <flux:table.columns>
                            <flux:table.column>Bon</flux:table.column>
                            <flux:table.column>Machine</flux:table.column>
                            <flux:table.column>Opérateur</flux:table.column>
                            <flux:table.column align="end">Prévu / produit / pertes</flux:table.column>
                            <flux:table.column>Statut</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($order->printJobs as $job)
                                <flux:table.row :key="$job->id">
                                    <flux:table.cell>{{ $job->number }}</flux:table.cell>
                                    <flux:table.cell>{{ $job->machine?->name ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>{{ $job->operator?->name ?? '—' }}</flux:table.cell>
                                    <flux:table.cell align="end">{{ $job->planned_quantity }} / {{ $job->produced_quantity }} / {{ $job->waste_quantity }}</flux:table.cell>
                                    <flux:table.cell><x-status-badge :status="$job->status" /></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif

            <div class="panel p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading>Livraison</flux:heading>
                    @if ($order->status === \App\Enums\OrderStatus::Ready)
                        @can('deliveries.create')
                            <flux:modal.trigger name="delivery"><flux:button size="sm" icon="truck">Planifier</flux:button></flux:modal.trigger>
                        @endcan
                    @endif
                </div>
                @forelse ($order->deliveries as $delivery)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800" wire:key="d-{{ $delivery->id }}">
                        <div>
                            <span class="font-medium">{{ $delivery->number }}</span> · {{ $delivery->mode->label() }} · {{ $delivery->driver?->name ?? 'sans livreur' }}
                            <div class="text-xs text-zinc-500">{{ $delivery->address }} {{ $delivery->delivered_at ? '· livrée le '.$delivery->delivered_at->format('d/m/Y H:i') : '' }} {{ $delivery->failure_reason ? '· '.$delivery->failure_reason : '' }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$delivery->status" />
                            <flux:button size="xs" icon="printer" :href="route('print.delivery-note', $delivery)" target="_blank">Bon</flux:button>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm">Aucune livraison planifiée.</flux:text>
                @endforelse
            </div>

            <div class="panel p-4 space-y-4">
                <flux:heading>Conversation interne</flux:heading>
                <form wire:submit="postComment" class="space-y-2">
                    <flux:textarea wire:model="comment" rows="2" placeholder="Écrire un commentaire… mentionnez un collègue avec @nom.utilisateur" />
                    <div class="flex items-center justify-between">
                        <flux:text size="sm" class="truncate">Mentions : {{ $mentionables->take(6)->map(fn ($u) => '@'.$u)->implode(' ') }}…</flux:text>
                        <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane">Envoyer</flux:button>
                    </div>
                </form>
                <ul class="space-y-3">
                    @foreach ($order->comments as $comment)
                        <li class="flex gap-3" wire:key="c-{{ $comment->id }}">
                            <flux:avatar size="sm" :initials="$comment->user->initials()" />
                            <div class="min-w-0 flex-1 rounded-lg bg-zinc-50 p-2.5 text-sm dark:bg-zinc-800">
                                <div class="flex justify-between text-xs text-zinc-500"><span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $comment->user->name }}</span>{{ $comment->created_at->diffForHumans() }}</div>
                                <div class="mt-1 whitespace-pre-line">{!! preg_replace('/@([A-Za-z0-9._-]+)/', '<span class="font-medium text-brand-600">@$1</span>', e($comment->body)) !!}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="panel p-4 space-y-2">
                <flux:heading>Client</flux:heading>
                <flux:link :href="route('clients.show', $order->client)" wire:navigate class="font-medium">{{ $order->client->displayName() }}</flux:link>
                <div class="text-sm text-zinc-500 space-y-0.5">
                    <div>{{ $order->client->number }} · {{ $order->client->type->label() }}</div>
                    @if ($order->client->phone)<div>{{ $order->client->phone }}</div>@endif
                    @if ($order->client->email)<div>{{ $order->client->email }}</div>@endif
                </div>
                @if ($order->client->whatsapp)
                    <flux:button size="xs" icon="chat-bubble-left-right" :href="'https://wa.me/'.preg_replace('/\D/', '', $order->client->whatsapp)" target="_blank">WhatsApp</flux:button>
                @endif
            </div>

            <div class="panel p-4 space-y-3">
                <flux:heading>Montants</flux:heading>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Total HT</dt><dd><x-money :amount="(float) $order->total - (float) $order->tax_total" /></dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Remises</dt><dd><x-money :amount="$order->discount_total" /></dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">TVA</dt><dd><x-money :amount="$order->tax_total" /></dd></div>
                    <div class="flex justify-between font-semibold"><dt>Total TTC</dt><dd><x-money :amount="$order->total" /></dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Acompte exigé</dt><dd><x-money :amount="$order->deposit_required" /></dd></div>
                    <div class="flex justify-between text-emerald-600"><dt>Payé</dt><dd><x-money :amount="$order->amount_paid" /></dd></div>
                    <div @class(['flex justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700', 'text-amber-600' => $order->balanceDue() > 0])><dt>Reste à payer</dt><dd><x-money :amount="$order->balanceDue()" /></dd></div>
                </dl>
                @if ($order->balanceDue() > 0 && auth()->user()->can('cash.collect'))
                    <flux:button size="sm" class="w-full" icon="banknotes" :href="route('cash.checkout', ['commande' => $order->number])" wire:navigate>Encaisser</flux:button>
                @endif
                @foreach ($order->invoices as $invoice)
                    <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-2 text-sm dark:bg-zinc-800" wire:key="inv-{{ $invoice->id }}">
                        <span>{{ $invoice->type->label() }} {{ $invoice->number }}</span>
                        <flux:dropdown align="end">
                            <flux:button size="xs" icon="printer" />
                            <flux:menu>
                                <flux:menu.item :href="route('print.invoice', [$invoice, 'a4'])" target="_blank">Format A4</flux:menu.item>
                                <flux:menu.item :href="route('print.invoice', [$invoice, 'a5'])" target="_blank">Format A5</flux:menu.item>
                                <flux:menu.item :href="route('print.invoice', [$invoice, 'ticket'])" target="_blank">Ticket 80 mm</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endforeach
            </div>

            <div class="panel p-4">
                <flux:heading>Paiements</flux:heading>
                <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @forelse ($order->payments as $payment)
                        <li class="flex justify-between py-2" wire:key="pay-{{ $payment->id }}">
                            <div>
                                <div>{{ $payment->method->label() }} <span class="text-zinc-400">{{ $payment->reference }}</span></div>
                                <div class="text-xs text-zinc-500">{{ $payment->paid_at->format('d/m/Y H:i') }} · {{ $payment->receiver?->name }}</div>
                            </div>
                            <x-money :amount="$payment->amount" class="font-medium" />
                        </li>
                    @empty
                        <li class="py-2 text-zinc-500">Aucun paiement.</li>
                    @endforelse
                </ul>
            </div>

            <div class="panel p-4">
                <flux:heading>Historique</flux:heading>
                <ol class="relative mt-3 space-y-4 border-s border-zinc-200 ps-4 dark:border-zinc-700">
                    @foreach ($order->statusHistories as $history)
                        <li class="text-sm" wire:key="h-{{ $history->id }}">
                            <span class="absolute -start-1.5 mt-1.5 size-3 rounded-full border-2 border-white bg-brand-500 dark:border-zinc-900"></span>
                            <div class="flex items-center gap-2"><x-status-badge :status="$history->to_status" /></div>
                            <div class="mt-1 text-xs text-zinc-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name ?? 'Système' }}</div>
                            @if ($history->comment)<div class="mt-0.5 text-zinc-600 dark:text-zinc-300">{{ $history->comment }}</div>@endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    <flux:modal name="transition" class="w-full max-w-md">
        <form wire:submit="changeStatus" class="space-y-4">
            <flux:heading size="lg">Changer le statut</flux:heading>
            @if ($targetStatus)
                <flux:text>Nouveau statut : <strong>{{ \App\Enums\OrderStatus::from($targetStatus)->label() }}</strong></flux:text>
            @endif
            <flux:textarea wire:model="transitionComment" :label="$targetStatus === 'cancelled' ? 'Motif d’annulation (obligatoire)' : 'Commentaire'" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Fermer</flux:button></flux:modal.close>
                <flux:button type="submit" :variant="$targetStatus === 'cancelled' ? 'danger' : 'primary'">Confirmer</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="proof" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Validation du BAT</flux:heading>
            <flux:text>La validation verrouille définitivement la version pour l’impression. Un refus renvoie le travail en correction.</flux:text>
            <flux:radio.group wire:model.live="proofValidator" label="Validé par" variant="segmented">
                <flux:radio value="client" label="Client" />
                @can('proofs.validate')<flux:radio value="sales_rep" label="Commercial" />@endcan
                @can('proofs.approve')<flux:radio value="manager" label="Responsable" />@endcan
            </flux:radio.group>
            @if ($proofValidator === 'client')
                <flux:input wire:model="proofValidatorName" label="Nom du client signataire" />
            @endif
            <flux:textarea wire:model="proofComment" label="Commentaire / motif du refus" rows="3" />
            <flux:error name="proofComment" />
            <div class="flex justify-end gap-2">
                <flux:button variant="danger" wire:click="rejectProof" icon="x-mark">Refuser</flux:button>
                <flux:button variant="primary" wire:click="approveProof" icon="check">Valider le BAT</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delivery" class="w-full max-w-lg">
        <form wire:submit="scheduleDelivery" class="space-y-4">
            <flux:heading size="lg">Planifier la livraison</flux:heading>
            <flux:radio.group wire:model.live="delivery.mode" label="Mode" variant="segmented">
                @foreach (\App\Enums\DeliveryMode::cases() as $mode)
                    <flux:radio :value="$mode->value" :label="$mode->label()" />
                @endforeach
            </flux:radio.group>
            @if ($delivery['mode'] !== 'pickup')
                <flux:select wire:model="delivery.driver_id" label="Livreur">
                    <flux:select.option value="">— À affecter —</flux:select.option>
                    @foreach ($drivers as $driver)
                        <flux:select.option :value="$driver->id">{{ $driver->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="delivery.address" label="Adresse" />
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="delivery.phone" label="Téléphone" />
                    <flux:input wire:model="delivery.fee" type="number" step="0.01" label="Frais de livraison" />
                </div>
            @endif
            <flux:input wire:model="delivery.scheduled_at" type="datetime-local" label="Date prévue" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Planifier</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
