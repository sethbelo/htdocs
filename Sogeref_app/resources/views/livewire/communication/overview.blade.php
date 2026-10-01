<div wire:poll.30s>
    @php
        $integrationBadge = ['configured' => ['Configuré', 'success', 'check-circle'], 'simulation' => ['Simulation', 'warning', 'beaker'], 'missing' => ['Non configuré', 'danger', 'exclamation-triangle']];
        $channelRoute = ['whatsapp' => 'communication.whatsapp', 'sms' => 'communication.sms', 'email' => 'communication.emails'];
        $maxLoad = max(1, (int) $numbers->max(fn ($n) => (int) data_get($n, 'open', data_get($n, 'conversations', 0))));
    @endphp

    <x-ui.page-header title="Communication & marketing" eyebrow="Communication" subtitle="WhatsApp, SMS et e-mails clients au même endroit : conversations, campagnes de masse et modèles de messages.">
        <x-slot:actions>
            @if ($canCampaign)<x-ui.button variant="secondary" icon="megaphone" :href="route('communication.campaigns')">Campagnes</x-ui.button>@endif
            @if ($canSend)<x-ui.button variant="accent" icon="chat-bubble-oval-left-ellipsis" :href="route('communication.whatsapp')">Ouvrir WhatsApp</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($simulation)
        <div class="mb-5 flex flex-col gap-3 rounded-[var(--radius-card)] border border-sun-300 bg-sun-50 p-4 text-[13.5px] text-sun-900 sm:flex-row sm:items-center dark:border-sun-400/30 dark:bg-sun-400/10 dark:text-sun-200">
            <x-ui.icon name="beaker" class="size-6 shrink-0" />
            <p class="flex-1"><strong>Mode simulation actif :</strong> les envois réussissent localement sans contacter WhatsApp, l’opérateur SMS ni le serveur e-mail. Idéal pour former l’équipe et tester les campagnes.</p>
            @if ($isMaster)<x-ui.button variant="secondary" size="sm" icon="cog-6-tooth" :href="route('system', ['onglet' => 'integrations'])">Intégrations</x-ui.button>@endif
        </div>
    @endif

    {{-- ═══ INDICATEURS PAR CANAL ══════════════════════════════════════════ --}}
    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($kpis as $channel => $k)
            @php [$iLabel, $iTone, $iIcon] = $integrationBadge[$k['integration']]; @endphp
            <section class="card overflow-hidden">
                <header class="flex items-center gap-3 border-b border-line px-5 py-3.5">
                    <span @class(['grid size-10 shrink-0 place-items-center rounded-xl',
                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300' => $channel === 'whatsapp',
                        'bg-lagoon-50 text-lagoon-700 dark:bg-lagoon-400/12 dark:text-lagoon-300' => $channel === 'sms',
                        'bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300' => $channel === 'email'])><x-ui.icon :name="$k['icon']" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="card-title">{{ $k['label'] }}</h2>
                        <p class="text-[12px] text-fg-subtle">30 derniers jours</p>
                    </div>
                    <x-ui.badge :tone="$iTone" :icon="$iIcon" :title="$k['configured'] ? 'Paramètres complets' : 'Paramètres à compléter'">{{ $iLabel }}</x-ui.badge>
                </header>
                <dl class="grid grid-cols-2 gap-px bg-line">
                    <div class="bg-surface p-4"><dt class="text-[12px] text-fg-subtle">Envoyés</dt><dd class="font-display text-[24px] font-semibold tabular-nums">{{ number_format($k['sent'], 0, ',', ' ') }}</dd></div>
                    <div class="bg-surface p-4"><dt class="text-[12px] text-fg-subtle">Reçus</dt><dd class="font-display text-[24px] font-semibold tabular-nums">{{ number_format($k['received'], 0, ',', ' ') }}</dd></div>
                    <div class="bg-surface p-4"><dt class="text-[12px] text-fg-subtle">Conversations ouvertes</dt><dd class="font-display text-[20px] font-semibold tabular-nums">{{ $k['open'] }}</dd></div>
                    <div class="bg-surface p-4"><dt class="text-[12px] text-fg-subtle">Non lus</dt><dd @class(['font-display text-[20px] font-semibold tabular-nums', 'text-flame-600 dark:text-flame-300' => $k['unread']])>{{ $k['unread'] }}</dd></div>
                </dl>
                <div class="space-y-2 border-t border-line px-5 py-4">
                    @if ($k['rate'] === null)
                        <p class="text-[12.5px] text-fg-subtle">Aucun envoi sur la période.</p>
                    @else
                        <x-ui.progress :value="$k['rate']" :tone="$k['rate'] >= 90 ? 'emerald' : ($k['rate'] >= 70 ? 'sun' : 'flame')" label="Taux de réussite des envois" />
                        <p class="text-[12px] text-fg-subtle">{{ $k['delivered_rate'] }} % distribués ou lus · {{ $k['failed'] }} échec(s)</p>
                    @endif
                    <a href="{{ route($channelRoute[$channel]) }}" wire:navigate class="inline-flex items-center gap-1 text-[12.5px] font-semibold text-brand-600 hover:underline dark:text-lagoon-300">Ouvrir {{ $k['label'] }}<x-ui.icon name="arrow-right" class="size-3.5" /></a>
                </div>
            </section>
        @endforeach
    </div>

    <div class="mt-5 grid items-start gap-5 xl:grid-cols-3">
        {{-- ═══ ACTIVITÉ RÉCENTE ══════════════════════════════════════════ --}}
        <x-ui.card class="xl:col-span-2" title="Activité récente" subtitle="Derniers messages tous canaux confondus" icon="bolt" :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($recent as $message)
                    @php
                        $conv = $message->conversation;
                        [$sLabel, $sTone] = \App\Livewire\Communication\Overview::statusMeta($message->status);
                        $route = $channelRoute[$message->channel] ?? 'communication';
                    @endphp
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span @class(['mt-0.5 grid size-8 shrink-0 place-items-center rounded-full', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300' => $message->direction === 'in', 'bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300' => $message->direction !== 'in'])>
                            <x-ui.icon :name="$message->direction === 'in' ? 'arrow-down-left' : 'arrow-up-right'" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                @if ($conv)
                                    <a href="{{ route($route, ['conversation' => $conv->getRouteKey()]) }}" wire:navigate class="truncate text-[13.5px] font-semibold text-fg hover:underline">{{ $conv->display_name }}</a>
                                @else
                                    <span class="text-[13.5px] font-semibold">—</span>
                                @endif
                                <x-ui.badge tone="neutral">{{ \App\Livewire\Communication\Overview::channels()[$message->channel]['label'] ?? $message->channel }}</x-ui.badge>
                                <x-ui.badge :tone="$sTone" dot>{{ $sLabel }}</x-ui.badge>
                            </div>
                            <p class="mt-0.5 truncate text-[12.5px] text-fg-muted">{{ $message->subject ? $message->subject.' — ' : '' }}{{ \Illuminate\Support\Str::limit(strip_tags((string) $message->body), 140) }}</p>
                        </div>
                        <span class="shrink-0 text-[11.5px] text-fg-subtle">{{ $message->created_at->shortRelativeDiffForHumans() }}</span>
                    </li>
                @empty
                    <li><x-ui.empty icon="bolt" title="Aucune activité" text="Les messages envoyés et reçus apparaîtront ici." /></li>
                @endforelse
            </ul>
        </x-ui.card>

        <div class="space-y-5">
            {{-- ═══ NUMÉROS WHATSAPP ══════════════════════════════════════ --}}
            <x-ui.card title="Numéros WhatsApp" subtitle="Répartition des conversations" icon="phone">
                <x-slot:actions>
                    @if ($isMaster)
                        <x-ui.button variant="ghost" size="sm" square icon="cog-6-tooth" :href="route('system', ['onglet' => 'integrations'])" aria-label="Configurer les numéros" />
                    @endif
                </x-slot:actions>
                <ul class="space-y-4">
                    @forelse ($numbers as $n)
                        @php $load = (int) data_get($n, 'open', data_get($n, 'conversations', 0)); @endphp
                        <li>
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-[13.5px] font-semibold">{{ $n['label'] }}</p>
                                    <p class="truncate text-[12px] text-fg-subtle tabular-nums">{{ $n['phone'] ?: 'Numéro non défini' }}</p>
                                </div>
                                <x-ui.badge :tone="$n['active'] ? 'success' : 'neutral'" dot>{{ $n['active'] ? 'Actif' : 'Inactif' }}</x-ui.badge>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <x-ui.progress class="flex-1" :value="$load / $maxLoad * 100" tone="lagoon" />
                                <span class="w-28 shrink-0 text-right text-[12px] text-fg-subtle tabular-nums">{{ $load }} conv. ouverte(s)</span>
                            </div>
                            @if (data_get($n, 'messages') !== null)<p class="mt-1 text-[11.5px] text-fg-subtle">{{ data_get($n, 'messages') }} message(s) sur 30 jours</p>@endif
                        </li>
                    @empty
                        <li class="text-[13px] text-fg-subtle">Aucun numéro configuré.</li>
                    @endforelse
                </ul>
            </x-ui.card>

            {{-- ═══ DERNIÈRES CAMPAGNES ═══════════════════════════════════ --}}
            <x-ui.card title="Dernières campagnes" icon="megaphone" :padded="false">
                <x-slot:actions><a href="{{ route('communication.campaigns') }}" wire:navigate class="text-[12.5px] font-semibold text-brand-600 hover:underline dark:text-lagoon-300">Tout voir</a></x-slot:actions>
                <ul class="divide-y divide-line">
                    @forelse ($campaigns as $campaign)
                        @php
                            [$cLabel, $cTone] = \App\Livewire\Communication\Overview::statusMeta($campaign->status);
                            $total = $campaign->stat('total');
                            $done = $campaign->stat('sent') + $campaign->stat('failed');
                        @endphp
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ route('communication.campaigns.show', $campaign) }}" wire:navigate class="min-w-0 truncate text-[13.5px] font-semibold hover:underline">{{ $campaign->name }}</a>
                                <x-ui.badge :tone="$cTone" dot>{{ $campaign->status === 'sent' ? 'Envoyée' : $cLabel }}</x-ui.badge>
                            </div>
                            <p class="mt-0.5 text-[12px] text-fg-subtle">{{ \App\Livewire\Communication\Overview::channels()[$campaign->channel]['label'] ?? $campaign->channel }} · {{ $campaign->stat('sent') }}/{{ $total }} envoyé(s){{ $campaign->stat('failed') ? ' · '.$campaign->stat('failed').' échec(s)' : '' }}</p>
                            @if ($total)<x-ui.progress class="mt-1.5" :value="$done / $total * 100" :tone="$campaign->stat('failed') ? 'sun' : 'emerald'" />@endif
                        </li>
                    @empty
                        <li><x-ui.empty icon="megaphone" title="Aucune campagne" text="Créez une campagne pour toucher vos clients en masse." /></li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </div>
</div>
