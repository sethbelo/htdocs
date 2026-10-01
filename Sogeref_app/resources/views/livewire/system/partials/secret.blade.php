{{-- Champ secret : jamais réaffiché ; vide = valeur conservée. --}}
<x-ui.field :label="$label" :for="$channel.'.'.$field" :hint="$set ? 'Défini — laisser vide pour conserver' : 'Non défini'">
    <div class="flex gap-2">
        <input id="{{ $channel }}.{{ $field }}" type="password" autocomplete="new-password" wire:model="{{ $channel }}.{{ $field }}" class="input flex-1"
               placeholder="{{ $set ? '••••••••' : '' }}" @disabled(! $canEdit)>
        @if ($set && $canEdit)
            <button type="button" class="btn btn-ghost btn-icon" title="Effacer ce secret" aria-label="Effacer ce secret" wire:click="forget('{{ $channel }}', '{{ $field }}')" wire:confirm="Effacer définitivement ce secret ?"><x-ui.icon name="trash" class="size-4 text-flame-600" /></button>
        @endif
    </div>
</x-ui.field>
