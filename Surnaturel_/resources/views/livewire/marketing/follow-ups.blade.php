<div class="space-y-6">
    <x-page-header title="Suivi clients" eyebrow="Marketing" subtitle="Relances du jour, appels, messages WhatsApp, comptes rendus et rapports d’activité de l’équipe.">
        <x-slot:actions>
            <flux:switch wire:model.live="mineOnly" label="Mon portefeuille" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-kpi label="À relancer aujourd’hui" :value="$this->counts['today']" icon="bell-alert" color="amber" />
        <x-kpi label="Relances en retard" :value="$this->counts['late']" icon="exclamation-triangle" color="red" />
        <x-kpi label="Relances planifiées" :value="$this->counts['upcoming']" icon="calendar-days" color="sky" />
        <x-kpi label="Clients dormants (60 j)" :value="$this->counts['dormant']" icon="moon" color="zinc" />
    </div>

    <div class="tabs overflow-x-auto">
        @foreach (['today' => 'Aujourd’hui', 'late' => 'En retard', 'upcoming' => 'Planifiées', 'dormant' => 'Dormants', 'all' => 'Tout le portefeuille', 'report' => 'Rapports'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" class="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                {{ $label }}
                @isset($this->counts[$key])<span class="ms-1 rounded-full bg-zinc-100 px-1.5 text-[11px] dark:bg-white/10">{{ $this->counts[$key] }}</span>@endisset
            </button>
        @endforeach
    </div>

    @if ($tab === 'report')
        @php($report = $this->report)
        <div class="panel flex flex-wrap items-end gap-3 p-4">
            <flux:input wire:model.live="from" type="date" label="Du" />
            <flux:input wire:model.live="to" type="date" label="Au" />
            @unless ($mineOnly)
                <flux:select wire:model.live="agentId" label="Agent" class="min-w-48">
                    <flux:select.option value="">Toute l’équipe</flux:select.option>
                    @foreach ($agents as $agent)<flux:select.option :value="$agent->id">{{ $agent->name }}</flux:select.option>@endforeach
                </flux:select>
            @endunless
            <flux:spacer />
            @can('followups.print')<flux:button icon="document-arrow-down" wire:click="exportPdf">Rapport PDF</flux:button>@endcan
            @can('followups.export')<flux:button icon="table-cells" wire:click="exportExcel">Excel</flux:button>@endcan
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            @foreach ($report['kpis'] as $kpi)
                <x-kpi :label="$kpi['label']" :value="$kpi['value']" />
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="panel p-4 xl:col-span-2">
                <flux:heading>Activité par agent</flux:heading>
                <flux:table class="mt-2">
                    <flux:table.columns><flux:table.column>Agent</flux:table.column><flux:table.column align="end">Échanges</flux:table.column><flux:table.column align="end">Appels</flux:table.column><flux:table.column align="end">WhatsApp</flux:table.column><flux:table.column align="end">Clients</flux:table.column><flux:table.column align="end">Conversions</flux:table.column></flux:table.columns>
                    <flux:table.rows>
                        @forelse ($report['byAgent'] as $row)
                            <flux:table.row :key="'agent-'.$loop->index">
                                <flux:table.cell class="font-medium">{{ $row[0] }}</flux:table.cell>
                                @foreach (array_slice($row, 1) as $cell)<flux:table.cell align="end">{{ $cell }}</flux:table.cell>@endforeach
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="6"><x-empty-state icon="chart-bar" title="Aucun échange sur la période" /></flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="space-y-6">
                <div class="panel p-4">
                    <flux:heading>Par canal</flux:heading>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($report['byChannel'] as [$label, $count])
                            <li><div class="flex justify-between"><span>{{ $label }}</span><span class="font-semibold">{{ $count }}</span></div>
                                <div class="mt-1 h-1.5 rounded-full bg-zinc-100 dark:bg-white/8"><div class="h-full rounded-full bg-cyan2-500" style="width: {{ round($count / max(1, $report['rows']->count()) * 100) }}%"></div></div></li>
                        @endforeach
                    </ul>
                </div>
                <div class="panel p-4">
                    <flux:heading>Résultats</flux:heading>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach ($report['byOutcome'] as [$label, $count])<li class="flex justify-between"><span>{{ $label }}</span><span class="font-semibold">{{ $count }}</span></li>@endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="panel p-4">
            <flux:heading>Journal des échanges</flux:heading>
            <ul class="mt-3 divide-y divide-line">
                @forelse ($report['rows']->take(50) as $item)
                    <li class="flex items-start gap-3 py-2.5 text-sm" wire:key="j-{{ $item->id }}">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-zinc-100 text-fg-muted dark:bg-white/8"><flux:icon :icon="$item->channel->icon()" variant="micro" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ $item->client->displayName() }}</span>@if ($item->outcome)<flux:badge size="sm" :color="$item->outcome->color()">{{ $item->outcome->label() }}</flux:badge>@endif</div>
                            <div class="text-fg-muted">{{ $item->subject }} {{ \Illuminate\Support\Str::limit((string) $item->notes, 160) }}</div>
                        </div>
                        <div class="shrink-0 text-right text-xs text-fg-subtle">{{ $item->occurred_at->format('d/m H:i') }}<br>{{ $item->user->name }}</div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-fg-subtle">Aucun échange enregistré.</li>
                @endforelse
            </ul>
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-5">
            <div class="space-y-3 xl:col-span-3">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher un client (nom, téléphone, n°)…" />
                <div class="panel divide-y divide-line">
                    @forelse ($clients as $client)
                        <div @class(['flex flex-wrap items-center gap-3 p-3 transition sm:flex-nowrap', 'bg-brand-50/60 dark:bg-white/4' => $clientId === $client->id]) wire:key="fc-{{ $client->id }}">
                            <button type="button" wire:click="select({{ $client->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                                <span class="avatar size-10 bg-brand-100 text-[12px] text-brand-700 dark:bg-white/10 dark:text-white">{{ mb_strtoupper(mb_substr($client->displayName(), 0, 2)) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold">{{ $client->displayName() }}</span>
                                    <span class="block truncate text-xs text-fg-subtle">{{ $client->phone ?? 'Pas de téléphone' }} · {{ $client->followUpOwner?->name ?? $client->salesRep?->name ?? 'Non attribué' }}</span>
                                </span>
                            </button>
                            <div class="text-xs sm:text-right">
                                @if ($client->next_follow_up_at)
                                    <span @class(['font-semibold', 'text-carmin-600' => $client->next_follow_up_at->lt(now()->startOfDay()), 'text-safran-700 dark:text-safran-300' => $client->next_follow_up_at->isToday()])>Relance {{ $client->next_follow_up_at->isToday() ? 'à '.$client->next_follow_up_at->format('H:i') : $client->next_follow_up_at->format('d/m') }}</span><br>
                                @endif
                                <span class="text-fg-subtle">{{ $client->last_contacted_at ? 'Contacté '.$client->last_contacted_at->diffForHumans() : 'Jamais contacté' }}</span>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                @can('followups.create')
                                    @if ($client->phone)<flux:button size="sm" icon="phone" :href="'tel:'.preg_replace('/[^\d+]/', '', $client->phone)" wire:click="startCall({{ $client->id }})" title="Appeler" />@endif
                                    @if ($client->whatsappNumber())<flux:button size="sm" icon="chat-bubble-left-right" class="text-emerald-600!" wire:click="startWhatsApp({{ $client->id }})" title="WhatsApp" />@endif
                                @endcan
                                @can('followups.update')
                                    <flux:dropdown>
                                        <flux:button size="sm" icon="clock" title="Reporter la relance" />
                                        <flux:menu>
                                            <flux:menu.item wire:click="snooze({{ $client->id }}, 1)">Demain</flux:menu.item>
                                            <flux:menu.item wire:click="snooze({{ $client->id }}, 3)">Dans 3 jours</flux:menu.item>
                                            <flux:menu.item wire:click="snooze({{ $client->id }}, 7)">Dans une semaine</flux:menu.item>
                                            <flux:menu.item wire:click="snooze({{ $client->id }}, 30)">Dans un mois</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="phone-arrow-up-right" title="Aucun client dans cette vue" text="Planifiez des relances depuis la fiche d’échange pour les retrouver ici." />
                    @endforelse
                </div>
                {{ $clients->links() }}
            </div>

            <div class="xl:col-span-2">
                @if ($client = $this->client)
                    <div class="panel space-y-4 p-5 xl:sticky xl:top-20">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="eyebrow">{{ $client->number }} · {{ $client->type->label() }}</p>
                                <flux:heading size="lg" class="truncate">{{ $client->displayName() }}</flux:heading>
                                <flux:text>{{ collect([$client->phone, $client->email])->filter()->implode(' · ') ?: '—' }}</flux:text>
                            </div>
                            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('clients.show', $client)" wire:navigate title="Fiche client" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @if ($callUrl)<flux:button icon="phone" :href="$callUrl" wire:click="$set('log.channel', 'call')">Appeler</flux:button>@else<flux:button icon="phone" disabled>Appeler</flux:button>@endif
                            <flux:button icon="chat-bubble-left-right" wire:click="startWhatsApp({{ $client->id }})" :disabled="! $client->whatsappNumber()" class="text-emerald-700! dark:text-emerald-300!">WhatsApp</flux:button>
                        </div>

                        @can('followups.create')
                            <form wire:submit="record" class="space-y-3 rounded-xl border border-line p-3">
                                <div class="text-sm font-semibold">Compte rendu d’échange</div>
                                <div class="grid grid-cols-2 gap-2">
                                    <flux:select wire:model.live="log.channel" size="sm">@foreach ($channels as $channel)<flux:select.option :value="$channel->value">{{ $channel->label() }}</flux:select.option>@endforeach</flux:select>
                                    <flux:select wire:model="log.outcome" size="sm"><flux:select.option value="">Résultat…</flux:select.option>@foreach ($outcomes as $outcome)<flux:select.option :value="$outcome->value">{{ $outcome->label() }}</flux:select.option>@endforeach</flux:select>
                                </div>
                                <flux:input wire:model="log.subject" size="sm" placeholder="Objet (relance devis, promotion, réclamation…)" />
                                <flux:textarea wire:model="log.notes" rows="3" placeholder="Notes : ce qui a été dit, besoins exprimés, objections…" />
                                <div class="grid grid-cols-2 gap-2">
                                    @if ($log['channel'] === 'call')<flux:input wire:model="log.duration_minutes" type="number" step="0.5" size="sm" placeholder="Durée (min)" />@endif
                                    <flux:input wire:model="log.next_follow_up_at" type="datetime-local" size="sm" :class="$log['channel'] === 'call' ? '' : 'col-span-2'" />
                                </div>
                                <flux:error name="log.next_follow_up_at" />
                                <flux:button type="submit" size="sm" variant="primary" icon="check" class="w-full">Enregistrer l’échange</flux:button>
                            </form>
                        @endcan

                        <div>
                            <div class="mb-2 text-sm font-semibold">Historique</div>
                            <ol class="relative space-y-3 border-s border-line ps-4">
                                @forelse ($client->interactions as $item)
                                    <li class="text-sm" wire:key="h-{{ $item->id }}">
                                        <span class="absolute -start-[7px] mt-1 size-3 rounded-full border-2 border-surface bg-brand-500"></span>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <flux:badge size="sm" :color="$item->channel->color()" :icon="$item->channel->icon()">{{ $item->channel->label() }}</flux:badge>
                                            @if ($item->outcome)<flux:badge size="sm" :color="$item->outcome->color()">{{ $item->outcome->label() }}</flux:badge>@endif
                                            <span class="text-xs text-fg-subtle">{{ $item->occurred_at->format('d/m/Y H:i') }} · {{ $item->user->name }}</span>
                                        </div>
                                        @if ($item->subject)<div class="mt-1 font-medium">{{ $item->subject }}</div>@endif
                                        @if ($item->notes)<div class="whitespace-pre-line text-fg-muted">{{ $item->notes }}</div>@endif
                                        @if ($item->next_follow_up_at)<div class="mt-0.5 text-xs text-safran-700 dark:text-safran-300">Relance prévue le {{ $item->next_follow_up_at->format('d/m/Y H:i') }}</div>@endif
                                    </li>
                                @empty
                                    <li class="text-sm text-fg-subtle">Aucun échange enregistré.</li>
                                @endforelse
                            </ol>
                        </div>
                    </div>
                @else
                    <div class="panel"><x-empty-state icon="cursor-arrow-rays" title="Sélectionnez un client" text="Appelez, écrivez sur WhatsApp et consignez chaque échange." /></div>
                @endif
            </div>
        </div>
    @endif

    <flux:modal name="whatsapp" class="w-full max-w-lg">
        <form wire:submit="sendWhatsApp" class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-emerald-500 text-white"><flux:icon.chat-bubble-left-right /></span>
                <div>
                    <flux:heading size="lg">Message WhatsApp</flux:heading>
                    <flux:text>{{ $this->client?->displayName() }} · +{{ $this->client?->whatsappNumber() }}</flux:text>
                </div>
            </div>
            <flux:select wire:model.live="template" label="Modèle">
                @foreach ($templates as $index => $tpl)<flux:select.option :value="(string) $index">{{ $tpl['name'] }}</flux:select.option>@endforeach
            </flux:select>
            <flux:textarea wire:model="message" label="Message" rows="5" />
            <div class="grid grid-cols-2 gap-2">
                <flux:select wire:model="log.outcome" label="Résultat"><flux:select.option value="">—</flux:select.option>@foreach ($outcomes as $outcome)<flux:select.option :value="$outcome->value">{{ $outcome->label() }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="log.next_follow_up_at" type="datetime-local" label="Prochaine relance" />
            </div>
            <flux:text class="text-xs">WhatsApp s’ouvre avec le message pré-rempli ; l’envoi est consigné dans l’historique du client.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="bg-emerald-600! hover:bg-emerald-700!">Ouvrir WhatsApp</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
