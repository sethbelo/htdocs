<div wire:poll.15s>
    <x-ui.page-header title="Messagerie" eyebrow="Collaboration" subtitle="Échanges internes entre les équipes : siège, atelier, chantiers, caisse.">
        <x-slot:actions><x-ui.button variant="accent" icon="pencil-square" wire:click="createConversation">Nouvelle discussion</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="card grid h-[calc(100dvh-13rem)] min-h-[480px] overflow-hidden md:grid-cols-[320px_1fr]">
        {{-- ═══ CONVERSATIONS ════════════════════════════════════════════════ --}}
        <aside @class(['flex flex-col border-r border-line', 'max-md:hidden' => $active])>
            <div class="border-b border-line p-3"><x-ui.search wire:model.live.debounce.300ms="search" placeholder="Rechercher une personne…" class="!w-full" /></div>
            <ul class="flex-1 overflow-y-auto">
                @forelse ($conversations as $conversation)
                    @php
                        $others = $conversation->participants->where('id', '!=', auth()->id());
                        $name = $conversation->title ?: $others->pluck('name')->implode(', ');
                        $last = $conversation->latestMessage;
                        $unread = $last && $last->user_id !== auth()->id() && (! ($read = $conversation->participants->firstWhere('id', auth()->id())?->pivot?->last_read_at) || $last->created_at->gt($read));
                    @endphp
                    <li>
                        <button type="button" wire:click="selectConversation({{ $conversation->id }})" @class(['flex w-full items-center gap-3 border-b border-line px-4 py-3 text-left transition', 'bg-brand-50 dark:bg-white/6' => $active?->id === $conversation->id, 'hover:bg-surface-2' => $active?->id !== $conversation->id])>
                            <x-ui.avatar :name="$name ?: 'Discussion'" size="md" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2"><p @class(['truncate text-[13.5px]', 'font-bold' => $unread, 'font-semibold' => ! $unread])>{{ $name ?: 'Discussion' }}</p><span class="shrink-0 text-[11px] text-fg-subtle">{{ $last?->created_at?->shortRelativeDiffForHumans() }}</span></div>
                                <p @class(['truncate text-[12.5px]', 'text-fg' => $unread, 'text-fg-subtle' => ! $unread])>{{ $last ? ($last->user_id === auth()->id() ? 'Vous : ' : '').($last->body ?: '📎 Pièce jointe') : 'Nouvelle discussion' }}</p>
                            </div>
                            @if ($unread)<span class="size-2.5 shrink-0 rounded-full bg-flame-500"></span>@endif
                        </button>
                    </li>
                @empty
                    <li><x-ui.empty icon="chat-bubble-left-right" title="Aucune discussion" text="Démarrez une conversation avec un collègue." /></li>
                @endforelse
            </ul>
        </aside>

        {{-- ═══ FIL DE DISCUSSION ════════════════════════════════════════════ --}}
        <section @class(['flex min-w-0 flex-col', 'max-md:hidden' => ! $active])>
            @if ($active)
                @php $others = $active->participants->where('id', '!=', auth()->id()); @endphp
                <header class="flex items-center gap-3 border-b border-line px-4 py-3">
                    <button type="button" class="btn btn-ghost btn-sm btn-icon md:hidden" wire:click="$set('selectedConversation', null)"><x-ui.icon name="arrow-left" /></button>
                    <x-ui.avatar :name="$others->pluck('name')->implode(' ')" size="sm" />
                    <div class="min-w-0"><p class="truncate font-semibold">{{ $active->title ?: $others->pluck('name')->implode(', ') }}</p><p class="truncate text-[12px] text-fg-subtle">{{ $others->map(fn ($u) => $u->email)->implode(' · ') }}</p></div>
                </header>
                <div class="flex-1 space-y-3 overflow-y-auto bg-surface-2/60 px-4 py-5" x-data x-init="$el.scrollTop = $el.scrollHeight" x-on:message-sent.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                    @foreach ($active->messages as $message)
                        @php $mine = $message->user_id === auth()->id(); @endphp
                        <div @class(['flex gap-2', 'justify-end' => $mine])>
                            @unless ($mine)<x-ui.avatar :name="$message->author?->name ?? '?'" size="xs" class="mt-1" />@endunless
                            <div @class(['max-w-[78%] rounded-2xl px-3.5 py-2.5 text-[13.5px] shadow-sm', 'rounded-br-md bg-brand-600 text-white' => $mine, 'rounded-bl-md bg-surface ring-1 ring-line' => ! $mine])>
                                @unless ($mine)<p class="mb-0.5 text-[11.5px] font-semibold text-lagoon-700 dark:text-lagoon-300">{{ $message->author?->name }}</p>@endunless
                                @if ($message->body)<p class="whitespace-pre-line">{{ $message->body }}</p>@endif
                                @foreach ($message->attachments as $file)
                                    @if ($file->isImage())
                                        <a href="{{ route('chat.attachments.preview', $file) }}" target="_blank"><img src="{{ route('chat.attachments.preview', $file) }}" class="mt-2 max-h-52 rounded-lg" alt="{{ $file->original_name }}"></a>
                                    @else
                                        <a href="{{ route('chat.attachments.download', $file) }}" @class(['mt-2 flex items-center gap-2 rounded-lg px-2.5 py-2 text-[12.5px]', 'bg-white/15' => $mine, 'bg-surface-2' => ! $mine])><x-ui.icon name="paper-clip" class="size-4" /><span class="truncate">{{ $file->original_name }}</span></a>
                                    @endif
                                @endforeach
                                <p @class(['mt-1 text-right text-[10.5px]', 'text-white/60' => $mine, 'text-fg-subtle' => ! $mine])>{{ $message->created_at->format('d/m H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <form wire:submit="send" class="border-t border-line p-3">
                    @if ($attachments)<div class="mb-2 flex flex-wrap gap-1.5">@foreach ($attachments as $file)<x-ui.badge icon="paper-clip">{{ $file->getClientOriginalName() }}</x-ui.badge>@endforeach</div>@endif
                    @error('body')<p class="field-error mb-1">{{ $message }}</p>@enderror
                    <div class="flex items-end gap-2">
                        <label class="btn btn-ghost btn-icon cursor-pointer" title="Joindre un fichier"><x-ui.icon name="paper-clip" /><input type="file" wire:model="attachments" multiple class="sr-only"></label>
                        <textarea wire:model="body" rows="1" class="input min-h-10 flex-1 resize-none py-2.5" placeholder="Écrire un message…" x-data x-on:keydown.enter.prevent="if (! $event.shiftKey) $wire.send()"></textarea>
                        <x-ui.button type="submit" variant="primary" square icon="paper-airplane" aria-label="Envoyer" />
                    </div>
                </form>
            @else
                <div class="grid flex-1 place-items-center"><x-ui.empty icon="chat-bubble-left-right" title="Sélectionnez une discussion" /></div>
            @endif
        </section>
    </div>

    <x-ui.modal wire:model="showNew" title="Nouvelle discussion" icon="chat-bubble-left-right">
        <x-ui.select wire:model="recipientId" placeholder="Choisir un collègue…" :options="$users->mapWithKeys(fn ($u) => [$u->id => $u->name.' — '.$u->email])" />
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" wire:click="startConversation">Démarrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
