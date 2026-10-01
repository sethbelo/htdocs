<div wire:poll.10s>
    @php
        $isSms = $channel === 'sms';
        $isEmail = $channel === 'email';
        $numberLabels = $numbers->mapWithKeys(fn ($n) => [$n['phone'] => $n['label']]);
    @endphp

    <x-ui.page-header :title="$meta['label']" eyebrow="Communication" :subtitle="$isEmail ? 'E-mails échangés avec les clients : envoi individuel ou groupé, réponses dans le fil, modèles.' : ($isSms ? 'Conversations SMS avec les clients : réponses, modèles et suivi de distribution.' : 'Conversations WhatsApp des 3 numéros de l’entreprise : réponses en direct, modèles, attribution et suivi de lecture.')">
        <x-slot:actions>
            @if ($unreadTotal)<x-ui.badge tone="danger" dot>{{ $unreadTotal }} non lu(s)</x-ui.badge>@endif
            @if ($canSend)<x-ui.button variant="accent" icon="pencil-square" wire:click="openNew">{{ $isEmail ? 'Nouvel e-mail' : 'Nouvelle conversation' }}</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card grid h-[calc(100dvh-13rem)] min-h-[520px] overflow-hidden md:grid-cols-[340px_1fr]">
        {{-- ═══ CONVERSATIONS ════════════════════════════════════════════════ --}}
        <aside @class(['flex min-h-0 flex-col border-r border-line', 'max-md:hidden' => $active])>
            <div class="space-y-2 border-b border-line p-3">
                <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Nom, numéro ou message…" class="!w-full" />
                <div class="grid grid-cols-2 gap-2">
                    <select wire:model.live="status" class="input h-8 text-[12.5px]" aria-label="Statut">
                        <option value="open">Ouvertes</option><option value="closed">Clôturées</option><option value="all">Toutes</option>
                    </select>
                    <select wire:model.live="assignee" class="input h-8 text-[12.5px]" aria-label="Attribution">
                        <option value="all">Tous les agents</option><option value="me">Attribuées à moi</option><option value="none">Non attribuées</option>
                    </select>
                </div>
                @if ($numbers->isNotEmpty())
                    <div class="flex gap-1.5 overflow-x-auto scrollbar-none" role="group" aria-label="Numéro de l’entreprise">
                        <button type="button" wire:click="$set('number', '')" @class(['shrink-0 rounded-full border px-2.5 py-1 text-[11.5px] font-semibold transition', 'border-brand-600 bg-brand-600 text-white' => $number === '', 'border-line text-fg-muted hover:bg-surface-2' => $number !== ''])>Tous</button>
                        @foreach ($numbers as $n)
                            <button type="button" wire:click="$set('number', @js($n['phone']))" title="{{ $n['phone'] }}" @class(['shrink-0 rounded-full border px-2.5 py-1 text-[11.5px] font-semibold transition', 'border-brand-600 bg-brand-600 text-white' => $number === $n['phone'], 'border-line text-fg-muted hover:bg-surface-2' => $number !== $n['phone']])>{{ $n['label'] }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
            <ul class="min-h-0 flex-1 overflow-y-auto">
                @forelse ($conversations as $conversation)
                    @php $last = $conversation->latestMessage; $unread = $conversation->unread_count > 0; @endphp
                    <li wire:key="cv-{{ $conversation->id }}">
                        <button type="button" wire:click="select({{ $conversation->id }})" @class(['flex w-full items-center gap-3 border-b border-line px-4 py-3 text-left transition', 'bg-brand-50 dark:bg-white/6' => $active?->id === $conversation->id, 'hover:bg-surface-2' => $active?->id !== $conversation->id])>
                            <x-ui.avatar :name="$conversation->display_name" size="md" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p @class(['truncate text-[13.5px]', 'font-bold' => $unread, 'font-semibold' => ! $unread])>{{ $conversation->display_name }}</p>
                                    <span @class(['shrink-0 text-[11px]', 'font-semibold text-emerald-600 dark:text-emerald-400' => $unread, 'text-fg-subtle' => ! $unread])>{{ $conversation->last_message_at?->shortRelativeDiffForHumans() }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    @if ($last && $last->direction === 'out')@include('livewire.communication.partials.ticks', ['status' => $last->status])@endif
                                    <p @class(['min-w-0 flex-1 truncate text-[12.5px]', 'text-fg' => $unread, 'text-fg-subtle' => ! $unread])>{{ $last ? ($last->direction === 'out' ? 'Vous : ' : '').$last->body : 'Nouvelle conversation' }}</p>
                                    @if ($unread)<span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-emerald-500 px-1.5 text-[10.5px] font-bold text-white">{{ $conversation->unread_count }}</span>@endif
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-fg-subtle">
                                    @if ($conversation->sender && $numberLabels->has($conversation->sender))<span class="inline-flex items-center gap-1"><x-ui.icon name="phone" class="size-3" />{{ $numberLabels[$conversation->sender] }}</span>@endif
                                    @if ($conversation->assignee)<span class="inline-flex items-center gap-1"><x-ui.icon name="user" class="size-3" />{{ $conversation->assignee->name }}</span>@endif
                                    @if ($conversation->status === 'closed')<span class="inline-flex items-center gap-1"><x-ui.icon name="archive-box" class="size-3" />Clôturée</span>@endif
                                </div>
                            </div>
                        </button>
                    </li>
                @empty
                    <li><x-ui.empty :icon="$meta['icon']" title="Aucune conversation" :text="$search ? 'Aucun résultat pour cette recherche.' : 'Les messages reçus et envoyés apparaîtront ici.'" /></li>
                @endforelse
            </ul>
        </aside>

        {{-- ═══ FIL DE DISCUSSION ════════════════════════════════════════════ --}}
        <section @class(['flex min-h-0 min-w-0 flex-col', 'max-md:hidden' => ! $active])>
            @if ($active)
                <header class="flex items-center gap-3 border-b border-line px-3 py-2.5 sm:px-4">
                    <button type="button" class="btn btn-ghost btn-sm btn-icon md:hidden" wire:click="back" aria-label="Retour à la liste"><x-ui.icon name="arrow-left" /></button>
                    <x-ui.avatar :name="$active->display_name" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">
                            @if ($active->contact)
                                <a href="{{ route('contacts.show', $active->contact) }}" wire:navigate class="hover:underline">{{ $active->display_name }}</a>
                            @else
                                {{ $active->display_name }}
                            @endif
                        </p>
                        <p class="truncate text-[12px] text-fg-subtle">
                            {{ $active->address }}
                            @if ($active->sender) · via {{ $numberLabels[$active->sender] ?? '' }} {{ $active->sender }}@endif
                            @if ($active->assignee) · {{ $active->assignee->name }}@endif
                        </p>
                        @if ($isEmail && $active->subject)<p class="truncate text-[12.5px] font-medium text-fg-muted">Objet : {{ $active->subject }}</p>@endif
                    </div>
                    @php [$stLabel, $stTone] = \App\Livewire\Communication\Inbox::statusMeta($active->status); @endphp
                    <x-ui.badge :tone="$stTone" dot class="max-sm:hidden">{{ $stLabel }}</x-ui.badge>
                    @if ($canSend)
                        <div x-data="{ menu: false }" class="relative shrink-0" x-on:click.outside="menu = false">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon" x-on:click="menu = !menu" aria-label="Actions"><x-ui.icon name="ellipsis-vertical" /></button>
                            <div x-show="menu" x-cloak x-transition class="absolute right-0 z-30 mt-1 w-56 rounded-xl border border-line bg-surface p-1 shadow-[var(--shadow-pop)]">
                                @if ($active->assigned_to !== auth()->id())
                                    <button type="button" wire:click="assignToMe" x-on:click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-[13px] hover:bg-surface-2"><x-ui.icon name="user-plus" class="size-4" />M’attribuer</button>
                                @else
                                    <button type="button" wire:click="unassign" x-on:click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-[13px] hover:bg-surface-2"><x-ui.icon name="user-minus" class="size-4" />Retirer l’attribution</button>
                                @endif
                                @if ($active->status === 'open')
                                    <button type="button" wire:click="close" x-on:click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-[13px] hover:bg-surface-2"><x-ui.icon name="archive-box" class="size-4" />Clôturer</button>
                                @else
                                    <button type="button" wire:click="reopen" x-on:click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-[13px] hover:bg-surface-2"><x-ui.icon name="arrow-uturn-left" class="size-4" />Rouvrir</button>
                                @endif
                                @if ($active->contact)
                                    <a href="{{ route('contacts.show', $active->contact) }}" wire:navigate class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-[13px] hover:bg-surface-2"><x-ui.icon name="identification" class="size-4" />Fiche contact</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </header>

                <div class="comm-wallpaper min-h-0 flex-1 space-y-2 overflow-y-auto px-3 py-4 sm:px-6" x-data="scrollBottom">
                    @php $day = null; @endphp
                    @forelse ($active->messages as $message)
                        @php $mine = $message->direction === 'out'; $at = $message->sent_at ?? $message->created_at; @endphp
                        @if ($day !== $at->toDateString())
                            @php $day = $at->toDateString(); @endphp
                            <div class="flex justify-center py-1"><span class="rounded-full bg-surface px-3 py-1 text-[11px] font-semibold text-fg-subtle shadow-sm ring-1 ring-line">{{ $at->isToday() ? 'Aujourd’hui' : ($at->isYesterday() ? 'Hier' : ucfirst($at->translatedFormat('l j F'))) }}</span></div>
                        @endif
                        <div wire:key="msg-{{ $message->id }}" @class(['flex', 'justify-end' => $mine])>
                            <div @class(['max-w-[85%] rounded-2xl px-3 py-2 text-[13.5px] shadow-sm sm:max-w-[70%]', 'rounded-br-md bg-brand-600 text-white' => $mine && $message->status !== 'failed', 'rounded-br-md bg-flame-600 text-white' => $mine && $message->status === 'failed', 'rounded-bl-md bg-surface ring-1 ring-line' => ! $mine])>
                                @if ($message->campaign_id)<p @class(['mb-0.5 inline-flex items-center gap-1 text-[11px] font-semibold', 'text-white/70' => $mine, 'text-fg-subtle' => ! $mine])><x-ui.icon name="megaphone" class="size-3" />Campagne</p>@endif
                                @if ($isEmail && $message->subject)<p class="mb-1 font-semibold">{{ $message->subject }}</p>@endif
                                <p class="break-words whitespace-pre-line">{{ $isEmail ? trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>/i', "\n", $message->body)))) : $message->body }}</p>
                                @if ($message->status === 'failed' && $message->error)<p class="mt-1 text-[11.5px] text-white/85">{{ $message->error }}</p>@endif
                                <p @class(['mt-0.5 flex items-center justify-end gap-1 text-[10.5px]', 'text-white/65' => $mine, 'text-fg-subtle' => ! $mine])>
                                    @if ($mine && $message->sender)<span class="truncate">{{ $message->sender->name }} ·</span>@endif
                                    <span>{{ $at->format('H:i') }}</span>
                                    @if ($mine)@include('livewire.communication.partials.ticks', ['status' => $message->status, 'light' => true])@endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="grid h-full place-items-center"><x-ui.empty :icon="$meta['icon']" title="Aucun message" text="Écrivez le premier message ci-dessous." /></div>
                    @endforelse
                </div>

                @if ($canSend)
                    <form wire:submit="send" class="border-t border-line bg-surface p-2.5 sm:p-3">
                        @error('body')<p class="field-error mb-1 px-1">{{ $message }}</p>@enderror
                        @if ($active->status === 'closed')<p class="mb-1.5 px-1 text-[12px] text-fg-subtle">Conversation clôturée : envoyer un message la rouvrira.</p>@endif
                        <div class="flex items-end gap-1.5" @if ($isSms) x-data="smsCounter($wire.entangle('body'))" @endif>
                            @include('livewire.communication.partials.template-picker', ['templates' => $templates, 'up' => true])
                            <div class="min-w-0 flex-1">
                                <textarea wire:model="body" rows="1" class="input max-h-40 min-h-10 resize-none py-2.5" placeholder="Écrire un message…  (Entrée pour envoyer, Maj+Entrée pour un saut de ligne)" aria-label="Message"
                                          x-data x-init="const fit = () => { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 160) + 'px' }; fit(); $el.addEventListener('input', fit); $wire.on('comm-message-sent', () => $nextTick(fit))"
                                          x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.send() }"></textarea>
                                @if ($isSms)<p class="mt-1 px-1 text-[11.5px] tabular-nums text-fg-subtle" x-text="label"></p>@endif
                            </div>
                            <x-ui.button type="submit" variant="primary" square icon="paper-airplane" aria-label="Envoyer" />
                        </div>
                    </form>
                @else
                    <p class="border-t border-line p-3 text-center text-[12.5px] text-fg-subtle">Lecture seule : votre rôle ne permet pas d’envoyer de messages.</p>
                @endif
            @else
                <div class="comm-wallpaper grid flex-1 place-items-center">
                    <x-ui.empty :icon="$meta['icon']" title="Sélectionnez une conversation" :text="$isEmail ? 'Choisissez un échange dans la liste ou rédigez un nouvel e-mail.' : ($isSms ? 'Choisissez un échange SMS dans la liste ou démarrez-en un nouveau.' : 'Choisissez une conversation WhatsApp dans la liste ou démarrez-en une nouvelle.')">
                        @if ($canSend)<x-ui.button variant="soft" icon="pencil-square" wire:click="openNew">Nouvelle conversation</x-ui.button>@endif
                    </x-ui.empty>
                </div>
            @endif
        </section>
    </div>

    {{-- ═══ NOUVELLE CONVERSATION ════════════════════════════════════════════ --}}
    <x-ui.modal wire:model="showNew" :title="$isEmail ? 'Nouvel e-mail client' : 'Nouvelle conversation '.$meta['label']" :icon="$meta['icon']">
        <div class="space-y-3 text-left">
            @if ($newContact)
                <div class="flex items-center gap-3 rounded-xl border border-line bg-surface-2 p-2.5">
                    <x-ui.avatar :name="$newContact->display_name" size="sm" />
                    <div class="min-w-0 flex-1"><p class="truncate text-[13.5px] font-semibold text-fg">{{ $newContact->display_name }}</p><p class="truncate text-[12px] text-fg-subtle">{{ $isEmail ? $newContact->email : $newContact->phone }}</p></div>
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="clearContact" aria-label="Retirer le contact"><x-ui.icon name="x-mark" class="size-4" /></button>
                </div>
            @else
                <x-ui.field label="Contact">
                    <x-ui.search wire:model.live.debounce.250ms="contactSearch" placeholder="{{ $isEmail ? 'Rechercher un contact (nom, e-mail)…' : 'Rechercher un contact (nom, numéro)…' }}" class="!w-full" />
                </x-ui.field>
                @if ($matches->isNotEmpty())
                    <ul class="max-h-48 divide-y divide-line overflow-y-auto rounded-xl border border-line">
                        @foreach ($matches as $match)
                            <li><button type="button" wire:click="pickContact({{ $match->id }})" class="flex w-full items-center gap-2.5 px-3 py-2 text-left hover:bg-surface-2"><x-ui.avatar :name="$match->display_name" size="xs" /><span class="min-w-0 flex-1 truncate text-[13px] font-medium text-fg">{{ $match->display_name }}</span><span class="text-[12px] text-fg-subtle tabular-nums">{{ $isEmail ? $match->email : $match->phone }}</span></button></li>
                        @endforeach
                    </ul>
                @elseif (mb_strlen(trim($contactSearch)) >= 2)
                    <p class="text-[12.5px] text-fg-subtle">{{ $isEmail ? 'Aucun contact avec e-mail trouvé — saisissez l’adresse ci-dessous.' : 'Aucun contact avec numéro trouvé — saisissez le numéro ci-dessous.' }}</p>
                @endif
            @endif
            @if ($isEmail)
                <x-ui.input label="Destinataire(s)" wire:model="newAddress" placeholder="client@exemple.cd, autre@exemple.cd" inputmode="email" hint="Plusieurs adresses possibles, séparées par une virgule : un e-mail individuel est envoyé à chacune." required />
                <x-ui.input label="Objet" wire:model="newSubject" placeholder="Votre devis SOGEREF" required />
            @else
                <x-ui.input label="Numéro" wire:model="newAddress" placeholder="+243 81 000 0000" inputmode="tel" hint="Format international conseillé (+243…)." required />
            @endif
            <div @if ($isSms) x-data="smsCounter($wire.entangle('newBody'))" @endif>
                <div class="mb-1.5 flex items-center justify-between"><span class="label !mb-0">{{ $isEmail ? 'Message' : 'Premier message (facultatif)' }}</span>@include('livewire.communication.partials.template-picker', ['templates' => $templates, 'args' => ", 'newBody'", 'align' => 'right'])</div>
                <textarea wire:model="newBody" rows="4" class="input" placeholder="Bonjour {prenom}, …"></textarea>
                @error('newBody')<p class="field-error">{{ $message }}</p>@enderror
                @if ($isSms)<p class="mt-1 text-[11.5px] tabular-nums text-fg-subtle" x-text="label"></p>@endif
            </div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="paper-airplane" wire:click="startConversation">{{ $isEmail ? 'Envoyer' : 'Ouvrir la conversation' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
