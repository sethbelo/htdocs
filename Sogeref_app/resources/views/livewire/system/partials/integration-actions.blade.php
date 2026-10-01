{{-- Pied de carte d'intégration : adresse de test + « Tester » + « Enregistrer ». --}}
@if ($canEdit)
    <div class="mt-5 flex flex-wrap items-end justify-between gap-3 border-t border-line pt-4">
        <div class="flex min-w-0 flex-1 items-end gap-2">
            <x-ui.input label="Envoyer un test à" wire:model="testTo.{{ $channel }}" :placeholder="$placeholder" class="min-w-0 flex-1 sm:max-w-xs" />
            <x-ui.button variant="secondary" icon="paper-airplane" wire:click="test('{{ $channel }}')">Tester la connexion</x-ui.button>
        </div>
        <x-ui.button icon="check" wire:click="save('{{ $channel }}')">Enregistrer</x-ui.button>
    </div>
@endif
