@props(['title' => 'Confirmer votre mot de passe', 'content' => 'Pour votre sécurité, saisissez votre mot de passe afin de poursuivre.', 'button' => 'Confirmer'])

@php $confirmableId = md5($attributes->wire('then')); @endphp

<span {{ $attributes->wire('then') }} x-data x-ref="span" x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')" x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);">{{ $slot }}</span>

@once
    <flux:modal wire:model.live="confirmingPassword" class="md:w-[30rem]" :dismissible="false">
        <div class="space-y-5" x-data="{}" x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password?.focus(), 250)">
            <div class="flex items-start gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300"><flux:icon.lock-closed class="size-5" /></span><div><flux:heading size="lg">{{ $title }}</flux:heading><flux:text class="mt-1">{{ $content }}</flux:text></div></div>
            <flux:input x-ref="confirmable_password" wire:model="confirmablePassword" wire:keydown.enter="confirmPassword" type="password" label="Mot de passe actuel" icon="key" autocomplete="current-password" viewable />
            <div class="flex justify-end gap-2"><flux:button type="button" variant="ghost" wire:click="stopConfirmingPassword" wire:loading.attr="disabled">Annuler</flux:button><flux:button type="button" variant="primary" icon="check" dusk="confirm-password-button" wire:click="confirmPassword" wire:loading.attr="disabled">{{ $button }}</flux:button></div>
        </div>
    </flux:modal>
@endonce
