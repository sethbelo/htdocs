<div @if ($campaign->status === 'sending') wire:poll.4s @endif>
    @php
        [$stLabel, $stTone] = \App\Livewire\Communication\CampaignShow::statusMeta($campaign->status);
        $total = $campaign->stat('total'); $sent = $campaign->stat('sent'); $failed = $campaign->stat('failed'); $delivered = $campaign->stat('delivered');
    @endphp
    <x-ui.page-header :title="$campaign->name" eyebrow="Campagne" :back="route('communication.campaigns')"
        :subtitle="$meta['label'].' · créée par '.($campaign->creator?->name ?? '—').' le '.$campaign->created_at->translatedFormat('j F Y')">
        <x-slot:actions>
            <x-ui.badge :tone="$stTone" dot>{{ $stLabel }}</x-ui.badge>
            @if ($canCampaign && $failed && $campaign->status !== 'cancelled')<x-ui.button variant="secondary" icon="arrow-path" wire:click="retryFailed">Relancer les échecs</x-ui.button>@endif
            @if ($canCampaign && in_array($campaign->status, ['scheduled', 'sending'], true))<x-ui.button variant="danger" icon="x-circle" wire:click="cancel" wire:confirm="Annuler cette campagne ?">Annuler</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Destinataires" :value="$total" icon="users" />
        <x-ui.stat label="Envoyés" :value="$sent" icon="paper-airplane" tone="lagoon" :hint="$total ? round($sent / $total * 100).' %' : null" />
        <x-ui.stat label="Distribués / lus" :value="$delivered" icon="check-circle" tone="emerald" />
        <x-ui.stat label="Échecs" :value="$failed" icon="exclamation-triangle" tone="flame" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <x-ui.card title="Message" icon="chat-bubble-left-ellipsis">
            @if ($campaign->scheduled_at && $campaign->status === 'scheduled')
                <p class="mb-3 flex items-center gap-2 text-[12.5px] text-sun-700 dark:text-sun-300"><x-ui.icon name="calendar" class="size-4" />Programmée le {{ $campaign->scheduled_at->translatedFormat('j F Y à H:i') }}</p>
            @endif
            <div class="comm-wallpaper rounded-xl border border-line p-3">
                <div class="rounded-2xl rounded-br-md bg-brand-600 px-3 py-2 text-[13px] text-white">
                    @if ($campaign->subject)<p class="mb-1 font-semibold">{{ $campaign->subject }}</p>@endif
                    <p class="break-words whitespace-pre-line">{{ $campaign->body }}</p>
                </div>
            </div>
            @if ($total)
                <div class="mt-4"><x-ui.progress :value="round(($sent + $failed) / max(1, $total) * 100)" label="Progression" /></div>
            @endif
        </x-ui.card>

        <x-ui.card title="Destinataires" icon="users" :padded="false">
            <x-slot:actions>
                <select wire:model.live="status" class="input h-9 w-40 text-[13px]" aria-label="Filtrer par statut">
                    <option value="all">Tous</option><option value="pending">En attente</option><option value="sent">Envoyés</option><option value="failed">Échecs</option><option value="skipped">Ignorés</option>
                </select>
            </x-slot:actions>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Contact</th><th>Adresse</th><th>Statut</th><th>Détail</th></tr></thead>
                    <tbody>
                        @forelse ($recipients as $recipient)
                            @php [$rLabel, $rTone] = \App\Livewire\Communication\CampaignShow::statusMeta($recipient->status); @endphp
                            <tr wire:key="rcp-{{ $recipient->id }}">
                                <td>@if ($recipient->contact)<a href="{{ route('contacts.show', $recipient->contact) }}" wire:navigate class="font-medium hover:underline">{{ $recipient->contact->display_name }}</a>@else — @endif</td>
                                <td class="text-[12.5px] tabular-nums">{{ $recipient->address }}</td>
                                <td><x-ui.badge :tone="$rTone" dot>{{ $rLabel }}</x-ui.badge></td>
                                <td class="max-w-64 truncate text-[12px] text-flame-600">{{ $recipient->error }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-ui.empty icon="users" title="Aucun destinataire" :text="$campaign->status === 'draft' ? 'Les destinataires sont calculés au lancement de la campagne.' : 'Aucun destinataire pour ce filtre.'" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($recipients->hasPages())<div class="border-t border-line p-3">{{ $recipients->links() }}</div>@endif
        </x-ui.card>
    </div>
</div>
