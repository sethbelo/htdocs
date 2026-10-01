{{-- Envoi rapide depuis la fiche contact : <livewire:communication.quick-send :contact="$contact" /> --}}
<div>
    @if ($canSend)
        <div class="flex flex-wrap gap-2">
            @foreach ($channels as $key => $meta)
                <button type="button" class="btn btn-secondary btn-sm" wire:click="start('{{ $key }}')" @disabled(! $addresses[$key]) title="{{ $addresses[$key] ?: 'Aucune adresse pour ce canal' }}">
                    <x-ui.icon :name="$meta['icon']" class="size-4" />{{ $meta['label'] }}
                </button>
            @endforeach
        </div>
        @if ($history->isNotEmpty())
            <ul class="mt-3 space-y-1.5">
                @foreach ($history as $conversation)
                    <li class="flex items-center gap-2 text-[12.5px]">
                        <x-ui.icon :name="$channels[$conversation->channel]['icon'] ?? 'chat-bubble-left'" class="size-4 shrink-0 text-fg-subtle" />
                        <a href="{{ route($routes[$conversation->channel], ['conversation' => $conversation->getRouteKey()]) }}" wire:navigate class="min-w-0 flex-1 truncate hover:underline">{{ \Illuminate\Support\Str::limit(strip_tags((string) $conversation->latestMessage?->body), 70) }}</a>
                        <span class="shrink-0 text-fg-subtle">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-ui.modal wire:model="open" :title="'Écrire à '.$contact->display_name" :icon="$channels[$channel]['icon']">
            <div class="space-y-3 text-left">
                <div class="flex gap-1.5" role="group" aria-label="Canal">
                    @foreach ($channels as $key => $meta)
                        <button type="button" wire:click="$set('channel', '{{ $key }}')" @disabled(! $addresses[$key]) @class(['btn btn-sm', 'btn-primary' => $channel === $key, 'btn-secondary' => $channel !== $key])>{{ $meta['label'] }}</button>
                    @endforeach
                </div>
                <p class="text-[12.5px] text-fg-subtle">Destinataire : <span class="font-medium text-fg">{{ $addresses[$channel] ?: '—' }}</span></p>
                @if ($channel === 'email')<x-ui.input label="Objet" wire:model="subject" required />@endif
                <div>
                    <div class="mb-1.5 flex items-center justify-between"><span class="label !mb-0">Message</span>@include('livewire.communication.partials.template-picker', ['templates' => $templates, 'align' => 'right'])</div>
                    <textarea wire:model="body" rows="5" class="input" placeholder="Bonjour {{ $contact->first_name ?: $contact->display_name }}, …"></textarea>
                    @error('body')<p class="field-error">{{ $message }}</p>@enderror
                    @error('subject')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <x-slot:footer>
                <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
                <x-ui.button icon="paper-airplane" wire:click="send">Envoyer</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
