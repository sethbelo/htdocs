<div @if (collect($campaigns)->contains('status', 'sending')) wire:poll.5s @endif>
    <x-ui.page-header title="Campagnes" eyebrow="Communication & marketing" subtitle="Messages groupés WhatsApp, SMS et e-mail : ciblage des contacts, personnalisation, programmation et suivi des envois.">
        <x-slot:actions>
            @if ($canCampaign)<x-ui.button variant="accent" icon="plus" wire:click="create">Nouvelle campagne</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach (['all' => 'Toutes', 'draft' => 'Brouillons', 'scheduled' => 'Programmées', 'sending' => 'En cours', 'sent' => 'Envoyées', 'cancelled' => 'Annulées'] as $key => $label)
            <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['btn btn-sm', 'btn-primary' => $status === $key, 'btn-secondary' => $status !== $key])>
                {{ $label }} <span class="opacity-70">{{ $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0) }}</span>
            </button>
        @endforeach
        <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Rechercher une campagne…" class="ml-auto max-sm:!w-full" />
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Campagne</th><th>Canal</th><th>Statut</th><th>Progression</th><th class="max-md:hidden">Date</th><th></th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        @php
                            $meta = $channels[$campaign->channel] ?? $channels['whatsapp'];
                            [$stLabel, $stTone] = \App\Livewire\Communication\Campaigns::statusMeta($campaign->status);
                            $total = $campaign->stat('total'); $done = $campaign->stat('sent') + $campaign->stat('failed');
                        @endphp
                        <tr wire:key="camp-{{ $campaign->id }}">
                            <td>
                                <a href="{{ route('communication.campaigns.show', $campaign) }}" wire:navigate class="font-semibold hover:underline">{{ $campaign->name }}</a>
                                <p class="text-[12px] text-fg-subtle">{{ $campaign->creator?->name ?? '—' }}</p>
                            </td>
                            <td><x-ui.badge :tone="$meta['tone']" :icon="$meta['icon']">{{ $meta['label'] }}</x-ui.badge></td>
                            <td><x-ui.badge :tone="$stTone" dot>{{ $stLabel }}</x-ui.badge></td>
                            <td class="min-w-40">
                                @if ($total)
                                    <x-ui.progress :value="$total ? round($done / $total * 100) : 0" />
                                    <p class="mt-1 text-[12px] text-fg-subtle tabular-nums">{{ $campaign->stat('sent') }} envoyé(s) · {{ $campaign->stat('failed') }} échec(s) / {{ $total }}</p>
                                @else
                                    <span class="text-[12px] text-fg-subtle">—</span>
                                @endif
                            </td>
                            <td class="text-[12.5px] text-fg-muted max-md:hidden">
                                {{ ($campaign->finished_at ?? $campaign->started_at ?? $campaign->scheduled_at ?? $campaign->created_at)->translatedFormat('j M Y H:i') }}
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if ($canCampaign)
                                    @if ($campaign->isEditable())
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="edit({{ $campaign->id }})" title="Modifier" aria-label="Modifier"><x-ui.icon name="pencil-square" class="size-4" /></button>
                                    @endif
                                    <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="duplicate({{ $campaign->id }})" title="Dupliquer" aria-label="Dupliquer"><x-ui.icon name="document-duplicate" class="size-4" /></button>
                                    @if (in_array($campaign->status, ['scheduled', 'sending'], true))
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="cancel({{ $campaign->id }})" wire:confirm="Annuler cette campagne ? Les messages non encore envoyés ne partiront pas." title="Annuler" aria-label="Annuler"><x-ui.icon name="x-circle" class="size-4 text-flame-600" /></button>
                                    @elseif (in_array($campaign->status, ['draft', 'cancelled'], true))
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="delete({{ $campaign->id }})" wire:confirm="Supprimer cette campagne ?" title="Supprimer" aria-label="Supprimer"><x-ui.icon name="trash" class="size-4 text-flame-600" /></button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="megaphone" title="Aucune campagne" text="Créez une campagne pour informer vos clients d’une promotion, d’un nouveau service ou d’un rappel." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ ASSISTANT DE CAMPAGNE ════════════════════════════════════════════ --}}
    <x-ui.modal wire:model="showWizard" :title="$editingId ? 'Modifier la campagne' : 'Nouvelle campagne'" icon="megaphone" width="max-w-4xl">
        @if ($showWizard)
            <ol class="mb-5 grid grid-cols-4 gap-2 text-left">
                @foreach ([1 => 'Canal', 2 => 'Ciblage', 3 => 'Message', 4 => 'Envoi'] as $n => $label)
                    <li>
                        <button type="button" wire:click="goTo({{ $n }})" @class(['flex w-full items-center gap-2 rounded-lg border px-2.5 py-2 text-[12.5px] font-semibold transition', 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-white' => $step === $n, 'border-line text-fg-muted hover:border-line-strong' => $step !== $n])>
                            <span @class(['grid size-5 shrink-0 place-items-center rounded-full text-[11px]', 'bg-brand-600 text-white' => $step >= $n, 'bg-surface-2 text-fg-subtle' => $step < $n])>{{ $n }}</span><span class="truncate">{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <div class="text-left">
                {{-- 1. Canal --}}
                @if ($step === 1)
                    <div class="space-y-4">
                        <x-ui.input label="Nom de la campagne" wire:model="form.name" placeholder="Promotion menuiserie — octobre" required />
                        <div>
                            <p class="label">Canal d’envoi</p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($channels as $key => $meta)
                                    <label @class(['flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition', 'border-brand-500 bg-brand-50/60 ring-2 ring-brand-500/15 dark:bg-white/6' => $form['channel'] === $key, 'border-line hover:border-line-strong' => $form['channel'] !== $key])>
                                        <input type="radio" wire:model.live="form.channel" value="{{ $key }}" class="sr-only">
                                        <span class="size-9 shrink-0">{!! \App\Support\AppIcons::svg('communication.'.($key === 'email' ? 'emails' : $key)) !!}</span>
                                        <span class="font-semibold">{{ $meta['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                {{-- 2. Ciblage --}}
                @elseif ($step === 2)
                    <div class="grid gap-5 md:grid-cols-[1fr_220px]">
                        <div class="space-y-4">
                            <div>
                                <p class="label">Types de contacts</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($types as $value => $label)
                                        <label class="badge tone-neutral cursor-pointer gap-1.5 py-1.5"><input type="checkbox" wire:model.live="form.types" value="{{ $value }}"> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <p class="label">Segments</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($segments as $value => $label)
                                        <label class="badge tone-neutral cursor-pointer gap-1.5 py-1.5"><input type="checkbox" wire:model.live="form.segments" value="{{ $value }}"> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>
                            @if ($wizard['cities']->isNotEmpty())
                                <div>
                                    <p class="label">Villes</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($wizard['cities'] as $city)
                                            <label class="badge tone-neutral cursor-pointer gap-1.5 py-1.5"><input type="checkbox" wire:model.live="form.cities" value="{{ $city }}"> {{ $city }}</label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div>
                                <p class="label">Contacts précis (facultatif)</p>
                                <x-ui.search wire:model.live.debounce.250ms="contactSearch" placeholder="Ajouter un contact…" class="!w-full" />
                                @if ($wizard['matches']->isNotEmpty())
                                    <ul class="mt-1 max-h-40 divide-y divide-line overflow-y-auto rounded-xl border border-line">
                                        @foreach ($wizard['matches'] as $match)
                                            <li><button type="button" wire:click="toggleContact({{ $match->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left text-[13px] hover:bg-surface-2"><x-ui.icon name="plus" class="size-4 text-fg-subtle" />{{ $match->display_name }}</button></li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($wizard['picked']->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($wizard['picked'] as $contact)
                                            <button type="button" wire:click="toggleContact({{ $contact->id }})" class="badge tone-brand gap-1">{{ $contact->display_name }} <x-ui.icon name="x-mark" class="size-3" /></button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <x-ui.toggle wire:model.live="form.only_opted_in" label="Uniquement les contacts ayant accepté le marketing" description="Respecte le choix des clients qui ne souhaitent pas recevoir de messages promotionnels." />
                            @error('form.types')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="rounded-2xl bg-brand-600 p-5 text-center text-white">
                            <p class="text-[12px] font-semibold tracking-wider text-white/70 uppercase">Destinataires</p>
                            <p class="mt-2 font-display text-4xl font-bold tabular-nums">{{ $wizard['count'] }}</p>
                            <p class="mt-1 text-[12px] text-white/70">contacts joignables par {{ $channels[$form['channel']]['label'] }}</p>
                        </div>
                    </div>

                {{-- 3. Message --}}
                @elseif ($step === 3)
                    <div class="grid gap-5 md:grid-cols-[1fr_280px]">
                        <div class="space-y-3">
                            @if ($form['channel'] === 'email')
                                <x-ui.input label="Objet" wire:model.live.debounce.400ms="form.subject" placeholder="Nouveautés {entreprise}" required />
                            @endif
                            <div>
                                <div class="mb-1.5 flex items-center justify-between"><span class="label !mb-0">Message</span>@include('livewire.communication.partials.template-picker', ['templates' => $wizard['templates'], 'align' => 'right'])</div>
                                <textarea wire:model.live.debounce.400ms="form.body" rows="8" class="input" placeholder="Bonjour {prenom}, …"></textarea>
                                @error('form.body')<p class="field-error">{{ $message }}</p>@enderror
                                @error('form.subject')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (\App\Livewire\Communication\Campaigns::PLACEHOLDERS as $placeholder => $help)
                                    <button type="button" class="badge tone-brand cursor-pointer" wire:click="insert('{{ $placeholder }}')" title="{{ $help }}">{{ $placeholder }}</button>
                                @endforeach
                            </div>
                            @if ($form['channel'] === 'sms')
                                @php $len = mb_strlen($form['body'] ?? ''); @endphp
                                <p class="text-[12px] text-fg-subtle tabular-nums">{{ $len }} caractère(s) · {{ max(1, (int) ceil($len / 153)) }} SMS par destinataire</p>
                            @endif
                        </div>
                        <div>
                            <p class="label">Aperçu personnalisé</p>
                            <div class="comm-wallpaper max-h-80 space-y-2 overflow-y-auto rounded-xl border border-line p-3">
                                @forelse ($wizard['preview'] as $row)
                                    <div>
                                        <p class="mb-0.5 text-[11px] font-semibold text-fg-subtle">{{ $row['contact']->display_name }} · {{ $row['address'] }}</p>
                                        <div class="rounded-2xl rounded-br-md bg-brand-600 px-3 py-2 text-[12.5px] text-white">
                                            @if ($row['subject'])<p class="mb-1 font-semibold">{{ $row['subject'] }}</p>@endif
                                            <p class="break-words whitespace-pre-line">{{ $row['body'] ?: '…' }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-[12.5px] text-fg-subtle">Aucun destinataire pour l’aperçu.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                {{-- 4. Envoi --}}
                @else
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-3 rounded-xl border border-line p-4">
                            <p class="font-semibold">Récapitulatif</p>
                            <p class="text-[13px] text-fg-muted"><strong class="text-fg">{{ $form['name'] }}</strong> · {{ $channels[$form['channel']]['label'] }} · {{ $wizard['count'] }} destinataire(s)</p>
                            <x-ui.input label="Programmer l’envoi (facultatif)" type="datetime-local" wire:model.live="form.scheduled_at" hint="Laisser vide pour envoyer immédiatement." />
                        </div>
                        <div class="space-y-3 rounded-xl border border-dashed border-line-strong p-4">
                            <p class="font-semibold">Envoyer un test</p>
                            <p class="text-[12.5px] text-fg-subtle">Vérifiez le rendu sur votre téléphone ou votre messagerie avant l’envoi groupé.</p>
                            <div class="flex items-end gap-2">
                                <x-ui.input wire:model="testAddress" :placeholder="$form['channel'] === 'email' ? 'vous@sogeref.cd' : '+243 8x xxx xxxx'" class="min-w-0 flex-1" />
                                <x-ui.button variant="secondary" icon="paper-airplane" wire:click="sendTest">Tester</x-ui.button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Fermer</x-ui.button>
            @if ($step > 1)<x-ui.button variant="secondary" icon="arrow-left" wire:click="goTo({{ $step - 1 }})">Précédent</x-ui.button>@endif
            <x-ui.button variant="secondary" icon="document-check" wire:click="saveDraft">Brouillon</x-ui.button>
            @if ($step < 4)
                <x-ui.button iconRight="arrow-right" wire:click="next">Suivant</x-ui.button>
            @else
                <x-ui.button variant="accent" icon="paper-airplane" wire:click="launch" wire:confirm="Confirmer l’envoi de cette campagne ?">{{ $form['scheduled_at'] ? 'Programmer' : 'Envoyer maintenant' }}</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>
</div>
